# EG Construction Fleet Management System

## Frontend & UI Specifications

**Version:** 1.0  
**Date:** December 2024  
**Stack:** Next.js | React | Tailwind CSS  

---

## 1. Technology Stack

| Component | Technology |
|-----------|------------|
| Framework | Next.js (React) |
| Language | TypeScript or JavaScript |
| Styling | Tailwind CSS |
| State | React Context or Zustand |
| HTTP Client | Axios or Fetch |
| Date Library | date-fns or dayjs |

---

## 2. Pages Overview

| Page | Route | Description |
|------|-------|-------------|
| Login | /login | Authentication |
| Dashboard | / | Home, fleet overview |
| Vehicles List | /vehicles | All vehicles table |
| Add Vehicle | /vehicles/new | Create vehicle form |
| Edit Vehicle | /vehicles/[id]/edit | Edit vehicle form |
| Vehicle Detail | /vehicles/[id] | Single vehicle + tax history |
| Settings | /settings | System configuration |

---

## 3. Login Page

**Route:** `/login`

### Components
- Company logo/title
- Email input
- Password input
- Login button
- Error message area

### Behavior
- Validate email format
- Show loading state during auth
- Display error on failure
- Redirect to Dashboard on success
- Store token securely

### API
- POST /api/auth/login

---

## 4. Dashboard (Home)

**Route:** `/`

### 4.1 Summary Cards

| Card | Value | Color |
|------|-------|-------|
| Total Vehicles | Count of active | Blue/Gray |
| Expiring Soon | Within 14 days | Yellow |
| Overdue | Past expiry | Red |
| In Penalty Zone | 30+ days overdue | Dark Red |

### 4.2 Vehicle List (Color-Coded)

| Color | Status | Condition |
|-------|--------|-----------|
| Green | Valid | >30 days until expiry |
| Yellow/Orange | Expiring Soon | ≤30 days until expiry |
| Red | Expired | Past expiry, ≤30 days |
| Dark Red | Penalty | >30 days past expiry |

### 4.3 List Columns
- Reference Name
- Vehicle Type
- Tax Expiry Date
- Days Remaining (or days overdue)
- Status Badge

### 4.4 Pop-up Alert
On page load, show modal if:
- Any vehicles are overdue
- Any vehicles expiring within 7 days

Modal shows list with "View Details" and "Dismiss" buttons.

### APIs
- GET /api/dashboard/summary
- GET /api/dashboard/alerts
- GET /api/vehicles

---

## 5. Vehicles List Page

**Route:** `/vehicles`

### Features
- Full table of all vehicles
- Search by reference name
- Filter by vehicle status (Active/Disposed/Sold)
- Filter by tax status (Valid/Expiring/Expired/Penalty)
- Sortable columns
- "Add Vehicle" button
- Row actions: View, Edit, Delete

### Table Columns
| Column | Description |
|--------|-------------|
| Reference Name | Primary identifier |
| Type | Tipper, Grader, etc. |
| Registration | If available |
| Tax Expiry | End date |
| Days Remaining | Positive or negative |
| Status | Color-coded badge |
| Actions | View, Edit, Delete |

### Delete Confirmation
Modal: "Are you sure you want to delete [Vehicle Name]?"

### APIs
- GET /api/vehicles?status=&tax_status=&search=
- DELETE /api/vehicles/{id}

---

## 6. Add/Edit Vehicle Page

**Route:** `/vehicles/new` or `/vehicles/[id]/edit`

### Form Fields

**Required:**
| Field | Type | Validation |
|-------|------|------------|
| Reference Name | Text | Required, unique |
| Vehicle Type | Dropdown | Required |

**Optional:**
| Field | Type |
|-------|------|
| Registration Number | Text |
| Chassis Number | Text |
| Engine Number | Text |
| Make | Text |
| Model | Text |
| Year of Manufacture | Year picker |
| Status | Dropdown (Active/Disposed/Sold) |

### Vehicle Type Dropdown
Options: Tipper, Grader, Bulldozer, Bowser, Other

### Behavior
- Validate reference name uniqueness
- Show loading during save
- Show success message, redirect to detail page
- Show errors inline

### APIs
- GET /api/vehicle-types
- POST /api/vehicles (create)
- GET /api/vehicles/{id} (load for edit)
- PUT /api/vehicles/{id} (update)

---

## 7. Vehicle Detail Page

**Route:** `/vehicles/[id]`

### 7.1 Vehicle Info Card
- Reference Name (large, prominent)
- Current Tax Status Badge (color-coded)
- Vehicle Type
- Registration Number
- Other details (chassis, engine, make, model, year)
- Vehicle Status (Active/Disposed/Sold)

