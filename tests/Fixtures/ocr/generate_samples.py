#!/usr/bin/env python3
"""
Synthetic LTO OR/CR specimens for testing the vehicle-document OCR.

Everything on these pages is invented (names, plates, chassis numbers, TINs, addresses). They copy the
FIELD LAYOUT of an Official Receipt and a Certificate of Registration so the extraction can be tested, and
nothing else: no seals or logos, no QR code or barcode, no signatures, and every page carries a
"SPECIMEN" watermark. They are test fixtures, not copies of government documents.

    python3 tests/Fixtures/ocr/generate_samples.py [--count 6] [--seed 2026] [--out tests/Fixtures/ocr/samples]

Each document is written clean and as three degraded variants (flatbed scan, phone photo, photocopy),
together with ground_truth.json: the values the OCR is expected to read for each file.
Needs only Pillow. Same seed and scale, same files.
"""
import argparse
import json
import random
from datetime import date, datetime, timedelta
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageEnhance, ImageFilter, ImageFont

FONT_DIR = Path("/usr/share/fonts/truetype")
SANS = [FONT_DIR / "liberation/LiberationSans-Regular.ttf", FONT_DIR / "dejavu/DejaVuSans.ttf"]
SANS_B = [FONT_DIR / "liberation/LiberationSans-Bold.ttf", FONT_DIR / "dejavu/DejaVuSans-Bold.ttf"]
MONO = [FONT_DIR / "liberation/LiberationMono-Regular.ttf", FONT_DIR / "dejavu/DejaVuSansMono.ttf"]
MONO_B = [FONT_DIR / "liberation/LiberationMono-Bold.ttf", FONT_DIR / "dejavu/DejaVuSansMono-Bold.ttf"]


# Pages are laid out on a 1000-px-wide grid and drawn K times larger, so a page comes out at the size a real
# A4 scan (~2500 px) or phone photo has. Set with --scale.
K = 2.5


def font(candidates, size):
    for path in candidates:
        if path.exists():
            return ImageFont.truetype(str(path), max(6, int(round(size * K))))
    return ImageFont.load_default()


class Scaled:
    """An ImageDraw that takes the 1000-px layout grid and draws it K times larger."""

    def __init__(self, draw):
        self._d = draw

    def _xy(self, xy):
        return tuple(v * K for v in xy)

    def text(self, xy, text, **kw):
        self._d.text(self._xy(xy), text, **kw)

    def line(self, xy, fill=None, width=1):
        self._d.line(self._xy(xy), fill=fill, width=max(1, round(width * K)))

    def rectangle(self, xy, fill=None, outline=None, width=1):
        self._d.rectangle(self._xy(xy), fill=fill, outline=outline, width=max(1, round(width * K)))

    def textlength(self, text, font):
        return self._d.textlength(text, font=font) / K


def canvas(w, h, color):
    return Image.new("RGB", (int(w * K), int(h * K)), color)


def fit(candidates, size, text, max_width):
    """The largest font (down to 9 px) at which `text` still fits `max_width`."""
    probe = ImageDraw.Draw(Image.new("RGB", (1, 1)))
    while size > 9:
        f = font(candidates, size)
        if probe.textlength(text, font=f) <= max_width * K:
            return f
        size -= 1
    return font(candidates, 9)


# ── invented data ────────────────────────────────────────────────────────────────────────────────

SURNAMES = ["DELA CRUZ", "REYES", "SANTOS", "GARCIA", "MENDOZA", "TORRES", "VILLANUEVA", "PEÑA", "NUÑEZ", "CASTILLO",
            "RAMOS", "AQUINO", "BAUTISTA", "FLORES", "NAVARRO", "SALAZAR", "LIM", "TAN", "ABELLANA", "OSMEÑA"]
FIRST = ["JUAN", "MARIA", "JOSE", "ANA", "PEDRO", "LIZA", "RAMON", "CARLA", "MARK", "JENNY", "EDGAR", "ROSALIE",
         "DANTE", "ARNEL", "GLENDA", "NOEL", "MARITES", "ERNESTO", "CRISTINA", "BENJAMIN"]
