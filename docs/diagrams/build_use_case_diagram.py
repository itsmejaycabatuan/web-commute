#!/usr/bin/env python3
"""
Generates docs/diagrams/use_case_diagram.svg  (UML use-case diagram of SmartCommute).

    python3 docs/diagrams/build_use_case_diagram.py

Edit the DATA section below (groups, actors, relationships) and re-run; the layout is
computed from it. Only the standard library is needed.

Source of truth for the content: docs/UCN/README.md (actors + use-case list) and the
individual UCN_SC_E0xx sheets (primary / secondary actors, <<extend>> / <<include>> flows).
"""
import math
import os
from xml.sax.saxutils import escape

# ----------------------------------------------------------------------------- DATA
# colour = (fill, stroke)
BLUE = ("#E3F0FF", "#2F6FB5")
TEAL = ("#DDF3EC", "#1F8F6B")
ORANGE = ("#FFEBD6", "#C46A12")
PURPLE = ("#EBE3FA", "#6B49B8")
ROSE = ("#FBE0E3", "#B83A4B")
OLIVE = ("#F4EDC6", "#8A7410")
GRAY = ("#ECEFF1", "#5F6B73")

# (use-case id, label)
UC = {
    "E001": "Login",
    "E002": "Create Account",
    "E003": "Track PUJ",
    "E004": "Plan Trip",
    "E005": "Top Up Balance",
    "E006": "Pay Fare",
    "E007": "View Payment History",
    "E008": "Change Password",
    "E009": "Clock In",
    "E010": "Clock Out",
    "E011": "Reset Password",
    "E012": "Manage Drivers",
    "E013": "Manage Timekeeping",
    "E014": "Manage Violations",
    "E015": "Manage Vehicles",
    "E016": "Manage Maintenance Schedule",
    "E017": "Manage Maintenance Tasks",
    "E018": "Manage Commuters",
    "E019": "Manage Fare Rates",
    "E020": "View System Reports",
    "E021": "Share Vehicle Location",
    "E022": "Driver Registration",
    "E023": "Driver Dashboard Views",
    "E024": "Violation Code Management",
    "E026": "Profile & Dashboard Views",
    "E027": "Account Deletion",
    "E028": "Manage Staff Accounts",
    "E029": "View Activity Log",
}

# column, title, colour, [ids in drawing order]
GROUPS = {
    "account": ("L", "Account & Access", BLUE, ["E001", "E011", "E002", "E022"]),
    "commuter": ("L", "Map & Commuter Services", TEAL,
                 ["E003", "E004", "E006", "E005", "E007"]),
    "driverops": ("L", "Driver Operations", ORANGE, ["E009", "E010", "E021", "E023"]),
    "drvmgmt": ("R", "Driver Management", PURPLE, ["E013", "E014", "E024", "E012"]),
    "admin": ("R", "Administration", ROSE, ["E018", "E019", "E020", "E028", "E029"]),
    "fleet": ("R", "Fleet Maintenance", OLIVE, ["E015", "E016", "E017"]),
}
COMMON = ("Common Services (every authenticated user)", GRAY, ["E008", "E026", "E027"])

# actor -> use cases (UML associations)
ASSOC = {
    "guest": ["E001", "E002", "E011", "E022", "E003", "E004"],
    "commuter": ["E003", "E004", "E006", "E005", "E007"],
    "driver": ["E009", "E010", "E021", "E023"],
    "drvmgr": ["E013", "E014", "E024", "E012"],
    "admin": ["E018", "E019", "E020", "E028", "E029", "E012", "E015"],
    "maint": ["E015", "E016", "E017"],
    "auth": ["E008", "E026", "E027"],
}
ACTORS = {  # key: (label lines, colour)
    "guest": (["Guest"], BLUE),
    "commuter": (["Commuter"], TEAL),
    "driver": (["Driver"], ORANGE),
    "drvmgr": (["Driver", "Manager"], PURPLE),
    "admin": (["Admin"], ROSE),
    "maint": (["Maintenance", "Manager"], OLIVE),
}
# roles that specialise "Authenticated User"
ROLES_L = ["commuter", "driver"]
ROLES_R = ["drvmgr", "admin", "maint"]

