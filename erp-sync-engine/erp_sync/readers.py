"""Read ERP exports (CSV or XML) into ErpItems, one per SKU.

Rows for the same SKU (for example one row per warehouse) are combined: stock is summed, and the
price must agree between rows, otherwise the row is reported as an error and the SKU is skipped.
"""
from __future__ import annotations

import csv
import io
import xml.etree.ElementTree as ET
from decimal import Decimal
from pathlib import Path

from .config import SyncConfig
from .models import ErpItem, ParseResult, RowError
from .numbers import parse_decimal


def decode(data: bytes, encoding: str = "auto") -> str:
    """Serbian ERP exports are often Windows-1250 (ČĆŽŠĐ); newer ones UTF-8, sometimes with a BOM."""
    if encoding != "auto":
        return data.decode(encoding)
    try:
        return data.decode("utf-8-sig")
    except UnicodeDecodeError:
        return data.decode("cp1250")


def read_export(path: str | Path, cfg: SyncConfig) -> ParseResult:
    data = Path(path).read_bytes()
    if cfg.source.format == "xml":
        records = list(_xml_records(data, cfg))
    else:
        records = list(_csv_records(decode(data, cfg.source.encoding), cfg))
    return _combine(records, cfg)


def _csv_records(text: str, cfg: SyncConfig):
    reader = csv.DictReader(io.StringIO(text, newline=""), delimiter=cfg.source.delimiter)
    headers = [h.strip() for h in (reader.fieldnames or [])]
    reader.fieldnames = headers
    needed = [c for c in _mapped_columns(cfg) if c]
    missing = [c for c in needed if c not in headers]
    if missing:
        raise ValueError(
            f"Columns not found in the export: {', '.join(missing)}. Columns in the file: {', '.join(headers)}"
        )
    for line_no, row in enumerate(reader, start=2):   # line 1 is the header
        if not any((v or "").strip() for v in row.values() if isinstance(v, str)):
            continue
        yield line_no, {k: (v if isinstance(v, str) else None) for k, v in row.items()}


def _xml_records(data: bytes, cfg: SyncConfig):
    if b"<!DOCTYPE" in data[:2048] or b"<!ENTITY" in data[:4096]:
        raise ValueError("XML with DOCTYPE/ENTITY declarations is not accepted")
    root = ET.fromstring(data)
    for index, node in enumerate(root.iterfind(cfg.source.record_path), start=1):
        values = {}
        for column in _mapped_columns(cfg):
            if column:
                values[column] = _xml_value(node, column)
        yield index, values


def _xml_value(node: ET.Element, path: str) -> str | None:
    """'Sifra' = child element text, '@sifra' = attribute, 'Cene/MPC' = nested element."""
    if path.startswith("@"):
        return node.get(path[1:])
    child = node.find(path)
    return child.text if child is not None else None


def _mapped_columns(cfg: SyncConfig) -> list[str | None]:
    c = cfg.columns
    return [c.sku, c.stock if cfg.stock.update else None, c.price if cfg.prices.update else None,
            c.vat_rate, c.name, c.warehouse]


def _combine(records, cfg: SyncConfig) -> ParseResult:
    c, sep = cfg.columns, cfg.source.decimal_separator
    wanted_warehouses = {w.strip().casefold() for w in cfg.filters.warehouses}
    items: dict[str, ErpItem] = {}
    errors: list[RowError] = []
    bad_skus: set[str] = set()
    rows = 0

    for row_no, values in records:
        rows += 1
        sku = (values.get(c.sku) or "").strip()
        if not sku:
            errors.append(RowError(row_no, "empty SKU"))
            continue
        if any(sku.startswith(p) for p in cfg.filters.skip_sku_prefixes):
            continue
        if wanted_warehouses and c.warehouse:
            if (values.get(c.warehouse) or "").strip().casefold() not in wanted_warehouses:
                continue
        try:
            stock = parse_decimal(values.get(c.stock), sep) if cfg.stock.update else None
            price = parse_decimal(values.get(c.price), sep) if cfg.prices.update else None
            vat = parse_decimal(values.get(c.vat_rate), sep) if c.vat_rate else None
        except ValueError as exc:
            errors.append(RowError(row_no, f"SKU {sku}: {exc}"))
            bad_skus.add(sku)
            continue
        if price is not None and price < 0:
            errors.append(RowError(row_no, f"SKU {sku}: negative price {price}"))
            bad_skus.add(sku)
            continue

        item = items.get(sku)
        if item is None:
            name = ((values.get(c.name) or "").strip() or None) if c.name else None
            items[sku] = ErpItem(sku=sku, stock=stock, price=price, vat_rate=vat, name=name, source_rows=[row_no])
            continue

        item.source_rows.append(row_no)
        if stock is not None:
            item.stock = (item.stock or Decimal(0)) + stock
        if price is not None:
            if item.price is not None and item.price != price:
                errors.append(RowError(row_no, f"SKU {sku}: price {price} differs from {item.price} in row {item.source_rows[0]}"))
                bad_skus.add(sku)
            item.price = item.price if item.price is not None else price

    for sku in bad_skus:
        items.pop(sku, None)
    return ParseResult(items=items, errors=errors, rows_read=rows)
