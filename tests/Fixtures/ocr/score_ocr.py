#!/usr/bin/env python3
"""
Baseline: how well does Tesseract READ the specimen OR/CR images, before any field-extraction logic?

For every image in samples/ it preprocesses the page, runs `tesseract`, and checks whether each value listed in
ground_truth.json shows up in the recognised text. It reports, per field and per variant (clean / scan / photo /
photocopy), how many values were read exactly, read with one character wrong ("near"), or missed.

    python3 tests/Fixtures/ocr/score_ocr.py                 # needs the tesseract binary
    python3 tests/Fixtures/ocr/score_ocr.py --psm 4         # another page-segmentation mode
    python3 tests/Fixtures/ocr/score_ocr.py --verbose       # show what was read for every miss
    python3 tests/Fixtures/ocr/score_ocr.py --selftest      # check the scorer itself, no OCR needed

This measures reading only. A value that is present somewhere in the text may still be hard to LOCATE (which
box did it come from?); that is what the label-anchored extractor has to solve next.
"""
import argparse
import json
import re
import shutil
import subprocess
import sys
import tempfile
from collections import defaultdict
from pathlib import Path

from PIL import Image, ImageOps

HERE = Path(__file__).parent


def preprocess(path, work, width=2000, channel="gray", cutoff=1):
    """Pick a channel, enlarge small pages to a scanner-like width, stretch the contrast."""
    img = Image.open(path).convert("RGB")
    if channel == "gray":
        img = img.convert("L")
    elif channel == "max":      # the brightest channel: coloured tints and stamps fade towards white
        img = Image.merge("L", [ImageOps.invert(ImageOps.invert(c)) for c in img.split()[:1]]) if False else \
            __import__("PIL.ImageChops", fromlist=["lighter"]).lighter(__import__("PIL.ImageChops", fromlist=["lighter"]).lighter(*img.split()[:2]), img.split()[2])
    else:
        img = img.split()["rgb".index(channel)]
    if width and img.width != width:
        img = img.resize((width, int(img.height * width / img.width)), Image.LANCZOS)
    if cutoff is not None:
        img = ImageOps.autocontrast(img, cutoff=cutoff)
    out = Path(work) / (path.stem + ".png")
    img.save(out)
    return out


def ocr(image, psm, thresh=0, dpi=None):
    cmd = ["tesseract", str(image), "stdout", "--psm", str(psm), "-l", "eng", "-c", f"thresholding_method={thresh}"]
    if dpi:
        cmd += ["--dpi", str(dpi)]
    result = subprocess.run(cmd, capture_output=True, text=True, env={**__import__("os").environ, "OMP_THREAD_LIMIT": "1"})
    return result.stdout


def norm(s):
    """Upper-case and drop everything that is not a letter or digit, so 'ABC 1234' == 'ABC-1234'."""
    return re.sub(r"[^A-Z0-9]", "", str(s).upper())


def within_one(needle, haystack):
    """True if `needle` occurs in `haystack` with at most one wrong, missing or extra character."""
    n = len(needle)
    if n < 5:
        return False
    for size in (n - 1, n, n + 1):
        for i in range(0, max(0, len(haystack) - size) + 1):
            window = haystack[i:i + size]
            if size == n:
                if sum(a != b for a, b in zip(needle, window)) <= 1:
                    return True
            elif edit_is_one(needle, window):
                return True
    return False


def edit_is_one(a, b):
    if abs(len(a) - len(b)) != 1:
        return False
    if len(a) > len(b):
        a, b = b, a
    i = j = diff = 0
    while i < len(a) and j < len(b):
        if a[i] == b[j]:
            i += 1
            j += 1
        else:
            diff += 1
            if diff > 1:
                return False
            j += 1
    return True


