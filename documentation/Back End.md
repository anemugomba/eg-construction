# EG Construction Fleet Management System

## Backend & Database Specifications

**Version:** 1.0  
**Date:** December 2024  
**Stack:** PHP 8 | MySQL 8 | REST API  

---

## 1. Technology Stack

| Component | Technology |
|-----------|------------|
| Language | PHP 8 |
| Framework | Laravel (recommended) or Slim |
| Database | MySQL 8 |
| Authentication | Laravel Sanctum or JWT |
| SMS | Twilio, Africa's Talking, or WhatsApp Business API |
| Email | SendGrid, Mailgun, or SMTP |

---

## 2. Database Schema

### 2.1 Table: `users`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(150) | NOT NULL, UNIQUE |
| phone | VARCHAR(20) | For SMS/WhatsApp |
| password | VARCHAR(255) | Hashed, NOT NULL |
| receive_email_notifications | TINYINT(1) | DEFAULT 1 |
| receive_sms_notifications | TINYINT(1) | DEFAULT 1 |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 2.2 Table: `vehicle_types`

| Column | Type | Notes |
|--------|------|-------|
| id | INT | Primary key |
| name | VARCHAR(50) | NOT NULL |
| deleted_at | TIMESTAMP NULL | Soft delete |

**Default data:** 1=Tipper, 2=Grader, 3=Bulldozer, 4=Bowser, 5=Other

---

### 2.3 Table: `vehicles`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| reference_name | VARCHAR(100) | NOT NULL, UNIQUE (e.g., "Tipper 1") |
| vehicle_type_id | INT | FK to vehicle_types, NOT NULL |
| registration_number | VARCHAR(20) | NULL (not all have plates) |
| chassis_number | VARCHAR(50) | NULL |
| engine_number | VARCHAR(50) | NULL |
| make | VARCHAR(50) | NULL |
| model | VARCHAR(50) | NULL |
| year_of_manufacture | YEAR | NULL |
| status | ENUM('active','disposed','sold') | DEFAULT 'active' |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 2.4 Table: `tax_periods`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| vehicle_id | INT | FK to vehicles, NOT NULL |
| start_date | DATE | NOT NULL |
| end_date | DATE | NOT NULL |
| amount_paid | DECIMAL(10,2) | USD, NOT NULL |
| status | ENUM('active','expired','penalty') | DEFAULT 'active' |
| penalty_incurred | TINYINT(1) | DEFAULT 0 (1 = renewed 30+ days late) |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 2.5 Table: `notification_settings`

| Column | Type | Notes |
|--------|------|-------|
| id | INT | Primary key |
| days_before | INT | NOT NULL (e.g., 14, 7, 3, 1) |
| is_active | TINYINT(1) | DEFAULT 1 |

**Default data:** 14 days, 7 days, 3 days, 1 day (all active)

---

### 2.6 Table: `notification_log`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| vehicle_id | INT | FK to vehicles |
| tax_period_id | INT | FK to tax_periods |
| user_id | INT | FK to users (recipient) |
| notification_type | ENUM('email','sms','whatsapp') | |
| recipient | VARCHAR(150) | Email or phone |
| days_before_expiry | INT | Which reminder triggered this |
| sent_at | TIMESTAMP | |
| status | ENUM('sent','failed') | |

---

## 3. SQL Script

```sql
CREATE DATABASE IF NOT EXISTS eg_fleet_management;
USE eg_fleet_management;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  receive_email_notifications TINYINT(1) DEFAULT 1,
  receive_sms_notifications TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL
);

CREATE TABLE vehicle_types (
  id INT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  deleted_at TIMESTAMP NULL
);

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference_name VARCHAR(100) NOT NULL UNIQUE,
  vehicle_type_id INT NOT NULL,
  registration_number VARCHAR(20),
  chassis_number VARCHAR(50),
  engine_number VARCHAR(50),
  make VARCHAR(50),
  model VARCHAR(50),
  year_of_manufacture YEAR,
  status ENUM('active','disposed','sold') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

CREATE TABLE tax_periods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  status ENUM('active','expired','penalty') DEFAULT 'active',
  penalty_incurred TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE notification_settings (
  id INT PRIMARY KEY,
  days_before INT NOT NULL,
  is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE notification_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  tax_period_id INT NOT NULL,
  user_id INT NOT NULL,
  notification_type ENUM('email','sms','whatsapp') NOT NULL,
  recipient VARCHAR(150) NOT NULL,
  days_before_expiry INT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('sent','failed') NOT NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (tax_period_id) REFERENCES tax_periods(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Default data
INSERT INTO vehicle_types (id, name) VALUES 
(1, 'Tipper'), (2, 'Grader'), (3, 'Bulldozer'), (4, 'Bowser'), (5, 'Other');

INSERT INTO notification_settings (id, days_before, is_active) VALUES 
(1, 14, 1), (2, 7, 1), (3, 3, 1), (4, 1, 1);
```

