#!/usr/bin/env python3
"""
Builds UCN_Combined.md from the individual narratives, so the two can never drift apart.

    python3 docs/UCN/build_combined.py

Edit the narratives (UCN_SC_E0xx_*.md) and the index/terminology in README.md, then run this. Never edit
UCN_Combined.md by hand: it is overwritten.

The combined file is:  title and intro, the index table (linking to sections instead of files), everything in
README.md after its table (notes, terminology, conventions), then each narrative in order with its headings
moved down one level (the sheet's title becomes `##`) and the sheets separated by a rule.
"""
import glob
import re
from pathlib import Path

HERE = Path(__file__).parent


def demote(text: str) -> str:
    """Move every heading down one level (outside code fences) and never let one skip a level."""
    out, fence, previous = [], False, 1
    for line in text.split("\n"):
        if line.startswith("```"):
            fence = not fence
        match = None if fence else re.match(r"^(#{1,5}) (.*)$", line)
        if match:
            level = min(len(match.group(1)) + 1, previous + 1)
            line = "#" * level + " " + match.group(2)
            previous = level
        out.append(line)
    return "\n".join(out)


def main() -> None:
    readme = (HERE / "README.md").read_text(encoding="utf-8").split("\n")

    rows = [l for l in readme if l.startswith("| UCN_SC_E")]
    index_end = max(i for i, l in enumerate(readme) if l.startswith("| UCN_SC_E"))
    after_table = "\n".join(readme[index_end + 1:]).strip("\n")

    table = ["| Use Case ID | Name | Actor | Section |", "|---|---|---|---|"]
    for row in rows:
        cells = [c.strip() for c in row.strip("|").split("|")]
        ident, name, actor = cells[0], cells[1], cells[2]
        anchor = f"{ident.lower()}--{re.sub(r'[^a-z0-9 -]', '', name.lower()).replace(' ', '-')}"
        table.append(f"| {ident} | {name} | {actor} | [{name}](#{anchor}) |")

    sheets = []
    for path in sorted(glob.glob(str(HERE / "UCN_SC_E*.md"))):
        sheets.append(demote(Path(path).read_text(encoding="utf-8").strip()))

    combined = "\n".join([
        "# Use Case Narratives (UCN) — Web Commute (Combined)",
        "",
        "Documentation of the implemented behaviour of the system's core guest, commuter, staff and driver journeys.",
        "*Generated from the individual narratives by `build_combined.py`: edit those, not this file.*",
        "",
        *table,
        "",
        after_table,
        "",
        "---",
        "",
        "\n\n---\n\n".join(sheets),
        "",
    ])

    (HERE / "UCN_Combined.md").write_text(combined, encoding="utf-8")
    print(f"Wrote UCN_Combined.md: {len(sheets)} narratives, {combined.count(chr(10))} lines")


if __name__ == "__main__":
    main()
