from __future__ import annotations

import shutil
from pathlib import Path

import pytest

EXAMPLES = Path(__file__).resolve().parent.parent / "examples"


@pytest.fixture
def demo(tmp_path: Path) -> Path:
    """A copy of the examples folder, so tests can let the jsonfile target write to it."""
    target = tmp_path / "examples"
    shutil.copytree(EXAMPLES, target)
    return target


def write_config(path: Path, body: str) -> Path:
    path.write_text(body, encoding="utf-8")
    return path
