"""Compare the ERP export with the shop and decide what to change."""
from __future__ import annotations

from dataclasses import dataclass, field
from decimal import Decimal

from .config import SyncConfig
from .models import Change, ErpItem, ShopProduct
from .rules import price_change_percent, shop_price, shop_stock


@dataclass
class Plan:
    changes: list[Change] = field(default_factory=list)
    unchanged: int = 0
    not_in_shop: list[str] = field(default_factory=list)        # in the ERP export, no product with that SKU
    missing_in_erp: list[str] = field(default_factory=list)     # in the shop, absent from the export

    @property
    def to_apply(self) -> list[Change]:
        return [c for c in self.changes if c.stock_changed or c.price_changed]

    @property
    def blocked(self) -> list[Change]:
        return [c for c in self.changes if c.blocked_reason]


def build_plan(
    erp: dict[str, ErpItem],
    shop: dict[str, ShopProduct],
    cfg: SyncConfig,
    allow_big_price_changes: bool = False,
) -> Plan:
    plan = Plan()
    shop_by_key = {_key(sku): p for sku, p in shop.items()}

    for sku, item in sorted(erp.items()):
        product = shop_by_key.get(_key(sku))
        if product is None:
            plan.not_in_shop.append(sku)
            continue

        new_stock = shop_stock(item, cfg.stock) if cfg.stock.update and product.manage_stock else None
        new_price = shop_price(item, cfg.prices) if cfg.prices.update else None

        change = Change(
            sku=product.sku, ref=product.ref, name=product.name or item.name,
            old_stock=product.stock, new_stock=new_stock if new_stock != product.stock else None,
            old_price=product.price, new_price=new_price if new_price != product.price else None,
        )
        if change.new_price is not None:
            if new_price == 0:
                change.blocked_reason = "ERP price is 0"
            else:
                pct = price_change_percent(product.price, new_price)
                limit = cfg.prices.max_change_percent
                if pct is not None and limit is not None and pct > limit and not allow_big_price_changes:
                    change.blocked_reason = f"price change {pct:.1f}% is over the {limit}% limit"

        if change.stock_changed or change.price_changed or change.blocked_reason:
            plan.changes.append(change)
        else:
            plan.unchanged += 1

    erp_keys = {_key(s) for s in erp}
    for sku, product in sorted(shop.items()):
        if _key(sku) in erp_keys:
            continue
        plan.missing_in_erp.append(sku)
        if cfg.stock.update and cfg.stock.missing_in_erp == "zero" and product.manage_stock and product.stock not in (None, Decimal(0)):
            plan.changes.append(Change(
                sku=sku, ref=product.ref, name=product.name,
                old_stock=product.stock, new_stock=Decimal(0), old_price=product.price, new_price=None,
            ))
    return plan


def _key(sku: str) -> str:
    """SKUs match case-insensitively and ignoring surrounding spaces: ' ab-1' in the ERP is 'AB-1' in the shop."""
    return sku.strip().casefold()
