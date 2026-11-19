# erp-sync in a container, e.g. for a cron job on the client's server:
#   docker build -t erp-sync erp-sync-engine
#   docker run --rm -v "$PWD/sync:/data" -e WOO_CONSUMER_KEY -e WOO_CONSUMER_SECRET \
#     erp-sync run -c /data/sync.toml -f /data/zalihe.csv --apply -r /data/izvestaj.csv
FROM python:3.12-slim
WORKDIR /app
COPY pyproject.toml README.md ./
COPY erp_sync ./erp_sync
RUN pip install --no-cache-dir . && useradd --create-home sync
USER sync
WORKDIR /data
ENTRYPOINT ["erp-sync"]
CMD ["--help"]