def judge(field, expected, text):
    """'exact' | 'near' | 'miss' for one expected value against the recognised text."""
    want = norm(expected)
    flat = norm(text)

    if field in ("year", "fuel_type", "brand", "color", "vehicle_type", "model"):
        # short common words: demand a whole token on a line, so '2002' inside an amount does not count
        tokens = {norm(t) for line in text.splitlines() for t in re.split(r"[\s:]+", line) if t}
        lines = {norm(line) for line in text.splitlines()}
        if want in tokens or any(want in l for l in lines if len(want) > 4):
            return "exact"
        return "miss"

    if want in flat:
        return "exact"
    return "near" if within_one(want, flat) else "miss"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--samples", default=str(HERE / "samples"))
    ap.add_argument("--psm", type=int, default=6)
    ap.add_argument("--verbose", action="store_true")
    ap.add_argument("--width", type=int, default=2000, help="enlarge/shrink pages to this width (0 = leave)")
    ap.add_argument("--channel", default="gray", choices=["gray", "r", "g", "b", "max"])
    ap.add_argument("--thresh", type=int, default=0, help="tesseract thresholding_method: 0 Otsu, 1 adaptive Otsu, 2 Sauvola")
    ap.add_argument("--cutoff", type=int, default=1, help="autocontrast cutoff %% (-1 = off)")
    ap.add_argument("--only", default="", help="comma list of variants to score, e.g. photo,photocopy")
    ap.add_argument("--selftest", action="store_true", help="feed the expected values back as 'OCR text'")
    args = ap.parse_args()

    samples = Path(args.samples)
    truth_file = samples / "ground_truth.json"
    if not truth_file.exists():
        sys.exit(f"{truth_file} not found. Run generate_samples.py first.")
    truth = json.loads(truth_file.read_text(encoding="utf-8"))

    if not args.selftest and not shutil.which("tesseract"):
        sys.exit("tesseract is not installed.  sudo apt-get install -y tesseract-ocr   (or use --selftest)")

    tally = defaultdict(lambda: defaultdict(lambda: {"exact": 0, "near": 0, "miss": 0}))
    misses = []

    if args.only:
        keep = set(args.only.split(","))
        truth = [e for e in truth if e["variant"] in keep]

    with tempfile.TemporaryDirectory() as work:
        for entry in truth:
            if args.selftest:
                text = "\n".join(f"{k} {v}" for k, v in entry["expected"].items())
            else:
                text = ocr(preprocess(samples / entry["file"], work, args.width, args.channel, None if args.cutoff < 0 else args.cutoff), args.psm, args.thresh)

            for field, value in entry["expected"].items():
                verdict = judge(field, value, text)
                tally[entry["variant"]][field][verdict] += 1
                if verdict != "exact":
                    misses.append((entry["file"], field, value, verdict))

    variants = [v for v in ("clean", "scan", "photo", "photocopy") if v in tally]
    fields = sorted({f for v in tally.values() for f in v})

    print(f"\nTesseract --psm {args.psm}{'  (SELF-TEST, not real OCR)' if args.selftest else ''}")
    print("cells: exact / near (1 character off) / miss\n")
    print(f"{'field':<16}" + "".join(f"{v:>16}" for v in variants))
    for field in fields:
        row = f"{field:<16}"
        for v in variants:
            c = tally[v].get(field)
            row += f"{(str(c['exact']) + '/' + str(c['near']) + '/' + str(c['miss'])) if c else '-':>16}"
        print(row)

    print("\nValues read exactly, by variant:")
    for v in variants:
        total = sum(sum(c.values()) for c in tally[v].values())
        exact = sum(c["exact"] for c in tally[v].values())
        near = sum(c["near"] for c in tally[v].values())
        print(f"  {v:<10} {exact:>3}/{total:<3} = {100 * exact // max(total, 1):>3}%   (+{near} one character off)")

    if args.verbose and misses:
        print("\nNot read exactly:")
        for file, field, value, verdict in misses:
            print(f"  {file:<24} {field:<14} expected {value!r}  [{verdict}]")


if __name__ == "__main__":
    main()
