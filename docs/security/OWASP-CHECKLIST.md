# OWASP Practical Checklist — InventPro

| Field | Value |
|---|---|
| **Scope** | Local / staging readiness (Phase 12) |
| **Date** | 2026-08-14 |

## Controls implemented / verified

| Area | Status | Notes |
|---|---|---|
| Authentication | ✅ | Login throttled (`throttle:10,1`); public register disabled |
| Session | ✅ | Laravel session cookies; logout available |
| Authorization | ✅ | Spatie permission middleware on admin routes |
| Mass assignment | ✅ | Form Requests + `$fillable` on models |
| CSRF | ✅ | Laravel web middleware / Inertia |
| Security headers | ✅ | `SecurityHeaders` middleware: XFO, nosniff, Referrer-Policy, Permissions-Policy (CSP ketat → hardening staging) |
| Sensitive data | ✅ | Dev credentials only in `docs/LOGIN-CREDENTIALS.md` (local) |
| Audit | ✅ | `AuditLogger` on key write actions |
| UUID PKs | ✅ | Reduces sequential ID enumeration |

## Remaining / ops (out of phase code)

| Area | Action |
|---|---|
| HTTPS | Enforce at reverse proxy / production |
| Secrets | Rotate all seeder passwords before staging |
| CSP | Tighten `unsafe-inline` / `unsafe-eval` when assets allow |
| Backups | DB backup policy before go-live |
| Rate limit API | Add if public API introduced later |

## Permission smoke

- Viewer: dashboard + reports view, no export/create mutasi
- Approver: Persetujuan only (+ limited views)
- Warehouse: operasional stok/GR/opname/pinjam
- Purchasing: PO/vendor
- Admin/Superadmin: full
