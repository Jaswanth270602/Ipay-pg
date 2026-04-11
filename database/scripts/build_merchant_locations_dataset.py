"""
Build database/data/merchant_locations_dataset.json from countries+states+cities.json.
Run once after updating the source file (or use pre-built dataset in repo).
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "data" / "countries_states_cities.json"
OUT = ROOT / "data" / "merchant_locations_dataset.json"

# UI keys in admin modal must match these country option values
MAP = {
    "India": "India",
    "United States": "USA",
    "United Kingdom": "UK",
}


def main() -> int:
    if not SRC.is_file():
        print(f"Missing source: {SRC}", file=sys.stderr)
        return 1

    print("Loading JSON (this may take a minute)...", flush=True)
    with SRC.open("r", encoding="utf-8") as f:
        countries = json.load(f)

    out: dict[str, dict[str, list[str]]] = {v: {} for v in MAP.values()}

    for c in countries:
        name = c.get("name")
        if name not in MAP:
            continue
        key = MAP[name]
        for st in c.get("states") or []:
            sname = (st.get("name") or "").strip()
            if not sname:
                continue
            cities: list[str] = []
            for city in st.get("cities") or []:
                cn = (city.get("name") or "").strip()
                if cn:
                    cities.append(cn)
            # Dedupe case-insensitively, preserve first occurrence order
            seen: set[str] = set()
            uniq: list[str] = []
            for cn in cities:
                low = cn.casefold()
                if low in seen:
                    continue
                seen.add(low)
                uniq.append(cn)
            uniq.sort(key=str.casefold)
            out[key][sname] = uniq

    OUT.parent.mkdir(parents=True, exist_ok=True)
    with OUT.open("w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False, separators=(",", ":"))

    total_cities = sum(len(v) for st in out.values() for v in st.values())
    print(
        f"Wrote {OUT} — countries: {list(out.keys())}, "
        f"states: {sum(len(st) for st in out.values())}, city rows: {total_cities}",
        flush=True,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
