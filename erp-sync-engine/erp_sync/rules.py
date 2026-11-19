"""Turn an ERP value into what the shop should show: VAT, rounding, safety stock."""
from __future__ import annotations

from decimal import ROUND_FLOOR, ROUND_HALF_UP, Decimal

from .config import PriceRules, StockRules
from .models import ErpItem


def shop_price(item: ErpItem, rules: PriceRules) -> Decimal | None:
    if item.price is None:
        return None
    price = item.price
    vat = item.vat_rate if item.vat_rate is not None else rules.default_vat_rate
    factor = 1 + vat / Decimal(100)
    if rules.target_includes_vat and not rules.source_includes_vat:
        price = price * factor
    elif rules.source_includes_vat and not rules.target_includes_vat:
        price = price / factor
    return round_to(price, rules.round_to)


def round_to(value: Decimal, step: Decimal) -> Decimal:
    """round_to(1234.56, 1) = 1235, round_to(1234.56, 10) = 1230, round_to(1.005, 0.01) = 1.01"""
    rounded = (value / step).quantize(Decimal(1), rounding=ROUND_HALF_UP) * step
    return rounded.quantize(step) if step < 1 else rounded.quantize(Decimal(1))


def shop_stock(item: ErpItem, rules: StockRules) -> Decimal | None:
    if item.stock is None:
        return None
    stock = item.stock - rules.safety_buffer
    if rules.whole_units:
        stock = stock.to_integral_value(rounding=ROUND_FLOOR)
    return max(stock, Decimal(0))


def price_change_percent(old: Decimal | None, new: Decimal) -> Decimal | None:
    if old is None or old == 0:
        return None
    return abs(new - old) / old * 100
