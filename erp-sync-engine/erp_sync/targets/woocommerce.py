"""WooCommerce REST API v3: simple products and variations, batch updates of up to 100 per call.

Auth: a REST API key (WooCommerce > Settings > Advanced > REST API) with Read/Write access,
sent as HTTP Basic auth, which WooCommerce accepts over HTTPS.
"""
from __future__ import annotations

from collections import defaultdict
from decimal import Decimal, InvalidOperation

import httpx

from ..models import Change, ShopProduct
from .base import ApplyResult, Target
from .http import ApiError, chunks, request

FIELDS = "id,sku,name,type,manage_stock,stock_quantity,regular_price"


class WooCommerceTarget(Target):
    name = "woocommerce"

    def __init__(self, url: str, key: str, secret: str, batch_size: int = 50, timeout: float = 30, transport=None):
        self.client = httpx.Client(
            base_url=url.rstrip("/") + "/wp-json/wc/v3/", auth=(key, secret), timeout=timeout,
            transport=transport, headers={"User-Agent": "erp-sync-engine/0.1"},
        )
        self.batch_size = batch_size

    def close(self) -> None:
        self.client.close()

    def _paged(self, path: str) -> list[dict]:
        items, page = [], 1
        while True:
            params = {"per_page": 100, "page": page, "_fields": FIELDS}
            if path == "products":
                params["status"] = "any"   # drafts and private products have stock too
            response = request(self.client, "GET", path, params=params)
            batch = response.json()
            items.extend(batch)
            total_pages = int(response.headers.get("X-WP-TotalPages", "1") or 1)
            if page >= total_pages or not batch:
                return items
            page += 1

    def fetch_products(self) -> dict[str, ShopProduct]:
        products: dict[str, ShopProduct] = {}
        for p in self._paged("products"):
            if p.get("type") == "variable":
                for v in self._paged(f"products/{p['id']}/variations"):
                    self._add(products, v, f"{p['id']}/{v['id']}", fallback_name=p.get("name"))
            elif p.get("type") in ("simple", None):
                self._add(products, p, str(p["id"]))
        return products

    @staticmethod
    def _add(products: dict, p: dict, ref: str, fallback_name: str | None = None) -> None:
        sku = (p.get("sku") or "").strip()
        if not sku:
            return
        products[sku] = ShopProduct(
            sku=sku, ref=ref, name=p.get("name") or fallback_name,
            stock=None if p.get("stock_quantity") is None else Decimal(str(p["stock_quantity"])),
            price=_price(p.get("regular_price")),
            manage_stock=p.get("manage_stock") is True,
        )

    def apply(self, changes: list[Change]) -> ApplyResult:
        groups: dict[str, list[tuple[Change, dict]]] = defaultdict(list)
        for change in changes:
            payload: dict = {}
            if change.stock_changed:
                payload["stock_quantity"] = int(change.new_stock)
            if change.price_changed:
                payload["regular_price"] = f"{change.new_price:.2f}"
            if not payload:
                continue
            parent, _, variation = change.ref.partition("/")
            payload["id"] = int(variation or parent)
            groups["products/batch" if not variation else f"products/{parent}/variations/batch"].append((change, payload))

        result = ApplyResult()
        for endpoint, entries in groups.items():
            for chunk in chunks(entries, self.batch_size):
                by_id = {payload["id"]: change for change, payload in chunk}
                try:
                    response = request(self.client, "POST", endpoint, json={"update": [p for _, p in chunk]})
                except ApiError as exc:
                    result.failed.extend((c.sku, str(exc)) for c, _ in chunk)
                    continue
                for row in response.json().get("update", []):
                    change = by_id.get(row.get("id"))
                    if change is None:
                        continue
                    if row.get("error"):
                        result.failed.append((change.sku, row["error"].get("message", "error")))
                    else:
                        result.applied += 1
        return result


def _price(raw) -> Decimal | None:
    if raw in (None, ""):
        return None
    try:
        return Decimal(str(raw))
    except InvalidOperation:
        return None
