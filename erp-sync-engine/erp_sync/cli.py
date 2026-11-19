"""erp-sync: sync stock and prices from an ERP export to a webshop. Dry run unless --apply is given.

  erp-sync check --config sync.toml --file export.csv        parse the export only, no shop calls
  erp-sync run   --config sync.toml --file export.csv        compare with the shop and show the plan
  erp-sync run   --config sync.toml --file export.csv --apply --report izvestaj.csv

Exit codes: 0 ok, 1 config or connection error, 2 rows with errors in the export,
3 some updates failed, 4 price changes held back by the guard.
"""
from __future__ import annotations

import argparse
import logging
import sys

from . import __version__
from .config import ConfigError, load_config
from .engine import build_plan
from .readers import read_export
from .report import summary, write_report
from .targets import make_target
from .targets.http import ApiError


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="erp-sync", description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--version", action="version", version=f"erp-sync {__version__}")
    sub = parser.add_subparsers(dest="command", required=True)

    for name in ("check", "run"):
        p = sub.add_parser(name)
        p.add_argument("--config", "-c", required=True, help="TOML mapping file")
        p.add_argument("--file", "-f", required=True, help="ERP export (CSV or XML)")
        p.add_argument("--report", "-r", help="write a .csv or .json report")
        p.add_argument("-v", "--verbose", action="store_true")
        if name == "run":
            p.add_argument("--apply", action="store_true", help="write the changes (default is a dry run)")
            p.add_argument("--allow-big-price-changes", action="store_true",
                           help="apply price changes over prices.max_change_percent")

    args = parser.parse_args(argv)
    logging.basicConfig(level=logging.DEBUG if args.verbose else logging.WARNING, format="%(levelname)s %(message)s")

    try:
        cfg = load_config(args.config)
        parsed = read_export(args.file, cfg)
    except (ConfigError, ValueError, OSError) as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1

    if args.command == "check":
        print(f"{parsed.rows_read} rows, {len(parsed.items)} SKUs, {len(parsed.errors)} rows with errors")
        for err in parsed.errors:
            print(f"Row {err.row}: {err.message}")
        for item in list(parsed.items.values())[:10]:
            print(f"  {item.sku}: stock={item.stock} price={item.price} vat={item.vat_rate} {item.name or ''}")
        return 2 if parsed.errors else 0

    dry_run = not args.apply
    try:
        target = make_target(cfg)
    except ConfigError as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1
    applied = None
    try:
        shop = target.fetch_products()
        plan = build_plan(parsed.items, shop, cfg, allow_big_price_changes=args.allow_big_price_changes)
        if not dry_run and plan.to_apply:
            applied = target.apply(plan.to_apply)
    except ApiError as exc:
        print(f"Error talking to {target.name}: {exc}", file=sys.stderr)
        return 1
    finally:
        target.close()

    print(summary(parsed, plan, applied, dry_run))
    if args.report:
        write_report(args.report, parsed, plan, applied, dry_run)
        print(f"Report: {args.report}")

    if applied and applied.failed:
        return 3
    if parsed.errors:
        return 2
    if plan.blocked:
        return 4
    return 0


if __name__ == "__main__":
    sys.exit(main())
