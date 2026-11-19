from __future__ import annotations

from dataclasses import dataclass, field
from decimal import Decimal


@dataclass
class ErpItem:
    """One SKU as the ERP export describes it, after rows for several warehouses are combined."""

    sku: str
    stock: Decimal | None = None
    price: Decimal | None = None          # as exported, before VAT conversion and rounding
    vat_rate: Decimal | None = None       # percent, e.g. 20 or 10
    name: str | None = None
    source_rows: list[int] = field(default_factory=list)


@dataclass
class RowError:
    row: int
    message: str


@dataclass
class ParseResult:
    items: dict[str, ErpItem]
    errors: list[RowError]
    rows_read: int


@dataclass
class ShopProduct:
    """A product or variation in the webshop, addressed by SKU."""

    sku: str
    ref: str                              # target-specific id: Woo "123" or "45/123" (parent/variation), Magento SKU
    stock: Decimal | None
    price: Decimal | None
    name: str | None = None
    manage_stock: bool = True


@dataclass
class Change:
    sku: str
    ref: str
    name: str | None
    old_stock: Decimal | None
    new_stock: Decimal | None
    old_price: Decimal | None
    new_price: Decimal | None
    blocked_reason: str | None = None     # set when a guard stops the price update

    @property
    def stock_changed(self) -> bool:
        return self.new_stock is not None and self.new_stock != self.old_stock

    @property
    def price_changed(self) -> bool:
        return self.new_price is not None and self.new_price != self.old_price and self.blocked_reason is None
