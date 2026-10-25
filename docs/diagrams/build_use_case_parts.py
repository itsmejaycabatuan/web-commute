#!/usr/bin/env python3
"""
Splits the SmartCommute use-case diagram into page-sized pieces (docs/diagrams/use-case-parts/*.svg),
then renders each to a 2x PNG with headless Chrome so they can be pasted into Google Docs / Word
(neither accepts SVG).

    python3 docs/diagrams/build_use_case_parts.py

Content (actors, use-case names, colours, <<extend>>/<<include>> links) is imported from
build_use_case_diagram.py, so the overview and the parts never disagree.
"""
import math
import os
import shutil
import subprocess
import sys
from xml.sax.saxutils import escape

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import build_use_case_diagram as base  # noqa: E402

UC, ACTORS, UC_REL = base.UC, dict(base.ACTORS), base.UC_REL
ACTORS["auth"] = (["Authenticated", "User"], base.GRAY)
BLUE, TEAL, ORANGE, PURPLE, ROSE, OLIVE, GRAY = (base.BLUE, base.TEAL, base.ORANGE, base.PURPLE,
                                                  base.ROSE, base.OLIVE, base.GRAY)
RX, RY = 135, 19
PITCH = 62
OUT = os.path.join(HERE, "use-case-parts")

EXT = {
    "email": ["Email Service", "(SMTP)"],
    "map": ["Map & Routing", "OpenFreeMap · Photon · OSRM"],
    "paymongo": ["PayMongo Gateway", "(GCash / Maya)"],
    "pusher": ["Push Channel", "(Pusher / Echo)"],
    "scheduler": ["Scheduler", "(cron · schedule:run)"],
    "phone": ["Phone (scanner)", "(OR / CR photos)"],
}

# ------------------------------------------------------------------ part definitions
# groups: (title, colour, [use-case ids]); actors: key -> (side, [targets]); ext: key -> [targets]
PARTS = [
    dict(file="02_account_and_access", title="Account & Access",
         note="Public sign-up / sign-in / recovery, plus the three tasks every signed-in user has.",
         groups=[("Account & Access", BLUE, ["E001", "E011", "E002", "E022"]),
                 ("Common Services (every authenticated user)", GRAY, ["E008", "E025", "E026"])],
         actors={"guest": ("L", ["E001", "E011", "E002", "E022"]),
                 "auth": ("L", ["E008", "E025", "E026"])},
         ext={"email": ["E002", "E011"]}),
    dict(file="03_map_and_commuter_services", title="Map & Commuter Services",
         note="Tracking and trip planning are open to Guests; wallet and payments need a Commuter.",
         groups=[("Map & Commuter Services", TEAL, ["E003", "E004", "E006", "E005", "E007"])],
         actors={"guest": ("L", ["E003", "E004"]),
                 "commuter": ("L", ["E003", "E004", "E006", "E005", "E007"])},
         ext={"pusher": ["E003"], "map": ["E003", "E004"], "paymongo": ["E006"],
              "email": ["E005", "E006"], "scheduler": ["E006"]}),
    dict(file="04_driver_operations", title="Driver Operations",
         note="Shift clock, availability and live location sharing.",
         groups=[("Driver Operations", ORANGE, ["E009", "E010", "E021", "E023"])],
         actors={"driver": ("L", ["E009", "E010", "E021", "E023"])},
         ext={"pusher": ["E021"], "scheduler": ["E010"]}),
    dict(file="05_driver_management", title="Driver Management",
         note="Driver roster, timekeeping ledger and violations. Admin shares the Manage Drivers page.",
         groups=[("Driver Management", PURPLE, ["E013", "E014", "E024", "E012"])],
         actors={"drvmgr": ("L", ["E013", "E014", "E024", "E012"]), "admin": ("R", ["E012"])},
         ext={"email": ["E012", "E014"], "scheduler": ["E012", "E014"]}),
    dict(file="06_fleet_maintenance", title="Fleet Maintenance",
         note="Vehicles, preventive-maintenance schedule and task catalogue. Admin shares the Vehicles page.",
         groups=[("Fleet Maintenance", OLIVE, ["E015", "E016", "E017"])],
         actors={"maint": ("L", ["E015", "E016", "E017"]), "admin": ("R", ["E015"])},
         ext={"email": ["E016"], "scheduler": ["E015", "E016"], "phone": ["E015"]}),
    dict(file="07_administration", title="Administration",
         note="Commuter accounts, fare rates, system reports, staff accounts and the audit trail.",
         groups=[("Administration", ROSE, ["E018", "E019", "E020", "E027", "E028"])],
         actors={"admin": ("L", ["E018", "E019", "E020", "E027", "E028"])},
         ext={"email": ["E020"], "scheduler": ["E020"]}),
]

