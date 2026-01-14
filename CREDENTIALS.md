# EG Construction - User Credentials

**Default Password for all users:** `password`

---

## Role Overview

| Role | Description | Fleet Maintenance Access |
|------|-------------|--------------------------|
| `administrator` | Full system access | Full access |
| `senior_dpf` | Senior Dedicated Plant Foreman - Can approve services, job cards, inspections | Full access to all sites |
| `site_dpf` | Site Dedicated Plant Foreman - Can submit services, job cards, inspections | Limited to assigned sites |
| `data_entry` | Can record readings, enter basic data | Limited data entry |
| `view_only` | Can view reports and dashboards only | Read-only access |

---

## Administrators

Full access to everything including user management, settings, and all modules.

| Name | Email | Notes |
|------|-------|-------|
| System Administrator | admin@egconstruction.co.zw | Primary admin account |
| Anesu Cain Mugomba | anesucain@gmail.com | Developer admin account |

---

## Tax & Insurance Users (View Only for Fleet)

These users focus on tax and insurance management. They have **view-only** access to fleet maintenance.

| Name | Email | Focus Area |
|------|-------|------------|
| Tax Manager | tax.manager@egconstruction.co.zw | Tax management |
| Tax Clerk | tax.clerk@egconstruction.co.zw | Tax data entry |
| Insurance Officer | insurance@egconstruction.co.zw | Insurance management |
| Management User | management@egconstruction.co.zw | General oversight |
| Reports Viewer | reports@egconstruction.co.zw | Report viewing only |

---

## Fleet Maintenance - Senior DPF

Can approve services, job cards, and inspections. Have access to **all sites**.

| Name | Email | Assigned Sites |
|------|-------|----------------|
| Senior DPF Manager | senior.dpf@egconstruction.co.zw | All 6 sites |
| Fleet Supervisor | fleet.supervisor@egconstruction.co.zw | All 6 sites |

---

## Fleet Maintenance - Site DPF

Can submit services, job cards, and inspections. Limited to their **assigned sites**.

| Name | Email | Assigned Site |
|------|-------|---------------|
| DPF - Main Yard | dpf.mainyard@egconstruction.co.zw | Main Yard (Harare) |
| DPF - Kariba Site | dpf.kariba@egconstruction.co.zw | Site A - Kariba |
| DPF - Hwange Site | dpf.hwange@egconstruction.co.zw | Site B - Hwange |
| DPF - Bulawayo Site | dpf.bulawayo@egconstruction.co.zw | Site C - Bulawayo |

---

## Fleet Maintenance - Data Entry

Can record readings and enter basic data.

| Name | Email |
|------|-------|
| Data Entry Clerk 1 | data.entry1@egconstruction.co.zw |
| Data Entry Clerk 2 | data.entry2@egconstruction.co.zw |

---

## Sites

| Site Name | Location |
|-----------|----------|
| Main Yard | Harare |
| Site A - Kariba | Kariba |
| Site B - Hwange | Hwange |
| Site C - Bulawayo | Bulawayo |
| Site D - Mutare | Mutare |
| Site E - Masvingo | Masvingo |

---

## Quick Reference - Login by Module

### Tax & Insurance Module
Use any of these accounts:
- `admin@egconstruction.co.zw` (full access)
- `tax.manager@egconstruction.co.zw` (view only)
- `tax.clerk@egconstruction.co.zw` (view only)
- `insurance@egconstruction.co.zw` (view only)

### Fleet Maintenance Module
- **To approve entries:** `senior.dpf@egconstruction.co.zw` or `fleet.supervisor@egconstruction.co.zw`
- **To submit entries:** Use any `dpf.*@egconstruction.co.zw` account for the relevant site
- **To enter readings:** `data.entry1@egconstruction.co.zw` or `data.entry2@egconstruction.co.zw`
- **To view reports:** `management@egconstruction.co.zw` or `reports@egconstruction.co.zw`

---

## Permission Matrix

| Action | Administrator | Senior DPF | Site DPF | Data Entry | View Only |
|--------|---------------|------------|----------|------------|-----------|
| Manage users | Yes | No | No | No | No |
| Manage settings | Yes | No | No | No | No |
| Approve services/job cards | Yes | Yes | No | No | No |
| Submit services/job cards | Yes | Yes | Yes | No | No |
| Record readings | Yes | Yes | Yes | Yes | No |
| View reports | Yes | Yes | Yes | Yes | Yes |
| Access all sites | Yes | Yes | No* | No | Yes |

*Site DPFs can only access their assigned sites.

---

## Notification Preferences

| User | Email | SMS | WhatsApp |
|------|-------|-----|----------|
| System Administrator | Yes | No | No |
| Anesu Cain Mugomba | Yes | Yes | Yes |
| Tax Manager | Yes | Yes | No |
| Tax Clerk | Yes | No | No |
| Insurance Officer | Yes | No | Yes |
| Senior DPF Manager | Yes | Yes | Yes |
| Fleet Supervisor | Yes | Yes | No |
| DPF - Main Yard | Yes | Yes | No |
| DPF - Kariba Site | Yes | Yes | No |
| DPF - Hwange Site | Yes | No | Yes |
| DPF - Bulawayo Site | Yes | Yes | No |
| Data Entry Clerk 1 | Yes | No | No |
| Data Entry Clerk 2 | Yes | No | No |
| Management User | Yes | No | No |
| Reports Viewer | No | No | No |
