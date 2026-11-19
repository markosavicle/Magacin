# erp-sync-engine

A small command-line tool that reads a stock or price list export from an ERP (CSV or XML) and brings a webshop in line with it: stock quantities and prices, for WooCommerce or Magento 2. It is built for the way Serbian ERP exports usually look: Windows-1250 or UTF-8, `;` as the separator, `1.234,56` numbers, one row per warehouse, prices with or without VAT.

It is a starting point for an integration, not a finished connector for any particular ERP. Every export is a little different, so the columns, warehouses, VAT handling and rounding are set in a TOML file per client, and a new client is usually a new mapping file plus a dry run against their real export.

## What it does

1. **Reads the export.** CSV (encoding detected: UTF-8 with or without BOM, else Windows-1250) or XML (elements, nested elements, attributes). Rows for the same SKU across warehouses are summed; only the warehouses you list count.
2. **Applies the business rules.** Converts between prices with and without VAT (per-row VAT rate or a default), rounds to 0.01, whole dinars or tens, keeps a safety stock back, rounds stock down to whole units.
3. **Compares with the shop** and changes only what differs. SKUs match ignoring case and spaces.
4. **Guards against bad data.** Dry run unless you pass `--apply`. A price change over `max_change_percent` (default 30%) or a price of 0 is held back and reported, while that product's stock is still updated. Rows that do not parse, and SKUs with two different prices in one export, are reported and skipped instead of guessed.
5. **Reports.** A summary on the terminal and an optional CSV (opens in Excel with Serbian settings) or JSON report of every change, held-back price, unknown SKU and bad row.

Running it twice with the same export changes nothing the second time.

## Try it in a minute (no shop needed)

```bash
cd erp-sync-engine
python3 -m venv .venv && . .venv/bin/activate      # Windows: .venv\Scripts\activate
pip install -e '.[dev]'

erp-sync check -c examples/pantheon-demo.toml -f examples/pantheon_zalihe.csv   # parse only
erp-sync run   -c examples/pantheon-demo.toml -f examples/pantheon_zalihe.csv   # dry run: the plan
erp-sync run   -c examples/pantheon-demo.toml -f examples/pantheon_zalihe.csv --apply -r izvestaj.csv
git checkout examples/shop_demo.json               # reset the demo shop
```

The demo shop is `examples/shop_demo.json` (target type `jsonfile`), so the whole flow works offline. The export is a fictional Niš food and drinks wholesaler with two own warehouses and one consignment warehouse, whose stock is excluded. One product's price jumps 47%, so you can see the guard hold it back.

```
Export: 13 rows, 10 SKUs, 0 rows with errors
Shop:   9 products to update, 0 already in sync, 1 ERP SKUs not in the shop, 1 shop SKUs not in the export
Guard:  1 price changes held back (stock is still updated); see the report

SKU                          stock                   price  name
JOG-15-15L                36 -> 34              215 -> 197  Jogurt 1,5l
MLK-28-1L              980 -> 1302              129 -> 124  Mleko 2,8% 1l
...
DRY RUN: nothing was written. Run again with --apply to update the shop.
```

## Connecting a real shop

Credentials are read only from environment variables, never from the config file.

| Target | Config | Credentials | API calls |
|---|---|---|---|
| WooCommerce | `type = "woocommerce"`, `url` | `WOO_CONSUMER_KEY`, `WOO_CONSUMER_SECRET` (REST key with Read/Write) | `GET /wc/v3/products` (+ variations), `POST /products/batch`, `POST /products/{id}/variations/batch` |
| Magento 2 | `type = "magento"`, `url`, `inventory = "msi"` or `"legacy"`, `source_code` | `MAGENTO_TOKEN` (integration access token) | `GET /V1/products`, `GET /V1/inventory/source-items`, `POST /V1/products/base-prices`, `POST /V1/inventory/source-items` or `PUT /V1/products/{sku}/stockItems/{id}` |

Updates go in batches (`batch_size`, default 50). Rate limits (429) and gateway errors are retried with backoff. WooCommerce gets `regular_price`; sale prices are left alone. Magento configurable, bundle and grouped parents are skipped, their simple children are synced.