---

## 4. API Endpoints

### 4.1 Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | Login, returns token |
| POST | /api/auth/logout | Logout |

**Login Request:**
```json
{ "email": "string", "password": "string" }
```

**Login Response:**
```json
{ "token": "string", "user": {...} }
```

---

### 4.2 Vehicles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/vehicles | List all (with filters) |
| GET | /api/vehicles/{id} | Get one with tax history |
| POST | /api/vehicles | Create |
| PUT | /api/vehicles/{id} | Update |
| DELETE | /api/vehicles/{id} | Soft delete |

**Query params for GET /api/vehicles:**
- `status` - active, disposed, sold
- `tax_status` - valid, expiring_soon, expired, penalty
- `search` - search by reference name

**Create/Update body:**
```json
{
  "reference_name": "Tipper 1",
  "vehicle_type_id": 1,
  "registration_number": "ABC 1234",
  "chassis_number": "optional",
  "engine_number": "optional",
  "make": "Caterpillar",
  "model": "D6",
  "year_of_manufacture": 2020,
  "status": "active"
}
```

---

### 4.3 Tax Periods

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/vehicles/{id}/tax-periods | Get history for vehicle |
| POST | /api/vehicles/{id}/tax-periods | Add new period |
| PUT | /api/tax-periods/{id} | Update |
| DELETE | /api/tax-periods/{id} | Soft delete |

**Create body:**
```json
{
  "start_date": "2024-01-01",
  "end_date": "2024-04-30",
  "amount_paid": 150.00
}
```

**Note:** System auto-calculates `penalty_incurred` based on gap from previous period.

---

### 4.4 Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/users | List all |
| GET | /api/users/{id} | Get one |
| POST | /api/users | Create |
| PUT | /api/users/{id} | Update |
| PUT | /api/users/{id}/preferences | Update notification prefs |
| DELETE | /api/users/{id} | Soft delete |

**Preferences body:**
```json
{
  "receive_email_notifications": true,
  "receive_sms_notifications": false
}
```

---

### 4.5 Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/settings/notifications | Get all reminders |
| POST | /api/settings/notifications | Add reminder |
| PUT | /api/settings/notifications/{id} | Enable/disable |
| DELETE | /api/settings/notifications/{id} | Remove |

---

### 4.6 Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/dashboard/summary | Get counts |
| GET | /api/dashboard/alerts | Vehicles needing attention |
| GET | /api/vehicle-types | Get all types |

**Summary response:**
```json
{
  "total_vehicles": 15,
  "expiring_soon": 3,
  "overdue": 1,
  "in_penalty": 0
}
```

---

## 5. Background Job: Daily Notifications

Run daily at **6:00 AM** via cron or Laravel scheduler.

### Logic:
1. Get all active vehicles with active tax periods
2. Calculate days until expiry for each
3. Check against enabled notification_settings
4. For matching vehicles, send to users who haven't opted out
5. Log all notifications to notification_log

### Notification Content:
**Email Subject:** Vehicle Tax Renewal Reminder - [Reference Name]

**SMS/WhatsApp:** 
```
EG Construction: Vehicle tax for [Name] ([Type]) expires on [Date] ([X] days). Renew to avoid double penalty.
```

---

## 6. Security Requirements

- Passwords hashed with bcrypt or Argon2
- Session tokens expire after 24 hours
- Rate limiting on auth endpoints
- All endpoints require authentication
- HTTPS enforced
- Input validation on all fields
- Parameterized queries (use ORM)
- All deletes are soft deletes

---

## 7. Tax Status Logic

```
IF current_date < end_date - 30 days THEN "valid" (green)
IF current_date >= end_date - 30 days AND current_date <= end_date THEN "expiring_soon" (yellow)
IF current_date > end_date AND current_date <= end_date + 30 days THEN "expired" (red)
IF current_date > end_date + 30 days THEN "penalty" (dark red)
```

---

*End of Backend Specifications*