OVERVIEW_PACKAGES = {  # id -> (name, colour, ids)
    "P_common": ("Common Services", GRAY, ["E008", "E025", "E026"]),
    "P_account": ("Account & Access", BLUE, ["E001", "E011", "E002", "E022"]),
    "P_map": ("Map & Commuter Services", TEAL, ["E003", "E004", "E006", "E005", "E007"]),
    "P_driver": ("Driver Operations", ORANGE, ["E009", "E010", "E021", "E023"]),
    "P_dm": ("Driver Management", PURPLE, ["E013", "E014", "E024", "E012"]),
    "P_admin": ("Administration", ROSE, ["E018", "E019", "E020", "E027", "E028"]),
    "P_fleet": ("Fleet Maintenance", OLIVE, ["E015", "E016", "E017"]),
}
OVERVIEW_ACTORS = {
    "auth": ("L", ["P_common"]),
    "guest": ("L", ["P_account", "P_map"]),
    "commuter": ("L", ["P_map"]),
    "driver": ("L", ["P_driver"]),
    "drvmgr": ("R", ["P_dm"]),
    "admin": ("R", ["P_dm", "P_admin", "P_fleet"]),
    "maint": ("R", ["P_fleet"]),
}


# ------------------------------------------------------------------ drawing
class Canvas:
    def __init__(self):
        self.o = []

    def add(self, s):
        self.o.append(s)

    def line(self, x1, y1, x2, y2, stroke, w=1.4, dash=None, extra=""):
        d = f' stroke-dasharray="{dash}"' if dash else ""
        self.add(f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="{stroke}" stroke-width="{w}"{d} {extra}/>')

    def actor(self, key, x, yc, note=None):
        labels, (fill, stroke) = ACTORS[key]
        top = yc - 32
        self.add(f'<g stroke="{stroke}" stroke-width="2" fill="{fill}" stroke-linecap="round">'
                 f'<circle cx="{x}" cy="{top + 9}" r="9"/><line x1="{x}" y1="{top + 18}" x2="{x}" y2="{top + 46}"/>'
                 f'<line x1="{x - 15}" y1="{top + 28}" x2="{x + 15}" y2="{top + 28}"/>'
                 f'<line x1="{x}" y1="{top + 46}" x2="{x - 12}" y2="{top + 64}"/>'
                 f'<line x1="{x}" y1="{top + 46}" x2="{x + 12}" y2="{top + 64}"/></g>')
        n = 0
        for t in labels:
            self.add(f'<text x="{x}" y="{top + 82 + n * 14}" text-anchor="middle" class="al">{escape(t)}</text>')
            n += 1
        if note:
            self.add(f'<text x="{x}" y="{top + 82 + n * 14}" text-anchor="middle" class="sub" font-style="italic">{escape(note)}</text>')
        return top + 28

    def oval(self, cx, cy, rx, ry, colour, name, ident=None, sub=None):
        fill, stroke = colour
        self.add(f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="#fff" stroke="{stroke}" stroke-width="1.6"/>'
                 f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="{fill}" fill-opacity="0.75"/>')
        if sub:
            self.add(f'<text x="{cx}" y="{cy - 3}" text-anchor="middle" class="un" font-weight="700">{escape(name)}</text>')
            self.add(f'<text x="{cx}" y="{cy + 13}" text-anchor="middle" class="sub">{escape(sub)}</text>')
        else:
            idt = f'<tspan class="ui">{ident}</tspan><tspan class="un" dx="6">' if ident else '<tspan class="un">'
            self.add(f'<text x="{cx}" y="{cy + 4.3}" text-anchor="middle">{idt}{escape(name)}</tspan></text>')


def head(c, w, h, title):
    c.add(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}" '
          f'font-family="\'Segoe UI\', \'Helvetica Neue\', Helvetica, Arial, sans-serif">')
    c.add(f'<title>{escape(title)}</title>')
    c.add('''<style>
.al{font-size:12.5px;font-weight:700;fill:#1b1f23}.ui{font-size:9.5px;font-weight:700;fill:#57606a}
.un{font-size:12.5px;fill:#1b1f23}.grp{font-size:12px;font-weight:700}.rel{font-size:10px;font-style:italic;fill:#444}
.xs{font-size:9px;font-style:italic;fill:#57606a}.xn{font-size:12px;font-weight:700;fill:#1b1f23}.xb{font-size:9.5px;fill:#57606a}
.ttl{font-size:17px;font-weight:700;fill:#1b1f23}.sub{font-size:11px;fill:#57606a}.leg{font-size:11px;fill:#1b1f23}
</style><defs><marker id="open" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto">
<path d="M1,1 L9,5 L1,9" fill="none" stroke="#444" stroke-width="1.3"/></marker></defs>''')
    c.add(f'<rect width="{w}" height="{h}" fill="#fff"/>')


def spread(items, gap):
    """items: list of (key, desired_y) -> {key: y} pushed down so neighbours keep `gap`."""
    out, last = {}, None
    for k, y in sorted(items, key=lambda t: t[1]):
        if last is not None and y < last + gap:
            y = last + gap
        out[k] = y
        last = y
    return out


def legend(c, x, y, used_ext, used_rel):
    items = [("assoc", "Association")]
    if used_ext:
        items.append(("ext", "Association to an external system"))
    if used_rel:
        items.append(("dash", "«extend» / «include» (arrow points at the base / included use case)"))
    cx = x
    for kind, text in items:
        if kind == "assoc":
            c.line(cx, y, cx + 34, y, "#2F6FB5", 1.4)
        elif kind == "ext":
            c.line(cx, y, cx + 34, y, "#57606a", 1.2)
        else:
            c.add(f'<line x1="{cx}" y1="{y}" x2="{cx + 34}" y2="{y}" stroke="#444" stroke-width="1.2" stroke-dasharray="5 3" marker-end="url(#open)"/>')
        c.add(f'<text x="{cx + 42}" y="{y + 4}" class="leg">{escape(text)}</text>')
        cx += 42 + len(text) * 5.6 + 22


# ------------------------------------------------------------------ one single-column part
def build_part(p):
    groups, actors, ext = p["groups"], p["actors"], p["ext"]
    BX, BW = 190, 380
    GX, GW = 210, 340
    CX = GX + GW / 2
    pos, gboxes, colour = {}, [], {}
    y = 104
    for title, col, ids in groups:
        h = 30 + len(ids) * PITCH + 4
        gboxes.append((GX, y, GW, h, title, col))
        for i, uc in enumerate(ids):
            pos[uc] = (CX, y + 30 + PITCH / 2 + i * PITCH)
            colour[uc] = col
        y += h + 22
    bottom = y - 22 + 20
    rel = [r for r in UC_REL if r[0] in pos and r[1] in pos]
    ext_y = spread([(k, sum(pos[t][1] for t in ts) / len(ts)) for k, ts in ext.items()], 76)
    leg_y = bottom + 34
    if ext_y:
        leg_y = max(leg_y, max(ext_y.values()) + 28 + 30)
    wide = bool(ext) and any(side == "R" for side, _ in actors.values())
    if wide:  # room for the right-hand actors moved down below (see "placed" further on)
        for side, ts in actors.values():
            if side == "R":
                leg_y = max(leg_y, sum(pos[t][1] for t in ts) / len(ts) + 95 + 100)
    H = leg_y + 24
    W = 860
    wide = bool(ext) and any(side == "R" for side, _ in actors.values())
    if wide:
        W = 1020
    c = Canvas()
    head(c, W, int(H), f"SmartCommute use-case diagram — {p['title']}")
    c.add(f'<text x="24" y="30" class="ttl">Use-Case Diagram — {escape(p["title"])}</text>')
    c.add(f'<text x="24" y="48" class="sub">{escape(p["note"])}</text>')
    c.add(f'<rect x="{BX}" y="62" width="{BW}" height="{bottom - 62}" rx="10" fill="#FCFCFD" stroke="#30363d" stroke-width="2"/>')
    c.add(f'<text x="{BX + 16}" y="84" class="grp" fill="#30363d">SmartCommute — Web Commute System</text>')
    if len(groups) > 1:
        for gx, gy, gw, gh, title, col in gboxes:
            c.add(f'<rect x="{gx}" y="{gy}" width="{gw}" height="{gh}" rx="8" fill="{col[0]}" fill-opacity="0.45" '
                  f'stroke="{col[1]}" stroke-width="1.2" stroke-dasharray="5 3"/>')
            c.add(f'<text x="{gx + 12}" y="{gy + 19}" class="grp" fill="{col[1]}">{escape(title)}</text>')

    # externals (right of the boundary)
    EXC = 900 if wide else 740
    for k, ts in ext.items():
        ey = ext_y[k]
        for t in ts:
            tx, ty = pos[t]
            c.line(EXC - 85, ey, tx + RX, ty, "#57606a", 1.2)
    for k in ext:
        ey = ext_y[k]
        n = EXT[k]
        c.add(f'<rect x="{EXC - 85}" y="{ey - 28}" width="170" height="56" rx="4" fill="#fff" stroke="#57606a" stroke-width="1.4"/>'
              f'<text x="{EXC}" y="{ey - 12}" text-anchor="middle" class="xs">«external system»</text>'
              f'<text x="{EXC}" y="{ey + 3}" text-anchor="middle" class="xn">{escape(n[0])}</text>'
              f'<text x="{EXC}" y="{ey + 18}" text-anchor="middle" class="xb">{escape(n[1])}</text>')

    # include / extend arrows
    for a, b, st in rel:
        ax, ay = pos[a]
        bx, by = pos[b]
        dx = -70
        k = math.sqrt(1 - (dx / RX) ** 2) * RY
        s = 1 if by > ay else -1
        y1, y2 = ay + s * k, by - s * k
        c.add(f'<line x1="{ax + dx}" y1="{y1:.1f}" x2="{bx + dx}" y2="{y2:.1f}" stroke="#444" stroke-width="1.2" '
              f'stroke-dasharray="5 3" marker-end="url(#open)"/>')
        c.add(f'<text x="{ax + dx + 8}" y="{(y1 + y2) / 2 + 3.5:.1f}" class="rel">«{st}»</text>')

    # actors + associations
    want = {}
    for side in ("L", "R"):
        want[side] = [(k, sum(pos[t][1] for t in ts) / len(ts)) for k, (s, ts) in actors.items() if s == side]
    placed = {}
    for side in ("L", "R"):
        placed.update(spread(want[side], 112))
    if wide:  # keep right-hand actors clear of the lines running out to the external systems
        for k, (side, _) in actors.items():
            if side == "R":
                placed[k] += 95
    for k, (side, ts) in actors.items():
        x = 100 if side == "L" else 700
        arm = c.actor(k, x, placed[k], "(any signed-in role)" if k == "auth" else None)
        sx = x + 15 if side == "L" else x - 15
        for t in ts:
            tx, ty = pos[t]
            ex = tx - RX if side == "L" else tx + RX
            c.line(sx, arm, ex, ty, ACTORS[k][1][1], 1.4)

    for uc, (cx, cy) in pos.items():
        c.oval(cx, cy, RX, RY, colour[uc], UC[uc], uc)
    legend(c, 24, leg_y, bool(ext), bool(rel))
    c.add('</svg>')
    return int(W), int(H), "\n".join(c.o)


# ------------------------------------------------------------------ overview (packages)
def build_overview():
    PX = {"L": 330, "R": 790}
    PR = 150
    PY = 36
    order = {"L": ["P_common", "P_account", "P_map", "P_driver"], "R": ["P_dm", "P_admin", "P_fleet"]}
    pos = {}
    top = 158
    pitch = 118
    for side, ids in order.items():
        for i, k in enumerate(ids):
            pos[k] = (PX[side], top + i * pitch)
    # mirror right column vertically so it is centred against the left one
    shift = (pos["P_driver"][1] - pos["P_fleet"][1]) / 2 + (pos["P_common"][1] - pos["P_dm"][1]) / 2
    for k in order["R"]:
        pos[k] = (pos[k][0], pos[k][1] + shift)
    bottom = pos["P_driver"][1] + 70
    W, H = 1120, int(bottom + 100)
    c = Canvas()
    head(c, W, H, "SmartCommute use-case diagram — overview")
    c.add('<text x="24" y="30" class="ttl">Use-Case Diagram — Overview</text>')
    c.add('<text x="24" y="48" class="sub">Seven feature packages and the roles that use them. Each package is expanded on its own page that follows.</text>')
    c.add(f'<rect x="170" y="62" width="780" height="{bottom - 62}" rx="10" fill="#FCFCFD" stroke="#30363d" stroke-width="2"/>')
    c.add('<text x="186" y="84" class="grp" fill="#30363d">SmartCommute — Web Commute System</text>')
    actor_y = {}
    for side in ("L", "R"):
        items = [(k, sum(pos[t][1] for t in ts) / len(ts)) for k, (s, ts) in OVERVIEW_ACTORS.items() if s == side]
        actor_y.update(spread(items, 112))
    for k, (side, ts) in OVERVIEW_ACTORS.items():
        x = 80 if side == "L" else 1040
        arm = c.actor(k, x, actor_y[k], "(any signed-in role)" if k == "auth" else None)
        sx = x + 15 if side == "L" else x - 15
        for t in ts:
            tx, ty = pos[t]
            c.line(sx, arm, tx - PR if side == "L" else tx + PR, ty, ACTORS[k][1][1], 1.5)
    for k, (cx, cy) in pos.items():
        name, col, ids = OVERVIEW_PACKAGES[k]
        c.oval(cx, cy, PR, PY, col, name, sub=" · ".join(sorted(ids)))
    c.add('<text x="24" y="%d" class="leg">Association</text>' % (H - 24))
    c.line(24 + 70, H - 28, 24 + 104, H - 28, "#2F6FB5", 1.4)
    c.add('<text x="24" y="%d" class="sub" dx="124">Use-case ids under each package point to the UCN sheets in docs/UCN. "Authenticated User" stands for all five signed-in roles.</text>' % (H - 24))
    c.add('</svg>')
    return W, H, "\n".join(c.o)


# ------------------------------------------------------------------ output
def write(name, w, h, svg):
    path = os.path.join(OUT, name + ".svg")
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(svg)
    return path, w, h


def to_png(svg_path, w, h):
    chrome = shutil.which("google-chrome") or shutil.which("google-chrome-stable") or shutil.which("chromium")
    if not chrome:
        print("  (no Chrome found — SVG only)")
        return
    png = svg_path[:-4] + ".png"
    subprocess.run([chrome, "--headless=new", "--no-sandbox", "--disable-gpu", "--hide-scrollbars",
                    "--force-device-scale-factor=2", f"--window-size={w},{h}", f"--screenshot={png}",
                    "file://" + svg_path], check=True, capture_output=True)


if __name__ == "__main__":
    os.makedirs(OUT, exist_ok=True)
    jobs = [("01_overview",) + build_overview()]
    jobs += [(p["file"],) + build_part(p) for p in PARTS]
    for name, w, h, svg in jobs:
        path, w, h = write(name, w, h, svg)
        to_png(path, w, h)
        print("wrote", os.path.relpath(path, HERE), f"{w}x{h}")