MIDDLE = ["SANTOS", "REYES", "DELA ROSA", "CRUZ", "LOPEZ", "MARTINEZ", "PADILLA", "VELASCO", "GUTIERREZ", "ABAD"]
PLACES = [("SAN ISIDRO", "TALISAY CITY", "CEBU", "6045"), ("POBLACION", "MINGLANILLA", "CEBU", "6046"),
          ("LAHUG", "CEBU CITY", "CEBU", "6000"), ("BASAK", "LAPU-LAPU CITY", "CEBU", "6015"),
          ("TABUNOK", "TALISAY CITY", "CEBU", "6045"), ("BANILAD", "MANDAUE CITY", "CEBU", "6014"),
          ("TISA", "CEBU CITY", "CEBU", "6000"), ("CANDUMAN", "MANDAUE CITY", "CEBU", "6014")]
STREETS = ["P. REMEDIOS ST.", "ROSAL ST.", "MANGO AVE.", "ZONE 2 PUROK 4", "SITIO BAGONG BUHAY", "NATIONAL HIGHWAY", "JAKOSALEM ST.",
           "BLK 5 LOT 12", "ZONE 3", "PUROK MAHARLIKA"]

# make -> [(series, body type, denomination / vehicle type, fuel list)]
VEHICLES = {
    "ISUZU": [("ELF NHR", "JEEPNEY", "JEEPNEY", ["DIESEL"]), ("CROSSWIND", "UTILITY VEHICLE", "UTILITY VEHICLE", ["DIESEL"])],
    "TOYOTA": [("HIACE", "VAN", "UTILITY VEHICLE", ["DIESEL"]), ("INNOVA", "WAGON", "UTILITY VEHICLE", ["DIESEL", "GASOLINE"]),
               ("VIOS", "SEDAN", "CAR", ["GASOLINE"])],
    "MITSUBISHI": [("L300", "VAN", "UTILITY VEHICLE", ["DIESEL"]), ("ADVENTURE", "WAGON", "UTILITY VEHICLE", ["DIESEL"])],
    "SUZUKI": [("MULTICAB", "VAN", "UTILITY VEHICLE", ["GASOLINE"]), ("CARRY", "VAN", "UTILITY VEHICLE", ["GASOLINE"])],
    "HYUNDAI": [("H100", "VAN", "UTILITY VEHICLE", ["DIESEL"])],
    "HONDA": [("TMX 155", "MOTORCYCLE", "MOTORCYCLE WITHOUT SIDECAR", ["GAS"]), ("CLICK 125", "MOTORCYCLE", "MOTORCYCLE WITHOUT SIDECAR", ["GAS"])],
    "KYMCO": [("LX150", "MOTORCYCLE", "MOTORCYCLE WITHOUT SIDECAR", ["GAS"])],
    "YAMAHA": [("MIO SPORTY", "MOTORCYCLE", "MOTORCYCLE WITHOUT SIDECAR", ["GAS"])],
}
COLORS = ["RED", "WHITE", "BLUE", "BLACK", "SILVER", "GREEN", "YELLOW", "MAROON"]
OFFICES = [("NEW REGISTRATION UNIT", "0201"), ("RENEWAL UNIT", "0207"), ("NEW REGISTRATION UNIT", "0712"), ("RENEWAL UNIT", "0710")]
VIN_CHARS = "ABCDEFGHJKLMNPRSTUVWXYZ0123456789"  # no I, O, Q


def person(r):
    return f"{r.choice(SURNAMES)}, {r.choice(FIRST)} {r.choice(MIDDLE)}"


def address(r):
    brgy, city, prov, zipc = r.choice(PLACES)
    return f"{r.choice(STREETS)}, {brgy}, {city}, {zipc}, {prov}"


def plate(r, motorcycle):
    L = "ABCDEFGHJKLMNPRSTUVWXYZ"
    if motorcycle:
        return r.choice([
            lambda: f"{r.choice(L)}{r.randint(100, 999)}{r.choice(L)}{r.choice(L)}",
            lambda: f"{r.choice(L)}{r.choice(L)}{r.randint(1000, 9999)}",
            lambda: f"{r.randint(100, 999)}{r.choice(L)}{r.choice(L)}{r.choice(L)}",
        ])()
    return f"{r.choice(L)}{r.choice(L)}{r.choice(L)} {r.randint(100, 9999)}"


