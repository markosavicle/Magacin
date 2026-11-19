from __future__ import annotations

import os

from ..config import ConfigError, SyncConfig
from .base import Target


def make_target(cfg: SyncConfig, transport=None) -> Target:
    t = cfg.target
    if t.type == "jsonfile":
        from .jsonfile import JsonFileTarget
        return JsonFileTarget(cfg.base_dir / t.path)
    if t.type == "woocommerce":
        from .woocommerce import WooCommerceTarget
        return WooCommerceTarget(
            t.url, _env("WOO_CONSUMER_KEY"), _env("WOO_CONSUMER_SECRET"),
            batch_size=t.batch_size, timeout=t.timeout, transport=transport,
        )
    from .magento import MagentoTarget
    return MagentoTarget(
        t.url, _env("MAGENTO_TOKEN"), inventory=t.inventory, source_code=t.source_code,
        store_code=t.store_code, batch_size=t.batch_size, timeout=t.timeout, transport=transport,
    )


def _env(name: str) -> str:
    value = os.environ.get(name, "")
    if not value:
        raise ConfigError(f"Set {name} in the environment (credentials are never read from the config file).")
    return value


__all__ = ["Target", "make_target"]