Example mappings: [`examples/pantheon-woocommerce.toml`](examples/pantheon-woocommerce.toml) (CSV, wholesale prices without VAT, two warehouses) and [`examples/calculus-magento.toml`](examples/calculus-magento.toml) (XML, retail prices with VAT, MSI). The column names follow typical Pantheon and Calculus reports but are illustrative: check them against the client's actual export.

## Config reference

```toml
[source]
format = "csv"               # csv | xml
encoding = "auto"            # or "cp1250", "utf-8"
delimiter = ";"
decimal_separator = ","      # "," for 1.234,56, "." for 1,234.56
record_path = ".//Artikal"   # xml: one record

[columns]                    # csv: header names; xml: child element, "Cena/MPC", or "@attribute"
sku = "Ident"
stock = "Zaliha"
price = "VP cena"
vat_rate = "Stopa PDV"       # optional, percent
name = "Naziv"               # optional
warehouse = "Skladište"      # optional, used by filters.warehouses

[filters]
warehouses = ["MP Niš"]      # empty = every row
skip_sku_prefixes = ["OTPIS-"]

[prices]
update = true
source_includes_vat = false
target_includes_vat = true
default_vat_rate = 20
round_to = "1"               # "0.01", "1", "10"
max_change_percent = 30      # bigger changes need --allow-big-price-changes

[stock]
update = true
safety_buffer = 0
whole_units = true
missing_in_erp = "ignore"    # or "zero": set shop stock to 0 for SKUs the export no longer lists

[target]
type = "woocommerce"         # woocommerce | magento | jsonfile
url = "https://shop.example.rs"
batch_size = 50
```

Exit codes, for cron and alerting: `0` ok, `1` config or connection error, `2` rows with errors in the export, `3` some updates failed, `4` price changes held back.

## Running it on a schedule

```bash
# every 15 minutes, after the ERP drops its export into /srv/erp/zalihe.csv
*/15 * * * * cd /srv/sync && WOO_CONSUMER_KEY=... WOO_CONSUMER_SECRET=... \
  erp-sync run -c sync.toml -f /srv/erp/zalihe.csv --apply -r logs/$(date +\%F-\%H\%M).csv
```

Or with the Dockerfile in this folder: `docker build -t erp-sync .` then `docker run --rm -v "$PWD:/data" -e WOO_CONSUMER_KEY -e WOO_CONSUMER_SECRET erp-sync run -c /data/sync.toml -f /data/zalihe.csv --apply`.

## Tests

```bash
pytest
```

35 tests, no network: number and encoding parsing, warehouse aggregation, XML paths, VAT and rounding, the price guard, the full CLI against the JSON demo shop (dry run writes nothing, apply is idempotent), and the WooCommerce and Magento adapters against fake APIs (pagination, variations, batch payloads, per-item errors, MSI and legacy stock, retry on 429).

## Layout

```
erp_sync/readers.py      CSV/XML parsing, encoding, warehouse aggregation
erp_sync/rules.py        VAT, rounding, safety stock
erp_sync/engine.py       compare with the shop, the price guard
erp_sync/targets/        woocommerce.py, magento.py, jsonfile.py (demo), http.py (retries)
erp_sync/report.py       terminal summary, CSV/JSON report
erp_sync/cli.py          erp-sync check | run
examples/                demo export, demo shop, mappings for WooCommerce and Magento
```

---

**Ukratko (SR):** Alat komandne linije koji iz izvoza ERP-a (CSV ili XML, Windows-1250 ili UTF-8, decimalni zarez) usklađuje zalihe i cene u WooCommerce ili Magento 2 prodavnici. Sabira zalihe po magacinima koje izaberete, preračunava PDV i zaokružuje cene, zadržava sigurnosnu zalihu, menja samo ono što se razlikuje i podrazumevano radi „na suvo" dok ne dodate `--apply`. Sumnjive promene cena (preko zadatog procenta ili cena 0) ne primenjuje nego ih prijavljuje. Za svakog klijenta se pravi poseban TOML fajl sa mapiranjem kolona. Primeri izvoza i demo prodavnica su izmišljeni.
