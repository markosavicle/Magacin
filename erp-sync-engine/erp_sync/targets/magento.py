"""Magento 2 / Adobe Commerce REST API: simple and virtual products.

Prices go through the bulk endpoint POST /V1/products/base-prices. Stock goes either to an MSI source
(POST /V1/inventory/source-items, inventory = "msi") or to the legacy stock item
(PUT /V1/products/{sku}/stockItems/{id}, inventory = "legacy").
Auth: an integration access token (System > Integrations), sent as a Bearer token.
"""
from __future__ import annotations

from decimal import Decimal
from urllib.parse import quote

import httpx

from ..models import Change, ShopProduct
from .base import ApplyResult, Target
from .http import ApiError, chunks, request

STOCKABLE_TYPES = {"simple", "virtual"}
PAGE_SIZE = 200


class MagentoTarget(Target):
    name = "magento"

    def __init__(self, url: str, token: str, inventory: str = "msi", source_code: str = "default",
                 store_code: str = "all", batch_size: int = 50, timeout: float = 30, transport=None):
        self.client = httpx.Client(
            base_url=f"{url.rstrip('/')}/rest/{store_code}/V1/", timeout=timeout, transport=transport,
            headers={"Authorization": f"Bearer {token}", "User-Agent": "erp-sync-engine/0.1"},
        )
        self.inventory = inventory
        self.source_code = source_code
        self.batch_size = batch_size
        self._stock_item_ids: dict[str, int] = {}

    def close(self) -> None:
        self.client.close()

    def _search(self, path: str, extra: dict | None = None, fields: str | None = None) -> list[dict]:
        items, page = [], 1
        while True:
            params = {"searchCriteria[pageSize]": PAGE_SIZE, "searchCriteria[currentPage]": page, **(extra or {})}
            if fields:
                params["fields"] = fields
            data = request(self.client, "GET", path, params=params).json()
            batch = data.get("items") or []
            items.extend(batch)
            if len(items) >= int(data.get("total_count") or 0) or not batch:
                return items
            page += 1

    def fetch_products(self) -> dict[str, ShopProduct]:
        products: dict[str, ShopProduct] = {}
        rows = self._search(
            "products",
            fields="items[sku,name,price,type_id,extension_attributes[stock_item[item_id,qty,manage_stock]]],total_count",
        )
        for p in rows:
            if p.get("type_id") not in STOCKABLE_TYPES or not p.get("sku"):
                continue
            stock_item = (p.get("extension_attributes") or {}).get("stock_item") or {}
            if stock_item.get("item_id") is not None:
                self._stock_item_ids[p["sku"]] = stock_item["item_id"]
            products[p["sku"]] = ShopProduct(
                sku=p["sku"], ref=p["sku"], name=p.get("name"),
                stock=None if stock_item.get("qty") is None else Decimal(str(stock_item["qty"])),
                price=None if p.get("price") is None else Decimal(str(p["price"])),
                manage_stock=stock_item.get("manage_stock", True) is not False,
            )

        if self.inventory == "msi":
            source_rows = self._search("inventory/source-items", {
                "searchCriteria[filter_groups][0][filters][0][field]": "source_code",
                "searchCriteria[filter_groups][0][filters][0][value]": self.source_code,
            })
            for row in source_rows:
                if row.get("sku") in products:
                    products[row["sku"]].stock = Decimal(str(row.get("quantity", 0)))
        return products

    def apply(self, changes: list[Change]) -> ApplyResult:
        result = ApplyResult()
        failed: dict[str, str] = {}
        prices = [c for c in changes if c.price_changed]
        stocks = [c for c in changes if c.stock_changed]

        for chunk in chunks(prices, self.batch_size):
            body = {"prices": [{"sku": c.ref, "price": float(c.new_price), "store_id": 0} for c in chunk]}
            try:
                errors = request(self.client, "POST", "products/base-prices", json=body).json() or []
            except ApiError as exc:
                failed.update({c.sku: str(exc) for c in chunk})
                continue
            for err in errors:   # [{"message": "...", "parameters": ["SKU", ...]}]
                params = err.get("parameters") or []
                bad = next((c.sku for c in chunk if c.ref in params), None)
                if bad:
                    failed[bad] = err.get("message", "price error")

        if self.inventory == "msi":
            for chunk in chunks(stocks, self.batch_size):
                body = {"sourceItems": [
                    {"sku": c.ref, "source_code": self.source_code, "quantity": float(c.new_stock),
                     "status": 1 if c.new_stock > 0 else 0}
                    for c in chunk
                ]}
                try:
                    request(self.client, "POST", "inventory/source-items", json=body)
                except ApiError as exc:
                    failed.update({c.sku: str(exc) for c in chunk})
        else:
            for c in stocks:
                item_id = self._stock_item_ids.get(c.ref)
                if item_id is None:
                    failed[c.sku] = "no stock item id (fetch_products first)"
                    continue
                body = {"stockItem": {"qty": float(c.new_stock), "is_in_stock": c.new_stock > 0}}
                try:
                    request(self.client, "PUT", f"products/{quote(c.ref, safe='')}/stockItems/{item_id}", json=body)
                except ApiError as exc:
                    failed[c.sku] = str(exc)

        touched = {c.sku for c in prices} | {c.sku for c in stocks}
        result.failed = sorted(failed.items())
        result.applied = len(touched - failed.keys())
        return result
