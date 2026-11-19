"""WooCommerce and Magento adapters against a fake API (httpx.MockTransport): no network."""
import json
from decimal import Decimal

import httpx

from erp_sync.models import Change
from erp_sync.targets.magento import MagentoTarget
from erp_sync.targets.woocommerce import WooCommerceTarget


def _change(sku, ref, stock=None, price=None):
    return Change(sku=sku, ref=ref, name=None, old_stock=Decimal(0), new_stock=stock,
                  old_price=Decimal(1), new_price=price)


class FakeWoo:
    def __init__(self):
        self.calls = []

    def __call__(self, request: httpx.Request) -> httpx.Response:
        self.calls.append(request)
        path, page = request.url.path, request.url.params.get("page")
        assert request.headers["authorization"].startswith("Basic ")
        if request.method == "GET" and path.endswith("/products"):
            if page == "1":
                return httpx.Response(200, headers={"X-WP-TotalPages": "2"}, json=[
                    {"id": 10, "sku": "MLK-28-1L", "name": "Mleko", "type": "simple", "manage_stock": True, "stock_quantity": 5, "regular_price": "129"},
                    {"id": 11, "sku": "", "name": "No SKU", "type": "simple", "manage_stock": True, "stock_quantity": 1, "regular_price": "1"},
                ])
            return httpx.Response(200, headers={"X-WP-TotalPages": "2"}, json=[
                {"id": 20, "sku": "MAJICA", "name": "Majica", "type": "variable", "manage_stock": False, "stock_quantity": None, "regular_price": ""},
            ])
        if request.method == "GET" and path.endswith("/products/20/variations"):
            return httpx.Response(200, headers={"X-WP-TotalPages": "1"}, json=[
                {"id": 21, "sku": "MAJICA-M", "name": "", "manage_stock": True, "stock_quantity": 3, "regular_price": "1990.00"},
            ])
        if request.method == "POST" and path.endswith("/products/batch"):
            body = json.loads(request.content)
            return httpx.Response(200, json={"update": [{"id": u["id"]} for u in body["update"]]})
        if request.method == "POST" and path.endswith("/products/20/variations/batch"):
            body = json.loads(request.content)
            return httpx.Response(200, json={"update": [
                {"id": u["id"], "error": {"code": "woocommerce_rest_invalid", "message": "Invalid price"}} for u in body["update"]]})
        return httpx.Response(404, json={"message": f"unexpected {request.method} {path}"})


def test_woocommerce_fetch_pages_and_variations():
    woo = WooCommerceTarget("https://shop.example.rs/", "ck", "cs", transport=httpx.MockTransport(FakeWoo()))
    products = woo.fetch_products()
    assert set(products) == {"MLK-28-1L", "MAJICA-M"}
    assert products["MAJICA-M"].ref == "20/21"
    assert products["MAJICA-M"].name == "Majica"
    assert products["MLK-28-1L"].price == Decimal("129")


def test_woocommerce_apply_batches_and_reports_item_errors():
    fake = FakeWoo()
    woo = WooCommerceTarget("https://shop.example.rs", "ck", "cs", transport=httpx.MockTransport(fake))
    result = woo.apply([
        _change("MLK-28-1L", "10", stock=Decimal(1302), price=Decimal(124)),
        _change("MAJICA-M", "20/21", price=Decimal("2190")),
    ])
    assert result.applied == 1
    assert result.failed == [("MAJICA-M", "Invalid price")]
    batch = next(c for c in fake.calls if c.url.path.endswith("/products/batch"))
    assert json.loads(batch.content) == {"update": [{"stock_quantity": 1302, "regular_price": "124.00", "id": 10}]}


