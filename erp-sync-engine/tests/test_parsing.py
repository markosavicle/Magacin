from decimal import Decimal

import pytest

from erp_sync.config import ConfigError, load_config
from erp_sync.numbers import parse_decimal
from erp_sync.readers import decode, read_export
from erp_sync.rules import round_to, shop_price, shop_stock
from erp_sync.models import ErpItem
from tests.conftest import write_config


@pytest.mark.parametrize("raw,sep,expected", [
    ("1.234,56", ",", "1234.56"),
    ("1 234,56", ",", "1234.56"),
    ("1 234,5", ",", "1234.5"),
    ("1.234", ",", "1234"),            # Serbian: thousands separator, not a decimal point
    ("1,234.56", ".", "1234.56"),
    ("(12,00)", ",", "-12.00"),
    ("20%", ",", "20"),
    ("-3", ",", "-3"),
])
def test_parse_decimal(raw, sep, expected):
    assert parse_decimal(raw, sep) == Decimal(expected)


@pytest.mark.parametrize("raw", ["", "   ", None])
def test_parse_decimal_empty(raw):
    assert parse_decimal(raw) is None


@pytest.mark.parametrize("raw", ["abc", "12,3,4x", "NaN", "Infinity"])
def test_parse_decimal_rejects_garbage(raw):
    with pytest.raises(ValueError):
        parse_decimal(raw)


def test_decode_handles_cp1250_and_bom():
    assert decode("Skladište Čačak".encode("cp1250")) == "Skladište Čačak"
    assert decode("﻿Šifra".encode("utf-8")) == "Šifra"


def test_pantheon_csv_combines_warehouses_and_filters(demo):
    cfg = load_config(demo / "pantheon-demo.toml")
    parsed = read_export(demo / "pantheon_zalihe.csv", cfg)
    assert parsed.errors == []
    assert parsed.items["MLK-28-1L"].stock == Decimal("1304")          # 124 + 1.180 across two warehouses
    assert parsed.items["KAFA-500"].stock == Decimal("75")             # consignment warehouse filtered out
    assert "OTPIS-001" not in parsed.items                             # skipped prefix
    assert parsed.items["CAJ-NANA-20"].name == "Čaj nana 20 kesica"    # Windows-1250 decoded
    assert parsed.items["PIVO-05-LIM"].vat_rate == Decimal("20")


def test_calculus_xml_attributes_and_nested_fields(demo):
    cfg = load_config(demo / "calculus-magento.toml")
    parsed = read_export(demo / "calculus_cenovnik.xml", cfg)
    assert len(parsed.items) == 6
    sir = parsed.items["SIR-BELI-1KG"]
    assert (sir.stock, sir.price, sir.vat_rate) == (Decimal("42.5"), Decimal("890.00"), Decimal("10"))


def test_xml_with_doctype_is_rejected(demo, tmp_path):
    cfg = load_config(demo / "calculus-magento.toml")
    evil = tmp_path / "evil.xml"
    evil.write_text('<?xml version="1.0"?><!DOCTYPE x [<!ENTITY a "aaaa">]><Cenovnik/>', encoding="utf-8")
    with pytest.raises(ValueError, match="DOCTYPE"):
        read_export(evil, cfg)


def _csv_config(tmp_path, extra=""):
    return load_config(write_config(tmp_path / "c.toml", f"""
[columns]
sku = "sku"
stock = "qty"
price = "price"
warehouse = "wh"
[target]
type = "jsonfile"
path = "shop.json"
{extra}
"""))


def test_row_errors_are_reported_and_conflicting_skus_skipped(tmp_path):
    export = tmp_path / "e.csv"
    export.write_text("sku;qty;price;wh\nA;1;10,00;X\nA;2;11,00;Y\nB;abc;5;X\n;3;1;X\nC;4;-1;X\nD;5;7,50;X\n", encoding="utf-8")
    parsed = read_export(export, _csv_config(tmp_path))
    messages = " | ".join(e.message for e in parsed.errors)
    assert "differs" in messages and "not a number" in messages and "empty SKU" in messages and "negative" in messages
    assert set(parsed.items) == {"D"}


def test_missing_column_names_the_columns_in_the_file(tmp_path):
    export = tmp_path / "e.csv"
    export.write_text("Šifra;Količina\nA;1\n", encoding="utf-8")
    with pytest.raises(ValueError, match="Columns in the file: Šifra, Količina"):
        read_export(export, _csv_config(tmp_path))


def test_config_rejects_unknown_keys_and_bad_values(tmp_path):
    with pytest.raises(ConfigError, match="unknown keys: tipo"):
        load_config(write_config(tmp_path / "a.toml", '[source]\ntipo = "csv"\n[target]\npath="x"\n'))
    with pytest.raises(ConfigError, match="target.url"):
        load_config(write_config(tmp_path / "b.toml", '[target]\ntype = "woocommerce"\n'))
    with pytest.raises(ConfigError, match="decimal_separator"):
        load_config(write_config(tmp_path / "c.toml", '[source]\ndecimal_separator = ";"\n[target]\npath="x"\n'))


def test_vat_conversion_and_rounding(tmp_path):
    cfg = _csv_config(tmp_path, '[prices]\nsource_includes_vat = false\nround_to = "1"\n')
    assert shop_price(ErpItem("A", price=Decimal("112.50"), vat_rate=Decimal("10")), cfg.prices) == Decimal("124")
    assert shop_price(ErpItem("A", price=Decimal("100")), cfg.prices) == Decimal("120")     # default 20%
    assert round_to(Decimal("1234.56"), Decimal("10")) == Decimal("1230")
    assert round_to(Decimal("1.005"), Decimal("0.01")) == Decimal("1.01")


def test_stock_rules(tmp_path):
    cfg = _csv_config(tmp_path, '[stock]\nsafety_buffer = 2\n')
    assert shop_stock(ErpItem("A", stock=Decimal("42.5")), cfg.stock) == Decimal("40")
    assert shop_stock(ErpItem("A", stock=Decimal("1")), cfg.stock) == Decimal("0")         # never negative
