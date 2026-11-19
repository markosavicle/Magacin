"""Sync configuration, read from a TOML file. Credentials are never read from the file, only from env vars."""
from __future__ import annotations

import tomllib
from dataclasses import dataclass, field
from decimal import Decimal, InvalidOperation
from pathlib import Path


class ConfigError(ValueError):
    pass


@dataclass
class SourceConfig:
    format: str = "csv"                  # csv | xml
    encoding: str = "auto"               # auto: UTF-8 (with or without BOM), else Windows-1250
    delimiter: str = ";"
    decimal_separator: str = ","         # "," for 1.234,56   "." for 1,234.56
    record_path: str = ".//item"         # xml only: ElementPath to one record


@dataclass
class Columns:
    sku: str = "sku"
    stock: str | None = "stock"
    price: str | None = "price"
    vat_rate: str | None = None
    name: str | None = None
    warehouse: str | None = None


@dataclass
class Filters:
    warehouses: list[str] = field(default_factory=list)   # empty = all rows
    skip_sku_prefixes: list[str] = field(default_factory=list)


@dataclass
class PriceRules:
    update: bool = True
    source_includes_vat: bool = True
    target_includes_vat: bool = True
    default_vat_rate: Decimal = Decimal("20")
    round_to: Decimal = Decimal("0.01")
    max_change_percent: Decimal | None = Decimal("30")


@dataclass
class StockRules:
    update: bool = True
    safety_buffer: Decimal = Decimal("0")      # subtracted from ERP stock before publishing
    whole_units: bool = True                   # round stock down: 2,5 in the ERP is 2 sellable units
    missing_in_erp: str = "ignore"             # ignore | zero  (shop SKUs absent from the export)


@dataclass
class TargetConfig:
    type: str = "jsonfile"                     # woocommerce | magento | jsonfile
    url: str | None = None
    path: str | None = None                    # jsonfile
    batch_size: int = 50
    timeout: float = 30.0
    inventory: str = "msi"                     # magento: msi | legacy
    source_code: str = "default"               # magento MSI source
    store_code: str = "all"                    # magento REST store code in the URL


@dataclass
class SyncConfig:
    source: SourceConfig
    columns: Columns
    filters: Filters
    prices: PriceRules
    stock: StockRules
    target: TargetConfig
    base_dir: Path = Path(".")


def _decimal(value, key: str) -> Decimal:
    try:
        return Decimal(str(value))
    except InvalidOperation as exc:
        raise ConfigError(f"{key}: '{value}' is not a number") from exc


def _section(data: dict, name: str, cls, decimals: tuple[str, ...] = ()):
    raw = dict(data.get(name, {}))
    allowed = set(cls.__dataclass_fields__)
    unknown = set(raw) - allowed
    if unknown:
        raise ConfigError(f"[{name}] unknown keys: {', '.join(sorted(unknown))}")
    for key in decimals:
        if key in raw and raw[key] is not None:
            raw[key] = _decimal(raw[key], f"{name}.{key}")
    return cls(**raw)


def load_config(path: str | Path) -> SyncConfig:
    path = Path(path)
    try:
        data = tomllib.loads(path.read_text(encoding="utf-8"))
    except FileNotFoundError as exc:
        raise ConfigError(f"Config file not found: {path}") from exc
    except tomllib.TOMLDecodeError as exc:
        raise ConfigError(f"{path}: {exc}") from exc

    unknown = set(data) - {"source", "columns", "filters", "prices", "stock", "target"}
    if unknown:
        raise ConfigError(f"Unknown sections: {', '.join(sorted(unknown))}")

    cfg = SyncConfig(
        source=_section(data, "source", SourceConfig),
        columns=_section(data, "columns", Columns),
        filters=_section(data, "filters", Filters),
        prices=_section(data, "prices", PriceRules, ("default_vat_rate", "round_to", "max_change_percent")),
        stock=_section(data, "stock", StockRules, ("safety_buffer",)),
        target=_section(data, "target", TargetConfig),
        base_dir=path.parent,
    )
    validate(cfg)
    return cfg


def validate(cfg: SyncConfig) -> None:
    if cfg.source.format not in ("csv", "xml"):
        raise ConfigError("source.format must be 'csv' or 'xml'")
    if cfg.source.decimal_separator not in (",", "."):
        raise ConfigError("source.decimal_separator must be ',' or '.'")
    if not cfg.columns.sku:
        raise ConfigError("columns.sku is required")
    if cfg.stock.update and not cfg.columns.stock:
        raise ConfigError("columns.stock is required when stock.update = true")
    if cfg.prices.update and not cfg.columns.price:
        raise ConfigError("columns.price is required when prices.update = true")
    if cfg.prices.round_to <= 0:
        raise ConfigError("prices.round_to must be positive, e.g. 0.01 or 1")
    if cfg.stock.missing_in_erp not in ("ignore", "zero"):
        raise ConfigError("stock.missing_in_erp must be 'ignore' or 'zero'")
    if cfg.target.type not in ("woocommerce", "magento", "jsonfile"):
        raise ConfigError("target.type must be woocommerce, magento or jsonfile")
    if cfg.target.type in ("woocommerce", "magento") and not cfg.target.url:
        raise ConfigError(f"target.url is required for {cfg.target.type}")
    if cfg.target.type == "jsonfile" and not cfg.target.path:
        raise ConfigError("target.path is required for jsonfile")
    if cfg.target.inventory not in ("msi", "legacy"):
        raise ConfigError("target.inventory must be 'msi' or 'legacy'")
    if not 1 <= cfg.target.batch_size <= 100:
        raise ConfigError("target.batch_size must be between 1 and 100")
