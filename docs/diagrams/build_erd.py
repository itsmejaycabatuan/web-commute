#!/usr/bin/env python3
"""
Generates the SmartCommute entity-relationship diagrams with Graphviz:

    docs/diagrams/erd_full.{svg,png}      every table the migrations create
    docs/diagrams/erd_summary.{svg,png}   main entities only, key attributes only

    python3 docs/diagrams/build_erd.py          (needs the `dot` binary)

SCHEMA below is the final state of database/migrations/*.php (later ALTER migrations applied,
dropped tables such as vehicle_maintenance_logs left out). Re-check it when a migration is added.

Column spec: "name:type[:flags]"  flags = PK, FK, UQ, N (nullable).
Notation is crow's foot:  ||  exactly one   |o  zero or one   >o  zero or many.
Dashed line = logical link with no database foreign key (polymorphic or plain id column).
"""
import html
import os
import shutil
import subprocess
import sys

HERE = os.path.dirname(os.path.abspath(__file__))

C = {  # group -> (header fill, border, body tint)
    "id": ("#2F6FB5", "#2F6FB5", "#EEF5FD"),
    "pay": ("#1F8F6B", "#1F8F6B", "#EAF7F2"),
    "fare": ("#0E7C86", "#0E7C86", "#E7F5F6"),
    "drv": ("#6B49B8", "#6B49B8", "#F2EEFB"),
    "flt": ("#8A7410", "#8A7410", "#F8F4DC"),
    "trk": ("#C46A12", "#C46A12", "#FFF3E6"),
    "sys": ("#5F6B73", "#5F6B73", "#F3F5F6"),
}
GROUP_TITLE = {
    "id": "Identity & access", "pay": "Wallet, payments & ride QR", "fare": "Fares",
    "drv": "Drivers & violations", "flt": "Fleet & maintenance", "trk": "Live tracking",
    "sys": "Framework",
}

