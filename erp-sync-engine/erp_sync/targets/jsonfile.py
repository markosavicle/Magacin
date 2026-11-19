"""A webshop stand-in kept in a JSON file: for demos, tests and trying a mapping without a real shop."""
from __future__ import annotations

import json
from decimal import Decimal
from pathlib import Path

from ..models import Change, ShopProduct
from .base import ApplyResult, Target


class JsonFileTarget(Target):
    name = "jsonfile"

    def __init__(self, path: Path):
        self.path = Path(path)

    def _load(self) -> dict:
        return json.loads(self.path.read_text(encoding="utf-8"))

    def fetch_products(self) -> dict[str, ShopProduct]:
        products = {}
        for p in self._load()["products"]:
            if not p.get("sku"):
                continue
            products[p["sku"]] = ShopProduct(
                sku=p["sku"], ref=p["sku"], name=p.get("name"),
                stock=None if p.get("stock") is None else Decimal(str(p["stock"])),
                price=None if p.get("price") is None else Decimal(str(p["price"])),
                manage_stock=p.get("manage_stock", True),
            )
        return products

    def apply(self, changes: list[Change]) -> ApplyResult:
        data = self._load()
        by_sku = {p.get("sku"): p for p in data["products"]}
        result = ApplyResult()
        for change in changes:
            product = by_sku.get(change.ref)
            if product is None:
                result.failed.append((change.sku, "not in the file any more"))
                continue
            if change.stock_changed:
                product["stock"] = int(change.new_stock) if change.new_stock == change.new_stock.to_integral_value() else float(change.new_stock)
            if change.price_changed:
                product["price"] = float(change.new_price)
            result.applied += 1
        tmp = self.path.with_suffix(".tmp")
        tmp.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        tmp.replace(self.path)
        return result