def test_woocommerce_retries_rate_limit(monkeypatch):
    monkeypatch.setattr("erp_sync.targets.http.time.sleep", lambda s: None)
    calls = {"n": 0}

    def handler(request):
        calls["n"] += 1
        if calls["n"] == 1:
            return httpx.Response(429, headers={"Retry-After": "1"})
        return httpx.Response(200, headers={"X-WP-TotalPages": "1"}, json=[])

    woo = WooCommerceTarget("https://shop.example.rs", "ck", "cs", transport=httpx.MockTransport(handler))
    assert woo.fetch_products() == {}
    assert calls["n"] == 2


class FakeMagento:
    def __init__(self):
        self.calls = []

    def __call__(self, request: httpx.Request) -> httpx.Response:
        self.calls.append(request)
        assert request.headers["authorization"] == "Bearer tok"
        path = request.url.path
        if request.method == "GET" and path.endswith("/V1/products"):
            return httpx.Response(200, json={"total_count": 3, "items": [
                {"sku": "MLK-28-1L", "name": "Mleko", "price": 129, "type_id": "simple",
                 "extension_attributes": {"stock_item": {"item_id": 7, "qty": 5, "manage_stock": True}}},
                {"sku": "KAFA-500", "name": "Kafa", "price": 980, "type_id": "simple",
                 "extension_attributes": {"stock_item": {"item_id": 8, "qty": 90, "manage_stock": True}}},
                {"sku": "MAJICA", "name": "Majica", "price": 0, "type_id": "configurable"},
            ]})
        if request.method == "GET" and path.endswith("/V1/inventory/source-items"):
            assert request.url.params["searchCriteria[filter_groups][0][filters][0][value]"] == "nis"
            return httpx.Response(200, json={"total_count": 1, "items": [{"sku": "MLK-28-1L", "source_code": "nis", "quantity": 11}]})
        if request.method == "POST" and path.endswith("/V1/products/base-prices"):
            return httpx.Response(200, json=[{"message": "Invalid attribute %fieldName = %fieldValue.", "parameters": ["KAFA-500"]}])
        if request.method == "POST" and path.endswith("/V1/inventory/source-items"):
            return httpx.Response(200, json=[])
        if request.method == "PUT" and "/stockItems/" in path:
            return httpx.Response(200, json=7)
        return httpx.Response(404, json={"message": "unexpected"})


def test_magento_msi_fetch_and_apply():
    fake = FakeMagento()
    mage = MagentoTarget("https://m.example.rs", "tok", inventory="msi", source_code="nis", transport=httpx.MockTransport(fake))
    products = mage.fetch_products()
    assert set(products) == {"MLK-28-1L", "KAFA-500"}                   # configurable parent skipped
    assert products["MLK-28-1L"].stock == Decimal("11")                 # from the MSI source, not legacy qty
    assert fake.calls[0].url.path == "/rest/all/V1/products"

    result = mage.apply([
        _change("MLK-28-1L", "MLK-28-1L", stock=Decimal(3), price=Decimal(124)),
        _change("KAFA-500", "KAFA-500", price=Decimal(990)),
    ])
    assert result.applied == 1
    assert result.failed == [("KAFA-500", "Invalid attribute %fieldName = %fieldValue.")]
    source_post = next(c for c in fake.calls if c.method == "POST" and c.url.path.endswith("/source-items"))
    assert json.loads(source_post.content) == {"sourceItems": [{"sku": "MLK-28-1L", "source_code": "nis", "quantity": 3.0, "status": 1}]}


def test_magento_legacy_stock_uses_stock_item_id():
    fake = FakeMagento()
    mage = MagentoTarget("https://m.example.rs", "tok", inventory="legacy", transport=httpx.MockTransport(fake))
    products = mage.fetch_products()
    assert products["MLK-28-1L"].stock == Decimal("5")
    result = mage.apply([_change("MLK-28-1L", "MLK-28-1L", stock=Decimal(40))])
    assert result.applied == 1 and result.failed == []
    put = next(c for c in fake.calls if c.method == "PUT")
    assert put.url.path == "/rest/all/V1/products/MLK-28-1L/stockItems/7"
    assert json.loads(put.content) == {"stockItem": {"qty": 40.0, "is_in_stock": True}}