SCHEMA = {
    # ---------------------------------------------------------------- identity & access
    "users": ("id", [
        "id:bigint:PK", "email:varchar:UQ", "email_verified_at:timestamp:N", "password:varchar",
        "is_suspended:boolean", "suspended_at:timestamp:N", "anonymised_at:timestamp:N", "remember_token:varchar:N",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "user_preferences": ("id", [
        "id:bigint:PK", "user_id:bigint:FK", "theme:varchar", "font_size:int", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "roles": ("id", [
        "id:bigint:PK", "name:varchar", "guard_name:varchar", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "permissions": ("id", [
        "id:bigint:PK", "name:varchar", "guard_name:varchar", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "model_has_roles": ("id", [
        "role_id:bigint:PK,FK", "model_type:varchar:PK", "model_id:bigint:PK"
    ]),
    "model_has_permissions": ("id", [
        "permission_id:bigint:PK,FK", "model_type:varchar:PK", "model_id:bigint:PK"
    ]),
    "role_has_permissions": ("id", [
        "permission_id:bigint:PK,FK", "role_id:bigint:PK,FK"
    ]),
    "personal_access_tokens": ("id", [
        "id:bigint:PK", "tokenable_type:varchar", "tokenable_id:bigint", "name:varchar", "token:varchar:UQ",
        "abilities:text:N", "last_used_at:timestamp:N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "sessions": ("id", [
        "id:varchar:PK", "user_id:bigint:N", "ip_address:varchar:N", "user_agent:text:N", "payload:longtext",
        "last_activity:int"
    ]),
    "password_resets": ("id", [
        "email:varchar", "token:varchar", "created_at:timestamp:N"
    ]),
    "email_verification_codes": ("id", [
        "id:bigint:PK", "user_id:bigint:FK,UQ", "code_hash:varchar", "attempts:int", "expires_at:timestamp",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- wallet & payments, ride QR
    "wallets": ("pay", [
        "id:bigint:PK", "user_id:bigint:FK", "balance:decimal", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "topup_histories": ("pay", [
        "id:bigint:PK", "user_id:bigint:FK", "wallet_id:bigint:FK", "amount_added:decimal", "payment_method:varchar",
        "status:varchar", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "payments": ("pay", [
        "id:bigint:PK", "paid_by:bigint:FK", "transaction_id:varchar:UQ", "starting_point:varchar",
        "destination:varchar", "total_distance:decimal", "is_discounted:boolean", "payment_method:varchar",
        "status:varchar", "paymongo_payment_intent_id:varchar:N", "paymongo_checkout_session_id:varchar:N",
        "paymongo_reference_id:varchar:N", "price:decimal", "paid_at:timestamp:N", "failed_at:timestamp:N",
        "failure_message:text:N", "created_at:timestamp:N", "updated_at:timestamp:N", "vehicle_id:bigint:FK,N",
        "driver_id:bigint:FK,N"
    ]),
    "qr_codes": ("pay", [
        "id:bigint:PK", "token:varchar:UQ", "is_active:boolean", "created_by:bigint:FK,N", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "qr_scans": ("pay", [
        "id:bigint:PK", "user_id:bigint:FK", "qr_code_id:bigint:FK,N", "vehicle_id:bigint:FK,N",
        "driver_id:bigint:FK,N", "matched_distance_m:int:N", "located_at:timestamp:N", "scanned_at:timestamp",
        "used_at:timestamp:N", "payment_id:bigint:FK,N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- fares
    "fares": ("fare", [
        "id:bigint:PK", "location:varchar", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "fare_rates": ("fare", [
        "id:bigint:PK", "fare_id:bigint:FK", "km:int", "regular:double", "discount:double", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- drivers & violations
    "drivers": ("drv", [
        "id:bigint:PK", "user_id:bigint:FK", "driver_code:varchar:UQ,N", "name:varchar:N", "expiration_date:date:N",
        "contact_info:varchar:N", "license_number:varchar:UQ,N", "license_code:varchar:N",
        "license_image_path:varchar:N", "license_status:varchar:N", "is_approved:boolean", "is_rejected:boolean",
        "is_suspended:boolean", "suspended_at:timestamp:N", "suspension_reason:varchar:N",
        "inactivity_warned_at:timestamp:N", "reactivated_at:timestamp:N", "status:varchar:N",
        "license_image_data:mediumtext:N", "license_image_mime:varchar:N", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "time_keepings": ("drv", [
        "id:bigint:PK", "driver_id:bigint:FK", "vehicle_id:bigint:FK,N", "date:date", "time_in:varchar:N",
        "time_out:varchar:N", "hours_worked:decimal:N", "overtime_hours:decimal:N", "auto_closed_at:timestamp:N",
        "reviewed_at:timestamp:N", "sick:int:N", "vacation:int:N", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "violation_codes": ("drv", [
        "id:bigint:PK", "code:varchar", "violation_name:varchar", "first_offense:decimal", "second_offense:decimal",
        "third_offense:decimal", "fourth_offense:decimal:N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "violation_logs": ("drv", [
        "id:bigint:PK", "user_id:bigint:FK", "vc_id:bigint:FK", "violation_instance:varchar",
        "violation_fine:decimal", "payment_status:varchar", "paid_at:timestamp:N", "additional_penalties:varchar:N",
        "date_of_violation:date", "time_of_violation:time", "place_of_violation:varchar", "remarks:varchar:N",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- fleet & maintenance
    "vehicles": ("flt", [
        "id:bigint:PK", "year:int:N", "brand:varchar:N", "model:varchar:N", "plate_number:varchar:N",
        "status:varchar:N", "fuel_type:varchar:N", "tank_capacity:varchar:N", "vin:varchar:N", "location:varchar:N",
        "acquisition_date:date:N", "exp_disposal_date:date:N", "created_at:timestamp:N", "updated_at:timestamp:N",
        "is_active:boolean"
    ]),
    "driver_vehicle": ("flt", [
        "id:bigint:PK", "vehicle_id:bigint:FK", "driver_id:bigint:FK,UQ", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "vehicle_duty_days": ("flt", [
        "id:bigint:PK", "vehicle_id:bigint:FK", "driver_id:bigint:FK", "duty_date:date", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "driver_vacancies": ("flt", [
        "id:bigint:PK", "vehicle_id:bigint:FK", "former_driver_id:bigint:FK,N", "reason:varchar",
        "target_drivers:int", "opened_at:timestamp", "resolved_at:timestamp:N", "resolution:varchar:N",
        "replacement_driver_id:bigint:FK,N", "last_notified_at:timestamp:N", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "maintenance_tasks": ("flt", [
        "id:bigint:PK", "tasks_performed:varchar", "miles_between_service:int:N", "months_between_service:int:N",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "preventive_maintenances": ("flt", [
        "id:bigint:PK", "vehicle_id:bigint:FK", "task_id:bigint:FK", "last_service_odo:int:N",
        "last_service_date:date:N", "last_service_cost:decimal:N", "comments:varchar:N", "created_at:timestamp:N",
        "updated_at:timestamp:N"
    ]),
    "vehicle_maintenance_logs": ("flt", [
        "id:bigint:PK", "maintenance_id:bigint:FK,N", "vehicle_id:bigint:FK", "maintenance_task_id:bigint:FK",
        "service_date:date", "mileage_at_service:int", "performed_by:varchar:N", "cost:decimal",
        "invoice_number:varchar:N", "remarks:text:N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "vehicle_scan_sessions": ("flt", [
        "id:bigint:PK", "token:varchar:UQ", "user_id:bigint:FK", "status:varchar", "expires_at:timestamp",
        "files:longtext:N", "result:longtext:N", "error:varchar:N", "uploaded_at:timestamp:N",
        "consumed_at:timestamp:N", "vehicle_id:bigint:FK,N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- live tracking
    "vehicle_locations": ("trk", [
        "id:bigint:PK", "vehicle_id:varchar", "user_id:bigint:FK,N", "latitude:decimal", "longitude:decimal",
        "accuracy:decimal:N", "broadcast_timestamp:bigint:N", "speed:decimal:N", "last_update:timestamp",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "vehicle_location_histories": ("trk", [
        "id:bigint:PK", "vehicle_location_id:bigint:FK", "user_id:bigint:FK,N", "distance_from_last_pos:decimal",
        "latitude:decimal", "longitude:decimal", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "dev_markers": ("trk", [
        "id:bigint:PK", "user_id:bigint:FK", "name:varchar", "lat:decimal", "lng:decimal", "status:varchar",
        "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    # ---------------------------------------------------------------- framework
    "activity_log": ("sys", [
        "id:bigint:PK", "log_name:varchar:N", "description:text", "subject_type:varchar:N", "event:varchar:N",
        "subject_id:bigint:N", "causer_type:varchar:N", "causer_id:bigint:N", "properties:longtext:N",
        "batch_uuid:varchar:N", "created_at:timestamp:N", "updated_at:timestamp:N"
    ]),
    "failed_jobs": ("sys", [
        "id:bigint:PK", "uuid:varchar:UQ", "connection:text", "queue:text", "payload:longtext", "exception:longtext",
        "failed_at:timestamp"
    ]),
}

# (parent, parent_col, child, child_col, parent_end, child_end, logical?)
# ends: "1" exactly one, "01" zero-or-one, "0N" zero-or-many
REL = [
    ('users', 'id', 'user_preferences', 'user_id', '1', '0N', False),
    ('users', 'id', 'model_has_roles', 'model_id', '1', '0N', True),
    ('users', 'id', 'model_has_permissions', 'model_id', '1', '0N', True),
    ('users', 'id', 'personal_access_tokens', 'tokenable_id', '1', '0N', True),
    ('users', 'id', 'sessions', 'user_id', '01', '0N', True),
    ('users', 'email', 'password_resets', 'email', '1', '0N', True),
    ('users', 'id', 'email_verification_codes', 'user_id', '1', '01', False),
    ('users', 'id', 'wallets', 'user_id', '1', '0N', False),
    ('users', 'id', 'topup_histories', 'user_id', '1', '0N', False),
    ('users', 'id', 'payments', 'paid_by', '1', '0N', False),
    ('users', 'id', 'qr_codes', 'created_by', '01', '0N', False),
    ('users', 'id', 'qr_scans', 'user_id', '1', '0N', False),
    ('users', 'id', 'drivers', 'user_id', '1', '0N', False),
    ('users', 'id', 'violation_logs', 'user_id', '1', '0N', False),
    ('users', 'id', 'vehicle_scan_sessions', 'user_id', '1', '0N', False),
    ('users', 'id', 'vehicle_locations', 'user_id', '01', '0N', False),
    ('users', 'id', 'vehicle_location_histories', 'user_id', '01', '0N', False),
    ('users', 'id', 'dev_markers', 'user_id', '1', '0N', False),
    ('users', 'id', 'activity_log', 'causer_id', '01', '0N', True),
    ('roles', 'id', 'model_has_roles', 'role_id', '1', '0N', False),
    ('roles', 'id', 'role_has_permissions', 'role_id', '1', '0N', False),
    ('permissions', 'id', 'model_has_permissions', 'permission_id', '1', '0N', False),
    ('permissions', 'id', 'role_has_permissions', 'permission_id', '1', '0N', False),
    ('wallets', 'id', 'topup_histories', 'wallet_id', '1', '0N', False),
    ('payments', 'id', 'qr_scans', 'payment_id', '01', '0N', False),
    ('qr_codes', 'id', 'qr_scans', 'qr_code_id', '01', '0N', False),
    ('fares', 'id', 'fare_rates', 'fare_id', '1', '0N', False),
    ('drivers', 'id', 'payments', 'driver_id', '01', '0N', False),
    ('drivers', 'id', 'qr_scans', 'driver_id', '01', '0N', False),
    ('drivers', 'id', 'time_keepings', 'driver_id', '1', '0N', False),
    ('drivers', 'id', 'driver_vehicle', 'driver_id', '1', '01', False),
    ('drivers', 'id', 'vehicle_duty_days', 'driver_id', '1', '0N', False),
    ('drivers', 'id', 'driver_vacancies', 'former_driver_id', '01', '0N', False),
    ('drivers', 'id', 'driver_vacancies', 'replacement_driver_id', '01', '0N', False),
    ('violation_codes', 'id', 'violation_logs', 'vc_id', '1', '0N', False),
    ('vehicles', 'id', 'payments', 'vehicle_id', '01', '0N', False),
    ('vehicles', 'id', 'qr_scans', 'vehicle_id', '01', '0N', False),
    ('vehicles', 'id', 'time_keepings', 'vehicle_id', '01', '0N', False),
    ('vehicles', 'id', 'driver_vehicle', 'vehicle_id', '1', '0N', False),
    ('vehicles', 'id', 'vehicle_duty_days', 'vehicle_id', '1', '0N', False),
    ('vehicles', 'id', 'driver_vacancies', 'vehicle_id', '1', '0N', False),
    ('vehicles', 'id', 'preventive_maintenances', 'vehicle_id', '1', '0N', False),
    ('vehicles', 'id', 'vehicle_maintenance_logs', 'vehicle_id', '1', '0N', False),
    ('vehicles', 'id', 'vehicle_scan_sessions', 'vehicle_id', '01', '0N', False),
    ('vehicles', 'id', 'vehicle_locations', 'vehicle_id', '1', '0N', True),
    ('maintenance_tasks', 'id', 'preventive_maintenances', 'task_id', '1', '0N', False),
    ('maintenance_tasks', 'id', 'vehicle_maintenance_logs', 'maintenance_task_id', '1', '0N', False),
    ('preventive_maintenances', 'id', 'vehicle_maintenance_logs', 'maintenance_id', '01', '0N', False),
    ('vehicle_locations', 'id', 'vehicle_location_histories', 'vehicle_location_id', '1', '0N', False),
]
ARROW = {"1": "teetee", "01": "teeodot", "0N": "crowodot"}

# ----------------------------------------------------------------------------- summary
# entity key -> (title, table name, group, [columns to show], )
SUMMARY = {
    "users": ("User", "users", "id", ["id", "email", "password", "email_verified_at", "is_suspended", "anonymised_at"]),
    "roles": ("Role", "roles (via model_has_roles)", "id", ["id", "name"]),
    "wallets": ("Wallet", "wallets", "pay", ["id", "user_id", "balance"]),
    "topup_histories": ("Top-up History", "topup_histories", "pay",
                        ["id", "user_id", "wallet_id", "amount_added", "payment_method", "status"]),
    "payments": ("Payment", "payments", "pay", ["id", "paid_by", "transaction_id", "starting_point", "destination",
                                                 "total_distance", "price", "payment_method", "status", "paid_at"]),
    "fares": ("Fare", "fares", "fare", ["id", "location"]),
    "fare_rates": ("Fare Rate", "fare_rates", "fare", ["id", "fare_id", "km", "regular", "discount"]),
    "drivers": ("Driver", "drivers", "drv", ["id", "user_id", "driver_code", "license_number", "expiration_date",
                                              "status", "is_approved", "is_suspended"]),
    "time_keepings": ("Time Keeping", "time_keepings", "drv", ["id", "driver_id", "date", "time_in", "time_out",
                                                                 "hours_worked", "overtime_hours"]),
    "violation_codes": ("Violation Code", "violation_codes", "drv", ["id", "code", "violation_name", "first_offense"]),
    "violation_logs": ("Violation Log", "violation_logs", "drv", ["id", "user_id", "vc_id", "violation_fine",
                                                                    "date_of_violation", "place_of_violation"]),
    "driver_vehicle": ("Driver–Vehicle", "driver_vehicle", "flt", ["id", "driver_id", "vehicle_id"]),
    "vehicles": ("Vehicle", "vehicles", "flt", ["id", "plate_number", "brand", "model", "year", "status", "is_active"]),
    "maintenance_tasks": ("Maintenance Task", "maintenance_tasks", "flt",
                          ["id", "tasks_performed", "miles_between_service", "months_between_service"]),
    "preventive_maintenances": ("Preventive Maintenance", "preventive_maintenances", "flt",
                                ["id", "vehicle_id", "task_id", "last_service_date", "last_service_odo",
                                 "last_service_cost"]),
    "vehicle_locations": ("Vehicle Location", "vehicle_locations", "trk",
                          ["id", "vehicle_id", "user_id", "latitude", "longitude", "last_update"]),
}
SUM_REL = [  # parent, child, label
    ("users", "wallets", "owns"),
    ("wallets", "topup_histories", "receives"),
    ("users", "topup_histories", "requests"),
    ("users", "payments", "pays"),
    ("users", "drivers", "is profile of"),
    ("drivers", "time_keepings", "records"),
    ("drivers", "driver_vehicle", "holds"),
    ("vehicles", "driver_vehicle", "is driven by"),
    ("users", "violation_logs", "is cited in"),
    ("violation_codes", "violation_logs", "classifies"),
    ("vehicles", "preventive_maintenances", "is serviced by"),
    ("maintenance_tasks", "preventive_maintenances", "is performed in"),
    ("vehicles", "vehicle_locations", "reports"),
    ("users", "vehicle_locations", "broadcasts"),
    ("fares", "fare_rates", "contains"),
]


# ----------------------------------------------------------------------------- DOT
def e(s):
    return html.escape(s, quote=True)


def node(table, group, cols, title=None, subtitle=None, show=None):
    head, border, tint = C[group]
    rows = []
    shown = [c for c in cols if show is None or c.split(":")[0] in show]
    for spec in shown:
        name, typ, *fl = spec.split(":")
        flags = (fl[0] if fl else "").split(",")
        keys = [k for k in ("PK", "FK", "UQ") if k in flags]
        marker = ",".join(keys)
        nullable = "N" in flags
        label = f"<I>{e(name)}</I>" if nullable and show is None else e(name)
        if "PK" in flags:
            label = f"<B>{e(name)}</B>"
        rows.append(
            f'<TR><TD ALIGN="LEFT" WIDTH="26" PORT="{e(name)}_l"><FONT POINT-SIZE="8" COLOR="#57606a">{marker or "&nbsp;"}</FONT></TD>'
            f'<TD ALIGN="LEFT">{label}</TD>'
            f'<TD ALIGN="LEFT" PORT="{e(name)}_r"><FONT POINT-SIZE="8" COLOR="#57606a">{e(typ)}{"" if not nullable or show else "?"}</FONT></TD></TR>')
    t = title or table
    sub = f'<BR/><FONT POINT-SIZE="8" COLOR="#E6EDF3">{e(subtitle)}</FONT>' if subtitle else ""
    return (f'"{table}" [label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0" CELLPADDING="3" '
            f'COLOR="{border}" BGCOLOR="{tint}">'
            f'<TR><TD COLSPAN="3" BGCOLOR="{head}"><FONT COLOR="white" POINT-SIZE="11"><B>{e(t)}</B></FONT>{sub}</TD></TR>'
            + "".join(rows) + '</TABLE>>];')


def edge(parent, pcol, child, ccol, pend, cend, logical, label=None, side="R"):
    """side R: child sits right of parent; L: left of it; S: same column."""
    pp, cp = {"R": ("r:e", "l:w"), "L": ("l:w", "r:e"), "S": ("l:w", "l:w")}[side]
    style = ' style="dashed"' if logical else ""
    lab = f' label="{e(label)}" fontsize="9" fontcolor="#444444"' if label else ""
    return (f'"{parent}":"{pcol}_{pp.split(":")[0]}":{pp.split(":")[1]} -> "{child}":"{ccol}_{cp.split(":")[0]}":{cp.split(":")[1]} '
            f'[dir=both arrowtail={ARROW[pend]} arrowhead={ARROW[cend]} color="#444444" arrowsize=0.9{style}{lab}];')


# ----------------------------------------------------------------------------- grid layout
# Each inner list is one column (left to right), tables top to bottom. Parents sit left of children
# wherever the schema allows, so most lines run left -> right.
GRID_FULL = [
    ["roles", "permissions", "failed_jobs"],
    ["model_has_roles", "model_has_permissions", "role_has_permissions", "user_preferences",
     "sessions", "personal_access_tokens", "password_resets", "email_verification_codes", "activity_log"],
    ["violation_codes", "users", "qr_codes"],
    ["violation_logs", "wallets", "payments", "drivers"],
    ["topup_histories", "time_keepings", "driver_vehicle", "vehicle_duty_days", "driver_vacancies",
     "maintenance_tasks", "fares"],
    ["vehicles", "fare_rates"],
    ["preventive_maintenances", "vehicle_maintenance_logs", "vehicle_locations", "vehicle_scan_sessions", "qr_scans"],
    ["vehicle_location_histories", "dev_markers"],
]
GRID_SUMMARY = [
    ["roles", "users", "violation_codes"],
    ["wallets", "payments", "drivers", "violation_logs"],
    ["topup_histories", "time_keepings", "driver_vehicle", "maintenance_tasks", "fares"],
    ["vehicles", "fare_rates"],
    ["preventive_maintenances", "vehicle_locations"],
]


def node_sizes(node_defs):
    """First pass: let Graphviz measure each HTML table (points)."""
    src = 'digraph G { node [shape=plain, fontname="Helvetica", fontsize=10];\n' + "\n".join(node_defs.values()) + "}"
    res = subprocess.run([shutil.which("dot"), "-Tjson"], input=src, capture_output=True, text=True, check=True)
    import json
    return {o["name"]: (float(o["width"]) * 72, float(o["height"]) * 72) for o in json.loads(res.stdout)["objects"]}


def place(grid, sizes, col_gap, row_gap, center_single=True):
    """-> {key: (x, y)} centre points in Graphviz coordinates (y grows upward) + (width, height)."""
    colw = [max(sizes[k][0] for k in col) for col in grid]
    heights = [sum(sizes[k][1] for k in col) + row_gap * (len(col) - 1) for col in grid]
    total_h = max(heights) + 150            # head-room for the title / legend
    x, pos = 40.0, {}
    for col, w, h in zip(grid, colw, heights):
        cx = x + w / 2
        y = total_h - 110 - ((max(heights) - h) / 2.6 if center_single and len(col) == 1 else 0)
        for k in col:
            ny = y - sizes[k][1] / 2
            pos[k] = (cx, ny)
            y -= sizes[k][1] + row_gap
        x += w + col_gap
    return pos, (x - col_gap + 40, total_h)


def legend_node(groups, extra_note):
    rows = "".join(
        f'<TR><TD WIDTH="14" BGCOLOR="{C[g][0]}"> </TD><TD ALIGN="LEFT">{e(GROUP_TITLE[g])}</TD></TR>' for g in groups)
    return ('"legend" [label=<<TABLE BORDER="1" COLOR="#c9d1d9" CELLBORDER="0" CELLSPACING="2" CELLPADDING="3" BGCOLOR="white">'
            '<TR><TD COLSPAN="2" ALIGN="LEFT"><B>Legend</B></TD></TR>' + rows +
            f'<TR><TD COLSPAN="2" ALIGN="LEFT"><FONT POINT-SIZE="9" COLOR="#57606a">{extra_note}</FONT></TD></TR>'
            '</TABLE>>];')


def render_grid(name, grid, node_defs, edges_fn, title_html, col_gap, row_gap, legend):
    sizes = node_sizes({**node_defs})
    pos, (W, H) = place(grid, sizes, col_gap, row_gap)
    col_of = {k: i for i, col in enumerate(grid) for k in col}
    out = [f'digraph ERD {{ graph [splines=true, bgcolor="white", pad=0.2, outputorder=edgesfirst, '
           f'bb="0,0,{W:.0f},{H:.0f}"]; node [shape=plain, fontname="Helvetica", fontsize=10]; '
           f'edge [fontname="Helvetica"];']
    for k, d in node_defs.items():
        x, y = pos[k]
        out.append(d[:-2] + f' pos="{x:.1f},{y:.1f}"];')
    out.append(f'"title" [shape=plaintext label=<{title_html}> pos="{W / 2:.1f},{H - 40:.1f}"];')
    lx, ly = legend[0]
    out.append(legend_node(*legend[1])[:-2] + f' pos="{lx(W):.1f},{ly(H):.1f}"];')
    out.extend(edges_fn(col_of))
    out.append("}")
    src = os.path.join(HERE, name + ".dot")
    with open(src, "w", encoding="utf-8") as fh:
        fh.write("\n".join(out))
    neato = shutil.which("neato")
    for fmt, extra in (("svg", []), ("png", ["-Gdpi=130"])):
        subprocess.run([neato, "-n2", f"-T{fmt}", *extra, src, "-o", os.path.join(HERE, f"{name}.{fmt}")], check=True)
    os.remove(src)
    print("wrote", name + ".svg", name + ".png")


def side(col_of, p, c):
    return "R" if col_of[c] > col_of[p] else "L" if col_of[c] < col_of[p] else "S"


def build_full():
    defs = {t: node(t, g, cols) for t, (g, cols) in SCHEMA.items()}

    def edges(col_of):
        return [edge(p, pc, c, cc, pe, ce, lg, side=side(col_of, p, c)) for p, pc, c, cc, pe, ce, lg in REL]

    title = ('<FONT POINT-SIZE="22"><B>SmartCommute — Full Entity-Relationship Diagram</B></FONT><BR/>'
             f'<FONT POINT-SIZE="11" COLOR="#57606a">{len(SCHEMA)} tables · crow\'s-foot notation · PK primary key · FK foreign key · '
             'UQ unique · <I>italic</I> and type? = nullable · dashed line = linked by id, no database foreign key</FONT>')
    legend = ((lambda W: 150, lambda H: 120),
              (["id", "pay", "fare", "drv", "flt", "trk", "sys"],
               "Framework tables (activity_log, failed_jobs) are Laravel / Spatie internals."))
    render_grid("erd_full", GRID_FULL, defs, edges, title, col_gap=200, row_gap=48, legend=legend)


def build_summary():
    defs = {}
    for key, (ttl, tname, grp, show) in SUMMARY.items():
        defs[key] = node(key, grp, SCHEMA[key][1], title=ttl, subtitle=tname, show=show)
    rel = {(p, c): (pc, cc, pe, ce, lg) for p, pc, c, cc, pe, ce, lg in REL}

    def edges(col_of):
        out = []
        for p, c, label in SUM_REL:
            pc, cc, pe, ce, lg = rel[(p, c)]
            out.append(edge(p, pc, c, cc, pe, ce, lg, label, side=side(col_of, p, c)))
        out.append('"users":"id_l":w -> "roles":"id_l":w [dir=both arrowtail=crowodot arrowhead=crowodot color="#444444" '
                   'arrowsize=0.9 label="has" fontsize="9" fontcolor="#444444"];')
        return out

    title = ('<FONT POINT-SIZE="22"><B>SmartCommute — Summary Entity-Relationship Diagram</B></FONT><BR/>'
             '<FONT POINT-SIZE="11" COLOR="#57606a">Main entities and key attributes only · crow\'s-foot notation · '
             'dashed line = linked by id, no database foreign key · User–Role is many-to-many through model_has_roles</FONT>')
    legend = ((lambda W: 150, lambda H: 150),
              (["id", "pay", "fare", "drv", "flt", "trk"], "Full column lists: see the full ERD."))
    render_grid("erd_summary", GRID_SUMMARY, defs, edges, title, col_gap=210, row_gap=46, legend=legend)


if __name__ == "__main__":
    for p, pc, c, cc, *_ in REL:       # sanity: every relationship column exists
        assert pc in [x.split(":")[0] for x in SCHEMA[p][1]], (p, pc)
        assert cc in [x.split(":")[0] for x in SCHEMA[c][1]], (c, cc)
    assert {t for col in GRID_FULL for t in col} == set(SCHEMA), "grid must place every table exactly once"
    build_full()
    build_summary()
