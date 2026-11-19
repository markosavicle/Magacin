"""Number parsing for ERP exports: '1.234,56', '1 234,56', '-3', '12,5%'."""
from __future__ import annotations

from decimal import Decimal, InvalidOperation


def parse_decimal(raw: str | None, decimal_separator: str = ",") -> Decimal | None:
    """Parse a number as written in the export. Empty means None; anything unparsable raises ValueError.

    The decimal separator is configured, never guessed: in a Serbian export '1.234' is one thousand
    two hundred thirty-four, in an English one it is one point two three four.
    """
    if raw is None:
        return None
    text = raw.strip().replace(" ", "").replace(" ", "").replace("%", "")
    if text == "":
        return None
    thousands = "." if decimal_separator == "," else ","
    text = text.replace(thousands, "").replace(decimal_separator, ".")
    if text.startswith("(") and text.endswith(")"):   # accounting negative: (12,00)
        text = "-" + text[1:-1]
    try:
        value = Decimal(text)
    except InvalidOperation:
        raise ValueError(f"'{raw}' is not a number") from None
    if not value.is_finite():
        raise ValueError(f"'{raw}' is not a number")
    return value
