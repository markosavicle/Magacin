"""Human summary on the terminal, and a CSV or JSON report file for the client."""
from __future__ import annotations

import csv
import json
from decimal import Decimal
from pathlib import Path

from .engine import Plan
from .models import ParseResult
from .targets.base import ApplyResult


def _fmt(value: Decimal | None) -> str:
    if value is None:
        return ""
    return f"{value.normalize():f}" if value == value.to_integral_value() else f"{value:.2f}"


def summary(parsed: ParseResult, plan: Plan, applied: ApplyResult | None, dry_run: bool, max_lines: int = 25) -> str:
    to_apply = plan.to_apply
    out = [
        f"Export: {parsed.rows_read} rows, {len(parsed.items)} SKUs, {len(parsed.errors)} rows with errors",
        f"Shop:   {len(to_apply)} products to update, {plan.unchanged} already in sync, "
        f"{len(plan.not_in_shop)} ERP SKUs not in the shop, {len(plan.missing_in_erp)} shop SKUs not in the export",
    ]
    if plan.blocked:
        out.append(f"Guard:  {len(plan.blocked)} price changes held back (stock is still updated); see the report")
    if to_apply:
        out.append("")
        out.append(f"{'SKU':<18} {'stock':>15} {'price':>23}  name")
        for c in to_apply[:max_lines]:
            stock = f"{_fmt(c.old_stock)} -> {_fmt(c.new_stock)}" if c.stock_changed else ""
            price = f"{_fmt(c.old_price)} -> {_fmt(c.new_price)}" if c.price_changed else ""
            out.append(f"{c.sku:<18} {stock:>15} {price:>23}  {(c.name or '')[:40]}")
        if len(to_apply) > max_lines:
            out.append(f"... and {len(to_apply) - max_lines} more (full list in the report)")
    for err in parsed.errors[:10]:
        out.append(f"Row {err.row}: {err.message}")
    out.append("")
    if dry_run:
        out.append("DRY RUN: nothing was written. Run again with --apply to update the shop.")
    elif applied is not None:
        out.append(f"Applied: {applied.applied} updated, {len(applied.failed)} failed")
        for sku, error in applied.failed[:10]:
            out.append(f"  {sku}: {error}")
    return "\n".join(out)


def write_report(path: str | Path, parsed: ParseResult, plan: Plan, applied: ApplyResult | None, dry_run: bool) -> None:
    path = Path(path)
    failed = dict(applied.failed) if applied else {}
    rows = []
    for c in plan.changes:
        status = "blocked" if c.blocked_reason and not c.stock_changed else (
            "dry-run" if dry_run else ("failed" if c.sku in failed else "updated"))
        rows.append({
            "sku": c.sku, "name": c.name or "", "status": status,
            "old_stock": _fmt(c.old_stock), "new_stock": _fmt(c.new_stock) if c.stock_changed else "",
            "old_price": _fmt(c.old_price), "new_price": _fmt(c.new_price) if c.price_changed else "",
            "note": c.blocked_reason or failed.get(c.sku, ""),
        })
    rows += [{"sku": s, "name": "", "status": "not-in-shop", "old_stock": "", "new_stock": "",
              "old_price": "", "new_price": "", "note": ""} for s in plan.not_in_shop]
    rows += [{"sku": f"row {e.row}", "name": "", "status": "export-error", "old_stock": "", "new_stock": "",
              "old_price": "", "new_price": "", "note": e.message} for e in parsed.errors]

    if path.suffix.lower() == ".json":
        path.write_text(json.dumps({
            "dry_run": dry_run, "rows_read": parsed.rows_read, "skus": len(parsed.items),
            "missing_in_erp": plan.missing_in_erp, "rows": rows,
        }, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        return
    # utf-8-sig and ';' so the file opens correctly in Excel with Serbian regional settings
    with path.open("w", newline="", encoding="utf-8-sig") as fh:
        writer = csv.DictWriter(fh, fieldnames=list(rows[0].keys()) if rows else ["sku"], delimiter=";")
        writer.writeheader()
        writer.writerows(rows)
