# OCR test fixtures: synthetic OR/CR specimens

`generate_samples.py` draws fictitious LTO **Official Receipts (OR)** and **Certificates of Registration (CR)** so the
vehicle-document OCR can be tested without anyone's real papers.

- **Everything is invented**: names, addresses, plates, chassis and engine numbers, TINs, amounts.
- They copy the **field layout** only. No seals or logos, no QR code or barcode, no signatures, and every page is
  watermarked `SPECIMEN - FOR TESTING ONLY` and footed "Synthetic test data. Not a government document."
- Keep it that way: do not add real documents, real people's data or official marks to this folder.

```bash
python3 tests/Fixtures/ocr/generate_samples.py                 # 6 ORs + 6 CRs, 4 variants each -> samples/
python3 tests/Fixtures/ocr/generate_samples.py --count 20 --seed 7
```

Needs Pillow only. The same seed gives the same files, so the images are not committed (`samples/` is git-ignored).

Each document is written four ways, to mimic how papers actually reach the system:

| Variant | Mimics |
|---|---|
| `clean` | the page exactly as drawn (best case) |
| `scan` | a flatbed scan: slight tilt and softness, JPEG |
| `photo` | a phone photo: perspective, uneven light, blur, noise, lower resolution |
| `photocopy` | a photocopy: grey, high contrast, specks, dark edges |

`samples/ground_truth.json` lists, per file, the values the form should be prefilled with (`plate_number`, `brand`,
`model`, `year`, `fuel_type`, plus `vin` / engine / MV file number for a CR, or colour / vehicle type for an OR).
Score an OCR run by comparing its output with it, per field and per variant.

## Measuring the real reader

`php artisan vehicle-scan:evaluate` (needs Tesseract) runs the actual reader (`RegistrationDocumentReader`) over the specimens
and scores what would land in the Add Vehicle form against `ground_truth.json`: **right**, **check** (wrong but flagged),
**blank** (not read), and **WRONG** (wrong and marked "read clearly", the case that must stay at zero). Use `--only photo`,
`--limit 10` and `--detail` to narrow it down.

Last measured (6 CRs + 6 ORs, four variants each, 216 values):

| Variant | Correct in the form | Wrong but flagged "check" | Left blank | Wrong and trusted |
|---|---|---|---|---|
| clean | 100% | 0 | 0 | **0** |
| scan | 100% | 0 | 0 | **0** |
| photo | 94% | 1 | 2 | **0** |
| photocopy | 91% | 1 | 4 | **0** |

These are specimens drawn at scan quality: real photographs of worn, stamped papers will read worse. That is why the
manager confirms every value before saving.

`score_ocr.py` is the older raw benchmark (does Tesseract read the text at all, before any extraction). It is what showed
that Sauvola thresholding and full-resolution pages matter: the first synthetic pages were 120 dpi and read about 45%
on photos; at a realistic 300 dpi the same engine reads 79% of raw values, and the label-and-cell extractor with
independent passes lifts the form to 94%.

`tests/Fixtures/ocr/tsv/` holds real Tesseract output for five specimens, so the extractor is tested without Tesseract.