# (from, to, stereotype): arrow starts at `from`, points at `to`
UC_REL = [
    ("E011", "E001", "extend"),   # Reset Password extends Login            (E011 trigger)
    ("E022", "E002", "extend"),   # Driver Registration extends Create Acct (E002 A3)
    ("E006", "E004", "extend"),   # Pay Fare extends Plan Trip               (E004 A4)
    ("E014", "E024", "include"),  # Manage Violations includes code mgmt     (E014 A1-A4)
]

# secondary (external) actors: key -> (stereotype-free name lines, lane x, row, [use cases])
EXTERNAL = {
    "email": (["Email Service", "(SMTP)"], 580, "A", ["E002", "E011"]),
    "map": (["Map & Routing", "OpenFreeMap · Photon · OSRM"], 670, "B", ["E003", "E004"]),
    "paymongo": (["PayMongo Gateway", "(GCash / Maya)"], 760, "A", ["E006"]),
    "pusher": (["Push Channel", "(Pusher / Echo)"], 850, "B", ["E003", "E021"]),
}

# ----------------------------------------------------------------------------- GEOMETRY
W = 1420
OVAL_RX, OVAL_RY = 135, 19
PITCH = 58
GROUP_HEAD = 30
GROUP_W = 310
GROUP_GAP = 26
COL_X = {"L": 210, "R": 900}
TOP = 238
BOUNDARY = dict(x=175, y=172, w=1070)
LEFT_ACTOR_X, RIGHT_ACTOR_X = 100, 1320
LEFT_TRUNK_X, RIGHT_TRUNK_X = 40, 1385

pos = {}      # use-case id -> (cx, cy)
gbox = {}     # group id -> (x, y, w, h)


def group_h(n):
    return GROUP_HEAD + n * PITCH + 4


def layout():
    for col in ("L", "R"):
        y = TOP
        order = [g for g, v in GROUPS.items() if v[0] == col]
        for g in order:
            ids = GROUPS[g][3]
            h = group_h(len(ids))
            gbox[g] = (COL_X[col], y, GROUP_W, h)
            for i, uc in enumerate(ids):
                pos[uc] = (COL_X[col] + GROUP_W / 2, y + GROUP_HEAD + PITCH / 2 + i * PITCH)
            y += h + GROUP_GAP
    # common row sits under the taller column
    bottom = max(b[1] + b[3] for b in gbox.values())
    cy0 = bottom + GROUP_GAP
    gbox["common"] = (COL_X["L"], cy0, COL_X["R"] + GROUP_W - COL_X["L"], group_h(1))
    mid = COL_X["L"] + (COL_X["R"] + GROUP_W - COL_X["L"]) / 2
    for uc, dx in zip(COMMON[2], (-345, 0, 345)):
        pos[uc] = (mid + dx, cy0 + GROUP_HEAD + PITCH / 2)
    BOUNDARY["h"] = cy0 + group_h(1) + 24 - BOUNDARY["y"]
    return mid


# ----------------------------------------------------------------------------- DRAWING HELPERS
out = []


def add(s):
    out.append(s)


def edge_pt(uc, tx, ty):
    """Point on the ellipse of `uc` in the direction of (tx, ty)."""
    cx, cy = pos[uc]
    dx, dy = tx - cx, ty - cy
    d = math.hypot(dx / OVAL_RX, dy / OVAL_RY) or 1
    return cx + dx / d, cy + dy / d


def line(x1, y1, x2, y2, stroke, w=1.4, dash=None, extra=""):
    d = f' stroke-dasharray="{dash}"' if dash else ""
    add(f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="{stroke}" '
        f'stroke-width="{w}"{d} {extra}/>')


def actor(key, x, yc, side):
    labels, (fill, stroke) = ACTORS[key]
    top = yc - 32
    add(f'<g class="actor" stroke="{stroke}" stroke-width="2" fill="{fill}" stroke-linecap="round">')
    add(f'<circle cx="{x}" cy="{top + 9}" r="9"/>')
    add(f'<line x1="{x}" y1="{top + 18}" x2="{x}" y2="{top + 46}"/>')
    add(f'<line x1="{x - 15}" y1="{top + 28}" x2="{x + 15}" y2="{top + 28}"/>')
    add(f'<line x1="{x}" y1="{top + 46}" x2="{x - 12}" y2="{top + 64}"/>')
    add(f'<line x1="{x}" y1="{top + 46}" x2="{x + 12}" y2="{top + 64}"/>')
    add('</g>')
    for i, t in enumerate(labels):
        add(f'<text x="{x}" y="{top + 82 + i * 14}" text-anchor="middle" class="actor-label">{escape(t)}</text>')
    sx = x + 15 if side == "L" else x - 15
    return sx, top + 28, top + 28  # arm tip x, arm y, torso y


