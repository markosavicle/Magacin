from __future__ import annotations

from abc import ABC, abstractmethod
from dataclasses import dataclass, field

from ..models import Change, ShopProduct


@dataclass
class ApplyResult:
    applied: int = 0
    failed: list[tuple[str, str]] = field(default_factory=list)   # (sku, error)


class Target(ABC):
    name = "target"

    @abstractmethod
    def fetch_products(self) -> dict[str, ShopProduct]:
        """Every product and variation that has a SKU, keyed by SKU."""

    @abstractmethod
    def apply(self, changes: list[Change]) -> ApplyResult:
        """Write stock and price changes. Changes with blocked_reason only get their stock written."""

    def close(self) -> None:
        pass
