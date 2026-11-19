from __future__ import annotations

import time

import httpx

RETRY_STATUSES = {429, 502, 503, 504}


class ApiError(RuntimeError):
    pass


def request(client: httpx.Client, method: str, url: str, attempts: int = 4, **kwargs) -> httpx.Response:
    """One API call with retries on rate limits and gateway errors (exponential backoff, honours Retry-After)."""
    delay = 1.0
    for attempt in range(1, attempts + 1):
        try:
            response = client.request(method, url, **kwargs)
        except httpx.TransportError as exc:
            if attempt == attempts:
                raise ApiError(f"{method} {url}: {exc}") from exc
            time.sleep(delay)
            delay *= 2
            continue
        if response.status_code in RETRY_STATUSES and attempt < attempts:
            retry_after = response.headers.get("Retry-After", "")
            time.sleep(float(retry_after) if retry_after.isdigit() else delay)
            delay *= 2
            continue
        if response.status_code >= 400:
            raise ApiError(f"{method} {url} -> HTTP {response.status_code}: {response.text[:300]}")
        return response
    raise ApiError(f"{method} {url}: gave up after {attempts} attempts")


def chunks(items: list, size: int):
    for i in range(0, len(items), size):
        yield items[i:i + size]