def hop_h(x1, x2, y, stroke, lane_xs, w=1.2):
    """Horizontal line from x1 to x2 at y with small hops over any vertical lane in lane_xs."""
    step = 1 if x2 >= x1 else -1
    pts = sorted([lx for lx in lane_xs if min(x1, x2) < lx < max(x1, x2)], reverse=(step < 0))
    d = f"M{x1:.1f},{y:.1f}"
    for lx in pts:
        d += f" L{lx - 5 * step:.1f},{y:.1f} A5,5 0 0 {1 if step > 0 else 0} {lx + 5 * step:.1f},{y:.1f}"
    d += f" L{x2:.1f},{y:.1f}"
    add(f'<path d="{d}" fill="none" stroke="{stroke}" stroke-width="{w}"/>')


# ----------------------------------------------------------------------------- BUILD
def build():
    mid = layout()
    bx, by, bw, bh = BOUNDARY["x"], BOUNDARY["y"], BOUNDARY["w"], BOUNDARY["h"]
    H = int(by + bh + 215)
    BUS_Y = by + bh + 180

    add(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" '
        f'font-family="\'Segoe UI\', \'Helvetica Neue\', Helvetica, Arial, sans-serif">')
    add('<title>SmartCommute — UML use-case diagram</title>')
    add('<desc>Actors, use cases (UCN_SC_E001–E028) and their relationships for the SmartCommute web system.</desc>')
    add('''<style>
.actor-label{font-size:12.5px;font-weight:700;fill:#1b1f23}
.uc-id{font-size:9.5px;font-weight:700;fill:#57606a}
.uc-name{font-size:12.5px;fill:#1b1f23}
.grp{font-size:12px;font-weight:700}
.rel{font-size:10px;font-style:italic;fill:#444}
.ext-st{font-size:9px;font-style:italic;fill:#57606a}
.ext-name{font-size:12px;font-weight:700;fill:#1b1f23}
.ext-sub{font-size:9.5px;fill:#57606a}
.title{font-size:15px;font-weight:700;fill:#1b1f23}
.sub{font-size:11px;fill:#57606a}
.leg{font-size:11px;fill:#1b1f23}
</style>''')
    add('<defs>'
        '<marker id="open" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto">'
        '<path d="M1,1 L9,5 L1,9" fill="none" stroke="#444" stroke-width="1.3"/></marker>'
        '</defs>')
    add(f'<rect width="{W}" height="{H}" fill="#ffffff"/>')

    # ---- system boundary
    add(f'<rect x="{bx}" y="{by}" width="{bw}" height="{bh}" rx="10" fill="#FCFCFD" stroke="#30363d" stroke-width="2"/>')
    add(f'<text x="{bx + 20}" y="{by + 26}" class="title">SmartCommute — Web Commute System</text>')
    add(f'<text x="{bx + 20}" y="{by + 41}" class="sub">use cases UCN_SC_E001 – E029 (E025 is unassigned)</text>')

    # ---- groups
    def draw_group(box, title, colour):
        x, y, w, h = box
        fill, stroke = colour
        add(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="8" fill="{fill}" fill-opacity="0.45" '
            f'stroke="{stroke}" stroke-width="1.2" stroke-dasharray="5 3"/>')
        add(f'<text x="{x + 12}" y="{y + 19}" class="grp" fill="{stroke}">{escape(title)}</text>')

    for g, (_, title, colour, _) in GROUPS.items():
        draw_group(gbox[g], title, colour)
    draw_group(gbox["common"], COMMON[0], COMMON[1])

    uc_colour = {}
    for _, (_, _, colour, ids) in GROUPS.items():
        for i in ids:
            uc_colour[i] = colour
    for i in COMMON[2]:
        uc_colour[i] = COMMON[1]

    # ---- external actors (lanes + boxes)
    lane_xs = [v[1] for v in EXTERNAL.values()]
    rowy = {"A": 14, "B": 88}
    box_h = 58
    for key, (names, lx, row, targets) in EXTERNAL.items():
        y0 = rowy[row]
        add(f'<rect x="{lx - 80}" y="{y0}" width="160" height="{box_h}" rx="4" fill="#fff" stroke="#57606a" stroke-width="1.4"/>')
        add(f'<text x="{lx}" y="{y0 + 14}" text-anchor="middle" class="ext-st">«external system»</text>')
        add(f'<text x="{lx}" y="{y0 + 30}" text-anchor="middle" class="ext-name">{escape(names[0])}</text>')
        add(f'<text x="{lx}" y="{y0 + 45}" text-anchor="middle" class="ext-sub">{escape(names[1])}</text>')
        lowest = max(pos[t][1] for t in targets)
        line(lx, y0 + box_h, lx, lowest, "#57606a", 1.2)
        for t in targets:
            cx, cy = pos[t]
            hop_h(lx, cx + OVAL_RX, cy, "#57606a", [x for x in lane_xs if x != lx])

    # ---- use-case relationships (<<include>>/<<extend>>)
    for a, b, st in UC_REL:
        ax, ay = pos[a]
        bx_, by_ = pos[b]
        dx = -70
        k = math.sqrt(max(0.0, 1 - (dx / OVAL_RX) ** 2)) * OVAL_RY
        sgn = 1 if by_ > ay else -1
        y1, y2 = ay + sgn * k, by_ - sgn * k
        add(f'<line x1="{ax + dx}" y1="{y1:.1f}" x2="{bx_ + dx}" y2="{y2:.1f}" stroke="#444" stroke-width="1.2" '
            f'stroke-dasharray="5 3" marker-end="url(#open)"/>')
        add(f'<text x="{ax + dx + 8}" y="{(y1 + y2) / 2 + 3.5:.1f}" class="rel">«{st}»</text>')

    # ---- actors + associations
    actor_anchor = {}
    # vertical placement
    def mean_y(ids):
        return sum(pos[i][1] for i in ids) / len(ids)

    yc = {
        "guest": gbox["account"][1] + gbox["account"][3] + 10,
        "commuter": mean_y(GROUPS["commuter"][3]),
        "driver": mean_y(GROUPS["driverops"][3]),
        "drvmgr": mean_y(GROUPS["drvmgmt"][3]),
        "admin": mean_y(GROUPS["admin"][3]),
        "maint": mean_y(GROUPS["fleet"][3]),
    }
    for k, (labels, colour) in ACTORS.items():
        side = "L" if k in ("guest", "commuter", "driver") else "R"
        x = LEFT_ACTOR_X if side == "L" else RIGHT_ACTOR_X
        sx, sy, torso = actor(k, x, yc[k], side)
        actor_anchor[k] = (sx, sy, torso, side, x)
        for uc in ASSOC[k]:
            # end on the oval's outer extreme so the line never runs behind a neighbouring oval
            ex = pos[uc][0] + (-OVAL_RX if side == "L" else OVAL_RX)
            line(sx, sy, ex, pos[uc][1], colour[1], 1.4)

    # ---- Authenticated User (abstract) + generalisation tree
    au_x, au_yc = mid, by + bh + 100
    au_top = au_yc - 32
    # associations to the common use cases (drawn before the figure)
    for uc in ASSOC["auth"]:
        ex, ey = edge_pt(uc, au_x, au_top + 9)
        line(au_x, au_top + 4, ex, ey, GRAY[1], 1.4)
    add(f'<g stroke="{GRAY[1]}" stroke-width="2" fill="{GRAY[0]}" stroke-linecap="round">'
        f'<circle cx="{au_x}" cy="{au_top + 9}" r="9"/>'
        f'<line x1="{au_x}" y1="{au_top + 18}" x2="{au_x}" y2="{au_top + 46}"/>'
        f'<line x1="{au_x - 15}" y1="{au_top + 28}" x2="{au_x + 15}" y2="{au_top + 28}"/>'
        f'<line x1="{au_x}" y1="{au_top + 46}" x2="{au_x - 12}" y2="{au_top + 64}"/>'
        f'<line x1="{au_x}" y1="{au_top + 46}" x2="{au_x + 12}" y2="{au_top + 64}"/></g>')
    add(f'<text x="{au_x + 24}" y="{au_top + 34}" class="actor-label" fill="#1b1f23">Authenticated User</text>')
    add(f'<text x="{au_x + 24}" y="{au_top + 49}" class="sub" font-style="italic">abstract actor — any signed-in role</text>')
    feet = au_top + 64
    tri_base = feet + 20
    add(f'<path d="M{au_x},{feet + 2} L{au_x - 9},{tri_base} L{au_x + 9},{tri_base} Z" fill="#fff" stroke="#444" stroke-width="1.4"/>')
    gen = "#444"
    line(au_x, tri_base, au_x, BUS_Y, gen, 1.4)
    line(LEFT_TRUNK_X, BUS_Y, RIGHT_TRUNK_X, BUS_Y, gen, 1.4)
    for k in ROLES_L:
        _, _, torso, _, x = actor_anchor[k]
        line(x - 15, torso, LEFT_TRUNK_X, torso, gen, 1.4)
    for k in ROLES_R:
        _, _, torso, _, x = actor_anchor[k]
        line(x + 15, torso, RIGHT_TRUNK_X, torso, gen, 1.4)
    ly = [actor_anchor[k][2] for k in ROLES_L]
    line(LEFT_TRUNK_X, min(ly), LEFT_TRUNK_X, BUS_Y, gen, 1.4)
    ry = [actor_anchor[k][2] for k in ROLES_R]
    line(RIGHT_TRUNK_X, min(ry), RIGHT_TRUNK_X, BUS_Y, gen, 1.4)

    # ---- use-case ovals (on top of the lines)
    for uc, (cx, cy) in pos.items():
        fill, stroke = uc_colour[uc]
        add(f'<ellipse cx="{cx}" cy="{cy}" rx="{OVAL_RX}" ry="{OVAL_RY}" fill="#fff" stroke="{stroke}" stroke-width="1.6"/>')
        add(f'<ellipse cx="{cx}" cy="{cy}" rx="{OVAL_RX}" ry="{OVAL_RY}" fill="{fill}" fill-opacity="0.75" stroke="none"/>')
        add(f'<text x="{cx}" y="{cy + 4.3}" text-anchor="middle"><tspan class="uc-id">{uc}</tspan>'
            f'<tspan class="uc-name" dx="6">{escape(UC[uc])}</tspan></text>')

    # ---- legend
    lx0, ly0 = 70, by + bh + 28
    add(f'<rect x="{lx0}" y="{ly0}" width="420" height="132" rx="6" fill="#fff" stroke="#c9d1d9"/>')
    add(f'<text x="{lx0 + 12}" y="{ly0 + 18}" class="grp" fill="#1b1f23">Legend</text>')
    rows = [
        ("assoc", "Association — actor takes part in the use case"),
        ("ext", "Association to an external system the use case depends on"),
        ("dash", "«extend» / «include» — arrow points at the base / included use case"),
        ("gen", "Generalisation — role is a specialisation of Authenticated User"),
    ]
    for i, (kind, text) in enumerate(rows):
        y = ly0 + 40 + i * 22
        x1, x2 = lx0 + 14, lx0 + 52
        if kind == "assoc":
            line(x1, y, x2, y, "#2F6FB5", 1.4)
        elif kind == "ext":
            line(x1, y, x2, y, "#57606a", 1.2)
        elif kind == "dash":
            add(f'<line x1="{x1}" y1="{y}" x2="{x2}" y2="{y}" stroke="#444" stroke-width="1.2" stroke-dasharray="5 3" marker-end="url(#open)"/>')
        else:
            line(x1, y, x2 - 10, y, "#444", 1.4)
            add(f'<path d="M{x2},{y} L{x2 - 10},{y - 5} L{x2 - 10},{y + 5} Z" fill="#fff" stroke="#444" stroke-width="1.3"/>')
        add(f'<text x="{x2 + 12}" y="{y + 4}" class="leg">{escape(text)}</text>')

    add('</svg>')
    return "\n".join(out)


if __name__ == "__main__":
    svg = build()
    target = os.path.join(os.path.dirname(os.path.abspath(__file__)), "use_case_diagram.svg")
    with open(target, "w", encoding="utf-8") as fh:
        fh.write(svg)
    print("wrote", target, f"({len(svg):,} bytes)")