### 7.2 Current Tax Period Section
- Start Date
- End Date
- Days Remaining / Days Overdue
- Progress bar (visual)
- "Renew Now" button (if expiring/expired)

### 7.3 Tax History Table

| Column | Description |
|--------|-------------|
| Start Date | Period start |
| End Date | Period end |
| Amount (USD) | Payment amount |
| Status | Active/Expired/Penalty |
| Penalty? | Yes/No |

### 7.4 Action Buttons
- Add Tax Period / Renew
- Edit Vehicle
- Delete Vehicle (with confirmation)
- Back to List

### 7.5 Add Tax Period Modal

| Field | Type | Validation |
|-------|------|------------|
| Start Date | Date picker | Required |
| End Date | Date picker | Required, after start |
| Amount Paid | Number | Required, positive |

System auto-calculates penalty_incurred.

### APIs
- GET /api/vehicles/{id}
- POST /api/vehicles/{id}/tax-periods
- DELETE /api/vehicles/{id}

---

## 8. Settings Page

**Route:** `/settings`

### 8.1 Notification Settings
- List of reminder intervals (14, 7, 3, 1 days)
- Toggle to enable/disable each
- "Add Reminder" button
- Delete button per interval

### 8.2 User Management
- List of users (name, email, phone)
- "Add User" button
- Edit and Delete actions

### 8.3 Personal Preferences
- Toggle: Receive Email Notifications
- Toggle: Receive SMS Notifications

### APIs
- GET/POST/PUT/DELETE /api/settings/notifications
- GET/POST/PUT/DELETE /api/users
- PUT /api/users/{id}/preferences

---

## 9. Navigation

### Main Nav (Sidebar or Top)
- Dashboard (Home icon)
- Vehicles (Truck icon)
- Settings (Gear icon)
- User Profile / Logout

### Responsive
- Desktop: Full sidebar
- Tablet: Collapsed sidebar
- Mobile: Bottom nav or hamburger menu

---

## 10. Reusable Components

| Component | Props | Description |
|-----------|-------|-------------|
| StatusBadge | status | Color-coded badge |
| SummaryCard | title, count, icon, color | Dashboard card |
| VehicleRow | vehicle | Table row |
| ConfirmModal | title, message, onConfirm, onCancel | Confirmation dialog |
| AlertModal | vehicles, onDismiss | Urgent alerts |
| TextInput | label, value, error, onChange | Form input |
| Select | label, options, value, onChange | Dropdown |
| DatePicker | label, value, onChange | Date selection |
| Toggle | label, checked, onChange | Boolean switch |
| Button | variant, loading, onClick | Action button |

---

## 11. State Management

### Global State
- Authentication (user, token, isAuthenticated)
- User preferences

### Local State (per page)
- Form data and validation errors
- Loading states
- Modal open/close
- Filter and search values

---

## 12. Error Handling

| Scenario | Action |
|----------|--------|
| API error | Display message to user |
| Validation error | Show inline on form |
| 401 Unauthorized | Redirect to login |
| Network error | Show retry option |
| Loading | Show skeleton/spinner |

---

## 13. Color Scheme

| Use | Color | Tailwind Class |
|-----|-------|----------------|
| Valid | Green | bg-green-500 |
| Expiring Soon | Yellow/Orange | bg-yellow-500 |
| Expired | Red | bg-red-500 |
| Penalty | Dark Red | bg-red-700 |
| Primary | Blue | bg-blue-600 |
| Neutral | Gray | bg-gray-500 |

---

## 14. Folder Structure

```
/src
  /components      # Reusable UI components
    /ui            # Basic components (Button, Input, etc.)
    /vehicles      # Vehicle-specific components
    /dashboard     # Dashboard components
  /pages           # Next.js pages
  /hooks           # Custom React hooks
  /context         # React context providers
  /services        # API service functions
  /utils           # Utility functions (date formatting, etc.)
  /types           # TypeScript types/interfaces
  /styles          # Global styles
```

---

## 15. Key User Flows

### Flow 1: Check Fleet Status
1. Login
2. View Dashboard
3. See summary cards and color-coded list
4. Click vehicle for details

### Flow 2: Renew Vehicle Tax
1. Dashboard or Vehicles List
2. Click on expiring/expired vehicle
3. Click "Add Tax Period / Renew"
4. Fill in dates and amount
5. Submit

### Flow 3: Add New Vehicle
1. Go to Vehicles List
2. Click "Add Vehicle"
3. Fill required fields (name, type)
4. Optionally fill other fields
5. Submit

### Flow 4: Opt Out of Notifications
1. Go to Settings
2. Find Personal Preferences
3. Toggle off Email and/or SMS
4. Changes save automatically

---

*End of Frontend Specifications*