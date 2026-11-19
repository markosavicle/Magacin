import json
from decimal import Decimal

from erp_sync.cli import main
from erp_sync.config import load_config
from erp_sync.engine import build_plan
from erp_sync.models import ErpItem, ShopProduct


def _cfg(demo, **overrides):
    cfg = load_config(demo / "pantheon-demo.toml")
    for section, values in overrides.items():
        for k, v in values.items():
            setattr(getattr(cfg, section), k, v)
    return cfg


def test_plan_guard_holds_back_big_price_changes_but_keeps_stock(demo):
    cfg = _cfg(demo)
    erp = {"A": ErpItem("A", stock=Decimal("12"), price=Decimal("200"), vat_rate=Decimal("0"))}
    shop = {"A": ShopProduct("A", "A", stock=Decimal("5"), price=Decimal("100"))}
    plan = build_plan(erp, shop, cfg)
    (change,) = plan.changes
    assert change.stock_changed and not change.price_changed
    assert "over the 30" in change.blocked_reason
    assert build_plan(erp, shop, cfg, allow_big_price_changes=True).changes[0].price_changed


def test_plan_matches_skus_case_insensitively_and_zeroes_missing(demo):
    cfg = _cfg(demo, stock={"missing_in_erp": "zero", "safety_buffer": Decimal(0)}, prices={"update": False})
    erp = {" ab-1": ErpItem(" ab-1", stock=Decimal("3"))}
    shop = {"AB-1": ShopProduct("AB-1", "1", stock=Decimal("3"), price=None),
            "OLD": ShopProduct("OLD", "2", stock=Decimal("7"), price=None)}
    plan = build_plan(erp, shop, cfg)
    assert plan.unchanged == 1
    assert [(c.sku, c.new_stock) for c in plan.to_apply] == [("OLD", Decimal(0))]


def test_plan_never_writes_a_zero_price(demo):
    cfg = _cfg(demo)
    plan = build_plan({"A": ErpItem("A", price=Decimal("0"), stock=Decimal("2"))},
                      {"A": ShopProduct("A", "A", stock=Decimal("0"), price=Decimal("99"))}, cfg)
    assert plan.changes[0].blocked_reason == "ERP price is 0"


def test_cli_dry_run_writes_nothing_then_apply_is_idempotent(demo, capsys):
    shop_file = demo / "shop_demo.json"
    before = shop_file.read_text(encoding="utf-8")
    args = ["run", "-c", str(demo / "pantheon-demo.toml"), "-f", str(demo / "pantheon_zalihe.csv")]

    assert main(args) == 4                                 # SOK-JAB-1L price jump is held back
    assert shop_file.read_text(encoding="utf-8") == before
    assert "DRY RUN" in capsys.readouterr().out

    report = demo / "izvestaj.csv"
    assert main(args + ["--apply", "--report", str(report)]) == 4
    products = {p["sku"]: p for p in json.loads(shop_file.read_text(encoding="utf-8"))["products"]}
    assert products["MLK-28-1L"] == {"sku": "MLK-28-1L", "name": "Mleko 2,8% 1l", "stock": 1302, "price": 124.0}
    assert products["SOK-JAB-1L"]["price"] == 129.0       # guarded
    assert products["SOK-JAB-1L"]["stock"] == 286          # stock still synced
    assert products["POKLON-PAKET"]["stock"] == 15         # shop-only product untouched
    lines = report.read_text(encoding="utf-8-sig").splitlines()
    assert lines[0].startswith("sku;name;status")
    assert any(line.startswith("CAJ-NANA-20;;not-in-shop") for line in lines)

    capsys.readouterr()
    main(args)
    assert "0 products to update" in capsys.readouterr().out


def test_cli_check_and_config_errors(demo, capsys, tmp_path):
    assert main(["check", "-c", str(demo / "pantheon-demo.toml"), "-f", str(demo / "pantheon_zalihe.csv")]) == 0
    assert main(["check", "-c", str(tmp_path / "missing.toml"), "-f", "x.csv"]) == 1
    assert "Config file not found" in capsys.readouterr().err


def test_cli_requires_credentials_from_env(demo, monkeypatch, capsys):
    monkeypatch.delenv("WOO_CONSUMER_KEY", raising=False)
    code = main(["run", "-c", str(demo / "pantheon-woocommerce.toml"), "-f", str(demo / "pantheon_zalihe.csv")])
    assert code == 1
    assert "WOO_CONSUMER_KEY" in capsys.readouterr().err


def test_navision_example_parses_and_plans(demo, capsys):
    args = ["run", "-c", str(demo / "navision-demo.toml"), "-f", str(demo / "navision_artikli.csv")]
    assert main(args) == 0
    out = capsys.readouterr().out
    assert "7 rows, 5 SKUs, 0 rows with errors" in out
    assert "980 -> 1302" in out             # 1.180 + 124 across two locations, minus the 2-unit buffer
