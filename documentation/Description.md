# EG Construction Fleet Vehicle Tax Management System

## System Description & Requirements

**Version:** 1.0  
**Date:** December 2024  
**Client:** EG Construction  

---

## 1. Executive Summary

### 1.1 Purpose
A web-based application to track vehicle tax and insurance payments for EG Construction's fleet of heavy machinery operating in Zimbabwe. The system provides proactive notifications to prevent late renewal penalties.

### 1.2 Business Problem
EG Construction operates a fleet of heavy machinery. In Zimbabwe, vehicle tax (bundled with ZINARA license and mandatory insurance) must be renewed periodically. If renewal is more than 30 days late, the company pays **DOUBLE** the normal fee. This system prevents such penalties.

---

## 2. Business Rules (Zimbabwe Vehicle Tax)

1. Vehicle tax is bundled with mandatory third-party insurance (ZINARA license)
2. Payment covers both tax and insurance in one transaction
3. Payment periods are flexible: **4 months, 8 months, or 12 months**
4. After expiry, there is a **30-day grace period**
5. Renewal after 30 days past expiry = **DOUBLE penalty fee**
6. All payments are in **USD**

---

## 3. Fleet Composition

- **Tippers** - hauling and dumping materials
- **Graders** - road construction and leveling
- **Bulldozers** - earthmoving operations
- **Bowsers** - water or fuel transport
- **Other** - miscellaneous vehicles/machinery

**Important:** Many machines do not have registration plates. They are transported on low-bed trucks and identified internally by reference names (e.g., "Tipper 1", "Grader 2", "Bowser 1"). The system uses this as the **primary identifier**.

---

## 4. Target Users

- **2-3 staff members** at EG Construction
- All users have **equal access permissions** (no role-based access)
- Users include: administrative staff, fleet managers, executives (CEO)
- Users can **opt out of notifications** individually

---

## 5. Core Features (MVP)

### 5.1 Vehicle Management
- Register vehicles with reference name (primary identifier)
- Optional fields: registration number, chassis, engine number, make, model, year
- Vehicle status: Active, Disposed, Sold
- Soft delete (never permanently remove data)

### 5.2 Tax Period Tracking
- Record tax payments with: start date, end date, amount paid (USD)
- Track status: Active, Expired, Penalty
- Flag if penalty was incurred (renewed 30+ days late)
- Maintain complete history per vehicle

### 5.3 Notification System
- **Global reminder intervals** (configurable): 14, 7, 3, 1 days before expiry
- **Channels:** SMS and Email
- All users receive notifications (unless opted out)
- Log all sent notifications

### 5.4 Dashboard
- Summary cards: Total vehicles, Expiring soon, Overdue, In penalty zone
- Color-coded vehicle list (Green/Yellow/Red based on status)
- Pop-up alert on login for urgent items

### 5.5 User Management
- Simple authentication (email + password)
- Users can opt out of email and/or SMS notifications
- No role-based permissions

---

## 6. Technology Stack

| Layer | Technology |
|-------|------------|
| Frontend | Next.js (React) |
| Backend | PHP 8 |
| Database | MySQL 8 |
| SMS | Twilio or Africa's Talking |
| Email | SendGrid, Mailgun, or SMTP |

---

## 7. Key Business Objectives

1. **Prevent financial penalties** from late renewals
2. **Provide visibility** into fleet tax status
3. **Maintain historical records** for auditing
4. **Enable proactive planning** for tax renewals
5. **Track penalty occurrences** for process improvement

---

## 8. Out of Scope (Future Enhancements)

- Reports & Exports (Excel/PDF)
- Bulk renewal
- Notes/comments on vehicles
- Document uploads (tax certificates)
- Calendar view
- Mobile app

---

## 9. Glossary

| Term | Definition |
|------|------------|
| ZINARA | Zimbabwe National Road Administration |
| Vehicle Tax | Combined payment for road tax + mandatory insurance |
| Tax Period | Duration for which vehicle tax is paid (4/8/12 months) |
| Grace Period | 30 days after expiry for renewal without penalty |
| Penalty | Double fee charged when renewal is 30+ days late |
| Reference Name | Internal identifier (e.g., "Tipper 1") |
| Soft Delete | Marking record as deleted without removing from database |

---

*End of System Description*