def vehicle(r):
    make = r.choice(list(VEHICLES))
    series, body, denom, fuels = r.choice(VEHICLES[make])
    moto = body == "MOTORCYCLE"
    return {
        "make": make, "series": series, "body": body, "denomination": denom, "fuel": r.choice(fuels), "moto": moto,
        "year": r.randint(1996, 2025), "plate": plate(r, moto), "color": r.choice(COLORS),
        "chassis": "".join(r.choice(VIN_CHARS) for _ in range(17)),
        "engine": "".join(r.choice("ABCDEFGHJKLMNPRSTUVWXYZ0123456789") for _ in range(r.randint(8, 11))),
        "mv_file": f"{r.randint(1000, 9999)}-{r.randint(0, 99999999999):011d}",
        "cylinders": 1 if moto else r.choice([3, 4]),
        "displacement": r.choice([110, 125, 150, 155]) if moto else r.choice([1500, 2000, 2400, 2500, 2800]),
        "gross": r.randint(180, 230) if moto else r.randint(1800, 3500),
    }


def words(n):
    ones = "ZERO ONE TWO THREE FOUR FIVE SIX SEVEN EIGHT NINE TEN ELEVEN TWELVE THIRTEEN FOURTEEN FIFTEEN SIXTEEN SEVENTEEN EIGHTEEN NINETEEN".split()
    tens = "_ _ TWENTY THIRTY FORTY FIFTY SIXTY SEVENTY EIGHTY NINETY".split()
    if n < 20:
        return ones[n]
    if n < 100:
        return tens[n // 10] + ("-" + ones[n % 10] if n % 10 else "")
    if n < 1000:
        return ones[n // 100] + " HUNDRED" + (" " + words(n % 100) if n % 100 else "")
    return words(n // 1000) + " THOUSAND" + (" " + words(n % 1000) if n % 1000 else "")


# ── drawing helpers ──────────────────────────────────────────────────────────────────────────────

INK = (20, 20, 20)
PAPER = (252, 252, 250)


def watermark(img, text="SPECIMEN  -  FOR TESTING ONLY", alpha=26):
    w, h = img.size
    layer = Image.new("RGBA", (w * 2, h * 2), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    f = font(SANS_B, max(34, w // 20) / K)
    tw = d.textlength(text, font=f)
    for row in range(-2, 5):
        d.text((w - tw / 2, h * 0.4 + row * h * 0.2), text, font=f, fill=(60, 90, 160, alpha))
    layer = layer.rotate(32, resample=Image.BICUBIC).crop((w // 2, h // 2, w // 2 + w, h // 2 + h))
    base = img.convert("RGBA")
    base.alpha_composite(layer)
    return base.convert("RGB")


def footer(img, text="Synthetic test data. Not a government document."):
    d = ImageDraw.Draw(img)
    f = font(SANS, 14)
    d.text((img.width / 2, img.height - 20 * K), text, font=f, fill=(150, 30, 30), anchor="mm")


def render_or(data, r):
    W, H = 1000, 1380
    img = canvas(W, H, PAPER)
    d = Scaled(ImageDraw.Draw(img))
    F, FB, M, MB = font(SANS, 18), font(SANS_B, 20), font(MONO, 17), font(MONO_B, 17)
    cx = W // 2

    d.text((cx, 70), "Republic of the Philippines", font=font(SANS, 15), fill=INK, anchor="mm")
    d.text((cx, 98), "DEPARTMENT OF TRANSPORTATION", font=font(SANS, 24), fill=INK, anchor="mm")
    d.text((cx, 135), "LAND TRANSPORTATION OFFICE", font=font(SANS_B, 27), fill=INK, anchor="mm")
    d.text((cx, 172), "TEST CITY, TEST PROVINCE (SPECIMEN)", font=font(SANS, 15), fill=INK, anchor="mm")

    office, code = data["office"]
    d.text((120, 232), "Field Office:", font=F, fill=INK)
    d.text((340, 232), office, font=FB, fill=INK, anchor="mt")
    d.line((200, 256, 460, 256), fill=INK, width=2)
    d.text((120, 270), "Office Code:", font=F, fill=INK)
    d.text((330, 270), code, font=FB, fill=INK, anchor="mt")
    d.line((200, 294, 460, 294), fill=INK, width=2)
    d.text((500, 232), "TIN:", font=F, fill=INK)
    d.text((780, 232), data["office_tin"], font=F, fill=INK, anchor="mt")
    d.text((500, 270), "Date:", font=F, fill=INK)
    d.text((700, 270), data["or_datetime"], font=F, fill=INK, anchor="mt")

    d.rectangle((120, 330, 330, 372), fill=(70, 70, 70))
    d.text((225, 351), "OFFICIAL RECEIPT", font=FB, fill="white", anchor="mm")
    d.text((350, 351), data["or_number"], font=font(SANS_B, 26), fill=INK, anchor="lm")

    d.text((120, 410), "RECEIVED FROM (Last Name, First Name Middle Name)", font=font(SANS, 15), fill=INK)
    d.text((120, 436), data["owner"], font=FB, fill=INK)
    d.line((120, 466, 560, 466), fill=INK, width=2)
    d.text((120, 480), "ADDRESS (No. Street, Barangay, Municipality, Zip Code, Province)", font=font(SANS, 15), fill=INK)
    d.text((120, 506), data["address"], font=font(SANS_B, 17), fill=INK)
    d.line((120, 536, 700, 536), fill=INK, width=2)
    d.text((120, 548), "TIN (Tax Identification Number)", font=font(SANS, 15), fill=INK)
    d.text((410, 548), "LTO Client ID / LTO BUSINESS ID", font=font(SANS, 15), fill=INK)
    d.text((120, 572), data["owner_tin"], font=FB, fill=INK)
    d.text((410, 572), data["client_id"], font=FB, fill=INK)

    # payment details / breakdown
    d.rectangle((120, 620, 640, 650), fill=(70, 70, 70))
    d.rectangle((640, 620, 880, 650), fill=(70, 70, 70))
    d.text((380, 635), "PAYMENT DETAILS", font=font(SANS_B, 16), fill="white", anchor="mm")
    d.text((760, 635), "BREAKDOWN OF PAYMENT", font=font(SANS_B, 16), fill="white", anchor="mm")
    d.rectangle((120, 650, 880, 975), outline=INK, width=2)
    d.line((640, 650, 640, 975), fill=INK, width=2)

    rows = [("Transaction No:", data["transaction"]), ("Plate No:", None), ("File No:", data["file_no"]),
            ("Classification:", data["classification"]), ("Vehicle Type:", data["vehicle_type"]),
            ("Fuel Type:", data["fuel_label"]), ("Aircon Type:", "N/A"), ("Color:", data["color"]), ("Year Model:", str(data["year"]))]
    y = 668
    for label, value in rows:
        if label == "Plate No:":
            d.text((130, y), label, font=font(SANS, 24), fill=INK)
            d.text((260, y - 2), data["plate"], font=font(SANS, 26), fill=INK)
            y += 36
            continue
        d.text((210, y), label, font=font(MONO, 14), fill=INK, anchor="ra" if False else "la")
        d.text((340, y), value, font=font(MONO, 14), fill=INK)
        y += 27
    d.text((340, y + 2), f"This payment is valid until {data['valid_until']} and due for", font=font(MONO, 13), fill=INK)
    d.text((340, y + 20), f"renewal on {data['renewal']}.", font=font(MONO, 13), fill=INK)

    by = 668
    total = 0.0
    for label, amount in data["breakdown"]:
        d.text((652, by), label, font=font(SANS, 12), fill=INK)
        d.text((762, by), "PHP", font=font(SANS, 13), fill=INK)
        d.text((870, by), f"{amount:,.2f}", font=font(SANS, 14), fill=INK, anchor="ra")
        total += amount
        by += 26

    d.text((120, 1000), "TOTAL AMOUNT PAID:", font=font(SANS_B, 19), fill=INK)
    d.text((700, 997), "PHP", font=font(SANS_B, 19), fill=INK)
    d.text((880, 997), f"{total:,.2f}", font=font(SANS_B, 19), fill=INK, anchor="ra")
    d.text((120, 1043), "AMOUNT IN WORDS:", font=font(SANS_B, 19), fill=INK)
    peso, cents = int(total), round((total - int(total)) * 100)
    d.text((880, 1043), f"{words(peso)} PESOS", font=font(SANS_B, 16), fill=INK, anchor="ra")
    d.text((880, 1067), f"AND {cents:02d}/100 CENTAVO(S)", font=font(SANS_B, 16), fill=INK, anchor="ra")

    d.text((120, 1115), "MODE OF PAYMENT:", font=font(SANS, 14), fill=INK)
    d.text((300, 1115), data["mode"], font=font(SANS, 14), fill=INK)
    d.text((120, 1145), "LOCATION:", font=font(SANS, 14), fill=INK)
    d.text((300, 1145), "-", font=font(SANS, 14), fill=INK)
    d.text((cx, 1205), "This is a system generated Official Receipt. No Signatures needed.", font=font(SANS, 14), fill=INK, anchor="mm")
    d.text((cx, 1230), "[ specimen: barcode and QR code intentionally left out ]", font=font(SANS, 13), fill=(120, 120, 120), anchor="mm")
    d.text((W - 120, H - 60), "Page 1 of 1", font=font(SANS, 13), fill=INK, anchor="ra")

    img = watermark(img)
    footer(img)
    return img


def render_cr(data, r):
    W, H = 1100, 800
    img = canvas(W, H, PAPER)
    # a pale yellow security tint, as on the real certificate, makes the text harder to read on purpose
    img.paste(canvas(W - 70, 470, (252, 247, 200)), (int(35 * K), int(262 * K)))
    d = Scaled(ImageDraw.Draw(img))
    F, FB = font(SANS, 14), font(SANS_B, 17)
    cx = W // 2

    d.rectangle((35, 28, W - 35, H - 52), outline=INK, width=2)
    d.text((W - 60, 55), "LTO Form No. 3", font=font(SANS, 13), fill=INK, anchor="ra")
    d.text((cx, 72), "Republic of the Philippines", font=font(SANS, 17), fill=INK, anchor="mm")
    d.text((cx, 98), "DEPARTMENT OF TRANSPORTATION", font=font(SANS, 22), fill=INK, anchor="mm")
    d.text((cx, 130), "LAND TRANSPORTATION OFFICE", font=font(SANS_B, 26), fill=INK, anchor="mm")
    d.text((cx, 164), "TEST CITY (SPECIMEN)", font=font(SANS, 15), fill=INK, anchor="mm")
    d.text((240, 196), "Field Office:", font=font(SANS, 15), fill=INK)
    d.text((340, 196), data["district"], font=font(SANS_B, 16), fill=INK)

    d.rectangle((35, 215, 255, 262), fill=(30, 30, 30))
    d.text((145, 238), "CERTIFICATE OF REGISTRATION", font=fit(SANS_B, 14, "CERTIFICATE OF REGISTRATION", 200), fill="white", anchor="mm")
    d.text((285, 222), "CR", font=font(SANS_B, 20), fill=INK)
    d.text((285, 247), "No.", font=font(SANS_B, 20), fill=INK)
    d.text((335, 224), data["cr_number"], font=font(SANS, 34), fill=(190, 60, 60))
    d.text((620, 224), "DATE:", font=font(SANS, 17), fill=INK)
    d.text((700, 218), data["cr_date"], font=font(SANS_B, 26), fill=INK)
    d.text((820, 252), "(MM-DD-YYYY)", font=font(SANS, 12), fill=INK, anchor="ma")
    d.line((690, 250, 960, 250), fill=INK, width=1)

    L, T = 35, 262
    cols4 = [L, 205, 455, 700, 1065]  # MV file | plate | engine | chassis
    def cell(x0, y0, x1, y1, label, value, align="right", size=18):
        d.rectangle((x0, y0, x1, y1), outline=INK, width=1)
        d.text((x0 + 5, y0 + 3), label, font=fit(SANS, 11, label, (x1 - x0) - 8), fill=INK)
        if value:
            vf = fit(SANS_B, size, value, (x1 - x0) - 16)
            if align == "right":
                d.text((x1 - 8, y1 - 6), value, font=vf, fill=INK, anchor="rd")
            else:
                d.text((x0 + 8, y1 - 6), value, font=vf, fill=INK, anchor="ld")

    y0, y1 = 262, 308
    cell(35, y0, 205, y1, "MV FILE NO.", data["mv_file"], size=16)
    cell(205, y0, 455, y1, "PLATE NO.", data["plate"], size=18)
    cell(455, y0, 665, y1, "ENGINE NO.", data["engine"], size=16)
    cell(665, y0, 1065, y1, "CHASSIS NO.", data["chassis"], size=17)
    y0, y1 = 308, 356
    cell(35, y0, 205, y1, "DENOMINATION", data["denomination"], size=13)
    cell(205, y0, 455, y1, "PISTON DISPLACEMENT", str(data["displacement"]), size=18)
    cell(455, y0, 665, y1, "NO. OF CYLINDER", str(data["cylinders"]), size=18)
    cell(665, y0, 1065, y1, "FUEL", data["fuel"], size=18)
    y0, y1 = 356, 404
    cell(35, y0, 205, y1, "MAKE", data["make"].title(), size=17)
    cell(205, y0, 455, y1, "SERIES", data["series"], size=17)
    cell(455, y0, 665, y1, "BODY TYPE", data["body"], size=15)
    cell(665, y0, 800, y1, "BODY NO.", "", size=15)
    cell(800, y0, 1065, y1, "YEAR MODEL", str(data["year"]), size=18)
    y0, y1 = 404, 452
    cell(35, y0, 205, y1, "GROSS WT.", str(data["gross"]), size=16)
    cell(205, y0, 455, y1, "NET WT.", str(int(data["gross"] * 0.5)), size=16)
    cell(455, y0, 665, y1, "SHIPPING WT.", str(int(data["gross"] * 0.5)), size=16)
    cell(665, y0, 1065, y1, "NET. CAPACITY", str(int(data["gross"] * 0.5)), size=16)
    y0, y1 = 452, 498
    cell(35, y0, 665, y1, "COMPLETE OWNER'S NAME", data["owner_cr"], align="left", size=15)
    cell(665, y0, 1065, y1, "TELEPHONE NO/CONTACT DETAILS", "", size=15)
    y0, y1 = 498, 544
    cell(35, y0, 1065, y1, "COMPLETE ADDRESS (No. Street City, Municipality, Province)", data["address"], align="left", size=14)
    y0, y1 = 544, 590
    cell(35, y0, 1065, y1, "ENCUMBERED TO", "", size=15)
    y0, y1 = 590, 650
    cell(35, y0, 210, y1, "DETAILS OF FIRST REGISTRATION", "", size=15)
    cell(210, y0, 400, y1, "OR No.", data["or_ref"], size=16)
    cell(400, y0, 700, y1, "O.R DATE", data["cr_date"], size=16)
    cell(700, y0, 1065, y1, "", f"{data['or_amount']:,.2f}", size=16)

    t = font(SANS, 13)
    d.text((45, 664), "This is to certify that the Motor Vehicle described herein is duly registered. This certificate is VALID only when signed", font=t, fill=INK)
    d.text((45, 681), "by authorized LTO Official, Property sealed and accomplished by Official Receipt as proof of payment. Any unautho-", font=t, fill=INK)
    d.text((45, 700), "rized erasure or alteration hereon will invalidate this document.", font=t, fill=INK)
    d.rectangle((35, 726, 330, 742), fill=(30, 30, 30))
    d.text((60, 734), "REGISTRANT'S SIGNATURE", font=font(SANS_B, 11), fill="white", anchor="lm")
    d.text((760, 728), "AUTHORIZED SIGNATORY (SPECIMEN)", font=font(SANS_B, 14), fill=INK, anchor="ma")

    img = watermark(img, alpha=34)
    footer(img)
    return img


# ── degradations (Pillow only) ───────────────────────────────────────────────────────────────────

def noise(img, sigma, strength):
    n = Image.effect_noise(img.size, sigma).convert("RGB")
    return Image.blend(img, ImageChops.multiply(img, n.point(lambda v: 200 + v // 5)), strength)


def jpeg(img, quality, path):
    img.save(path, "JPEG", quality=quality)


def v_scan(img, r):
    img = img.rotate(r.uniform(-1.2, 1.2), resample=Image.BICUBIC, expand=True, fillcolor=(235, 235, 235))
    img = img.filter(ImageFilter.GaussianBlur(0.9))
    return ImageEnhance.Contrast(img).enhance(0.93), 80


def v_photo(img, r):
    w, h = img.size
    pad = int(w * 0.07)
    desk = Image.new("RGB", (w + 2 * pad, h + 2 * pad), (r.randint(110, 150), r.randint(95, 125), r.randint(80, 110)))
    desk.paste(img, (pad, pad))
    shift = lambda: r.uniform(-0.035, 0.035) * desk.width
    W, H = desk.size
    src = [(0, 0), (W, 0), (W, H), (0, H)]
    dst = [(shift(), shift()), (W + shift(), shift()), (W + shift(), H + shift()), (shift(), H + shift())]
    # PIL wants coefficients mapping output -> input; solve the 8-parameter homography by Gauss elimination.
    rows, vals = [], []
    for (x, y), (u, v) in zip(dst, src):
        rows.append([x, y, 1, 0, 0, 0, -u * x, -u * y]); vals.append(u)
        rows.append([0, 0, 0, x, y, 1, -v * x, -v * y]); vals.append(v)
    n = 8
    a = [rows[i] + [vals[i]] for i in range(n)]
    for i in range(n):
        p = max(range(i, n), key=lambda k: abs(a[k][i])); a[i], a[p] = a[p], a[i]
        for k in range(i + 1, n):
            f = a[k][i] / a[i][i]
            a[k] = [a[k][j] - f * a[i][j] for j in range(n + 1)]
    co = [0] * n
    for i in reversed(range(n)):
        co[i] = (a[i][n] - sum(a[i][j] * co[j] for j in range(i + 1, n))) / a[i][i]
    out = desk.transform(desk.size, Image.PERSPECTIVE, co, Image.BICUBIC, fillcolor=(90, 80, 70))
    out = out.rotate(r.uniform(-3, 3), resample=Image.BICUBIC, fillcolor=(90, 80, 70))
    # uneven light: a soft diagonal shadow
    side = max(out.size) * 2
    g = Image.linear_gradient("L").resize((side, side)).rotate(r.uniform(0, 360), resample=Image.BICUBIC)
    left, top = (side - out.width) // 2, (side - out.height) // 2
    g = g.crop((left, top, left + out.width, top + out.height)).filter(ImageFilter.GaussianBlur(40))
    shade = Image.merge("RGB", [g.point(lambda v: 150 + v * 105 // 255)] * 3)
    out = ImageChops.multiply(out, shade)
    out = out.filter(ImageFilter.GaussianBlur(r.uniform(1.0, 2.0)))
    out = noise(out, 20, 0.5)
    out = out.resize((int(out.width * 0.8), int(out.height * 0.8)), Image.LANCZOS)
    return ImageEnhance.Brightness(out).enhance(r.uniform(0.9, 1.05)), 70


def v_copy(img, r):
    g = img.convert("L").rotate(r.uniform(-1.5, 1.5), resample=Image.BICUBIC, expand=True, fillcolor=255)
    g = ImageEnhance.Contrast(g).enhance(1.9)
    g = g.point(lambda v: 255 if v > 205 else int(v * 0.8))
    g = g.filter(ImageFilter.GaussianBlur(1.2))
    d = ImageDraw.Draw(g)
    for _ in range(int(g.width * g.height / (2600 * K * K))):
        x, y = r.randrange(g.width), r.randrange(g.height)
        d.ellipse((x, y, x + K, y + K), fill=r.randint(60, 150))
    edge_w = int(12 * K)
    for edge in (0, g.width - edge_w):  # the dark edge a copier leaves
        d.rectangle((edge, 0, edge + edge_w, g.height), fill=r.randint(120, 190))
    return g.convert("RGB"), 65


VARIANTS = {"scan": v_scan, "photo": v_photo, "photocopy": v_copy}


# ── documents ────────────────────────────────────────────────────────────────────────────────────

def make_or(i, r):
    v = vehicle(r)
    reg = date(2020, 1, 1) + timedelta(days=r.randint(0, 2100))
    renew = date(reg.year + r.randint(1, 3), reg.month, 28)
    items = [("Cost of plate", 120.00), ("MVUC", float(r.choice([900, 1600, 2400, 3200]))), ("Science Tax", round(r.uniform(20, 90), 2)),
             ("Transfer Fee", 50.00), ("Legal Research Fee", 10.00)]
    fuel_label = {"GAS": "Gas", "GASOLINE": "Gasoline", "DIESEL": "Diesel"}[v["fuel"]]
    return {
        **v, "office": r.choice(OFFICES), "office_tin": f"{r.randint(0, 999999999999):012d}",
        "or_datetime": datetime(reg.year, reg.month, reg.day, r.randint(8, 16), r.randint(0, 59)).strftime("%m/%d/%Y %I:%M %p"),
        "or_number": f"{r.randint(100, 999):04d}-{r.randint(0, 99999999999):012d}", "owner": person(r), "address": address(r),
        "owner_tin": f"{r.randint(0, 999):03d}-{r.randint(0, 999):03d}-{r.randint(0, 999):03d}-{r.randint(0, 999):03d}",
        "client_id": f"{r.randint(10, 99)}-{r.randint(0, 999999):06d}-{r.randint(0, 9999999):07d}",
        "transaction": f"TEST-SAR-{reg:%Y%m%d}-{r.randint(100000, 999999)}", "file_no": f"{r.randint(0, 999999999999999):015d}",
        "classification": r.choice(["Private - (PVT)", "For Hire - (PUV)"]), "vehicle_type": v["denomination"].title(),
        "fuel_label": fuel_label, "valid_until": f"{renew:%m/%Y}", "renewal": f"{renew:%m}/22/{renew.year}-{renew:%m}/31/{renew.year}",
        "breakdown": items, "mode": r.choice(["GCash", "Cash", "Maya"]),
    }


def make_cr(i, r):
    v = vehicle(r)
    reg = date(2008, 1, 1) + timedelta(days=r.randint(0, 6000))
    owner = person(r)
    return {
        **v, "district": r.choice(["Test District Office", "Sample Extension Office"]), "cr_number": f"{r.randint(10000000, 99999999)}-{r.randint(1, 9)}",
        "cr_date": reg.strftime("%m/%d/%Y"), "owner_cr": owner.replace(",", ""), "address": address(r),
        "or_ref": str(r.randint(10000000, 999999999)), "or_amount": round(r.uniform(300, 2500), 2),
    }


def expected(kind, d):
    """What the form should be prefilled with: only the vehicle fields that document actually carries.

    A CR has make, series, chassis, engine and MV file number; an OR has colour and vehicle type but NOT make
    or series. Both have plate, year model and fuel.
    """
    if kind == "CR":
        return {"plate_number": d["plate"], "brand": d["make"].title(), "model": d["series"], "year": d["year"],
                "fuel_type": d["fuel"].title(), "vin": d["chassis"], "engine_number": d["engine"], "mv_file_number": d["mv_file"]}
    return {"plate_number": d["plate"], "year": d["year"], "fuel_type": d["fuel_label"],
            "color": d["color"].title(), "vehicle_type": d["vehicle_type"]}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--count", type=int, default=6, help="documents of each kind")
    ap.add_argument("--seed", type=int, default=2026)
    ap.add_argument("--out", default=str(Path(__file__).parent / "samples"))
    ap.add_argument("--scale", type=float, default=2.5, help="pixels per layout unit: 2.5 gives ~2500 px wide pages, like a 300 dpi A4 scan")
    args = ap.parse_args()

    global K
    K = args.scale

    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    truth = []

    for kind, build, render in (("OR", make_or, render_or), ("CR", make_cr, render_cr)):
        for i in range(1, args.count + 1):
            r = random.Random(f"{args.seed}-{kind}-{i}")
            data = build(i, r)
            clean = render(data, r)
            base = f"{kind.lower()}_{i:02d}"

            clean.save(out / f"{base}_clean.png")
            truth.append({"file": f"{base}_clean.png", "type": kind, "variant": "clean", "expected": expected(kind, data)})

            for name, fn in VARIANTS.items():
                img, quality = fn(clean.copy(), random.Random(f"{args.seed}-{kind}-{i}-{name}"))
                jpeg(img, quality, out / f"{base}_{name}.jpg")
                truth.append({"file": f"{base}_{name}.jpg", "type": kind, "variant": name, "expected": expected(kind, data)})

    (out / "ground_truth.json").write_text(json.dumps(truth, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {len(truth)} images and ground_truth.json to {out}")


if __name__ == "__main__":
    main()
