# Fleet Maintenance API Specification

## Overview

The Fleet Maintenance API provides comprehensive endpoints for managing construction equipment and vehicle maintenance, including service scheduling, inspections, job cards, and component tracking with approval workflows.

**Base URL**: `/api`

---

## Authentication

All endpoints require authentication via Laravel Sanctum. Include the bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

---

## Role-Based Access Control

| Role | Permissions |
|------|-------------|
| `DATA_ENTRY` | Record readings |
| `SITE_DPF` | Create services/job cards/inspections, view own site data |
| `SENIOR_DPF` | Approve/reject submissions, override readings, all sites |
| `ADMIN` | Full access |

---

## Endpoints

### Sites

#### List Sites
```
GET /api/sites
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search by name or location |
| `is_active` | boolean | Filter by active status |
| `per_page` | integer | Items per page (default: 50) |

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Site Alpha",
      "location": "123 Main St",
      "is_active": true,
      "vehicles_count": 15,
      "users_count": 8
    }
  ],
  "meta": { "current_page": 1, "per_page": 50, "total": 10 }
}
```

#### Create Site
```
POST /api/sites
```
**Required Role:** `ADMIN`

**Body:**
```json
{
  "name": "Site Alpha",
  "location": "123 Main St",
  "is_active": true
}
```

#### Get Site
```
GET /api/sites/{site}
```

#### Update Site
```
PUT /api/sites/{site}
```
**Required Role:** `ADMIN`

#### Delete Site
```
DELETE /api/sites/{site}
```
**Required Role:** `ADMIN`

**Constraints:** Cannot delete site with assigned vehicles.

#### Get Site Machines
```
GET /api/sites/{site}/machines
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `is_yellow_machine` | boolean | Filter by equipment type |
| `search` | string | Search by reference or registration |

#### Get Site Staff
```
GET /api/sites/{site}/staff
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `role` | string | Filter by user role |

---

### Machine Types

Configuration for yellow machines (construction equipment) with service intervals.

#### List Machine Types
```
GET /api/machine-types
```

#### Create Machine Type
```
POST /api/machine-types
```

**Body:**
```json
{
  "name": "Excavator",
  "tracking_unit": "hours",
  "minor_service_interval": 250,
  "major_service_interval": 500,
  "warning_threshold": 50,
  "is_active": true
}
```

| Field | Type | Description |
|-------|------|-------------|
| `tracking_unit` | enum | `hours` or `kilometers` |
| `minor_service_interval` | integer | Interval in tracking units |
| `major_service_interval` | integer | Interval in tracking units |
| `warning_threshold` | integer | Alert when within this value |

#### Get/Update/Delete Machine Type
```
GET /api/machine-types/{machineType}
PUT /api/machine-types/{machineType}
DELETE /api/machine-types/{machineType}
```

#### Manage Checklist Items
```
GET /api/machine-types/{machineType}/checklist-items
POST /api/machine-types/{machineType}/checklist-items
```

---

### Vehicles (Fleet Extensions)

#### Record Reading
```
POST /api/vehicles/{vehicle}/readings
```
**Required Role:** `DATA_ENTRY`, `SITE_DPF`, `SENIOR_DPF`, or `ADMIN`

**Body:**
```json
{
  "reading_value": 5250,
  "reading_type": "hours",
  "source": "manual",
  "recorded_at": "2024-01-15"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reading_value` | integer | Yes | Must be >= 0 |
| `reading_type` | enum | No | `hours` or `kilometers` (defaults to vehicle's tracking unit) |
| `source` | enum | No | `manual`, `telematics`, `import`, `adjustment` (default: manual) |
| `is_anomaly_override` | boolean | No | For backwards readings |
| `anomaly_reason` | string | If override | Required if is_anomaly_override is true |
| `recorded_at` | date | No | Defaults to now |

**Validation:**
- Reading cannot go backwards unless `is_anomaly_override` is true
- Anomaly override requires `SENIOR_DPF` or `ADMIN`

**Error Response (backwards reading):** `422`
```json
{
  "message": "Reading value cannot be less than current reading",
  "errors": {
    "reading_value": ["Reading value cannot be less than current reading (5300)"]
  },
  "last_reading": 5300
}
```

#### Bulk Record Readings
```
POST /api/readings/bulk
```

**Body:**
```json
{
  "readings": [
    { "vehicle_id": "uuid", "reading_value": 5250 },
    { "vehicle_id": "uuid", "reading_value": 3100 }
  ]
}
```

**Response:** `207 Multi-Status` (partial success)
```json
{
  "success": [{ "vehicle_id": "uuid", "reading": {...} }],
  "failed": [{ "vehicle_id": "uuid", "error": "..." }],
  "success_count": 1,
  "failed_count": 1
}
```

#### Get Vehicle Readings
```
GET /api/vehicles/{vehicle}/readings
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `reading_type` | enum | `hours` or `kilometers` |
| `source` | enum | `manual`, `telematics`, `import`, `adjustment` |
| `from_date` | date | Start date filter |
| `to_date` | date | End date filter |

#### Get Service Status
```
GET /api/vehicles/{vehicle}/service-status
```

**Response:**
```json
{
  "current_reading": 5250,
  "tracking_unit": "hours",
  "minor_service": {
    "interval": 500,
    "last_at": 5000,
    "since_last": 250,
    "remaining": 250,
    "status": "ok"
  },
  "major_service": {
    "interval": 1000,
    "last_at": 4000,
    "since_last": 1250,
    "remaining": -250,
    "status": "overdue"
  }
}
```

| Status | Description |
|--------|-------------|
| `ok` | No service needed |
| `due_soon` | Within warning threshold |
| `overdue` | Service interval exceeded |

#### Site Assignments
```
GET /api/vehicles/{vehicle}/site-assignments
POST /api/vehicles/{vehicle}/site-assignments
```

**POST Body (Assign to Site):**
```json
{
  "site_id": "uuid",
  "notes": "Transferred for project X"
}
```
**Required Role:** `SITE_DPF`, `SENIOR_DPF`, or `ADMIN`

#### Interval Overrides
```
GET /api/vehicles/{vehicle}/interval-overrides
POST /api/vehicles/{vehicle}/interval-overrides
```
**Required Role:** `SENIOR_DPF` or `ADMIN`

**POST Body:**
```json
{
  "override_type": "minor_interval",
  "new_value": 300,
  "reason": "Extended interval per manufacturer update"
}
```

| override_type | Description |
|--------------|-------------|
| `minor_interval` | Override minor service interval |
| `major_interval` | Override major service interval |
| `warning_threshold` | Override warning threshold |

#### Get Vehicle Services
```
GET /api/vehicles/{vehicle}/services
```

---

### Services

Service records for minor and major maintenance.

#### List Services
```
GET /api/services
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | enum | `draft`, `pending`, `approved`, `rejected` |
| `vehicle_id` | uuid | Filter by vehicle |
| `site_id` | uuid | Filter by site |
| `service_type` | enum | `minor` or `major` |
| `from_date` | date | Start date filter |
| `to_date` | date | End date filter |
| `sort_by` | string | Field to sort by (default: service_date) |
| `sort_dir` | enum | `asc` or `desc` (default: desc) |

**Note:** `SITE_DPF` users only see services from their assigned sites.

#### Create Service
```
POST /api/services
```
**Required Role:** `SITE_DPF`, `SENIOR_DPF`, or `ADMIN`

**Body:**
```json
{
  "vehicle_id": "uuid",
  "service_type": "minor",
  "service_date": "2024-01-15",
  "reading_at_service": 5000,
  "site_id": "uuid",
  "notes": "Regular service - oil change, filters",
  "status": "draft"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `vehicle_id` | uuid | Yes | |
| `service_type` | enum | Yes | `minor` or `major` |
| `service_date` | date | Yes | |
| `reading_at_service` | integer | Yes | Vehicle reading at service |
| `site_id` | uuid | Yes | |
| `notes` | string | No | |
| `status` | enum | No | `draft` or `pending` (default: draft) |

**Response:** `201 Created`
```json
{
  "message": "Service created successfully",
  "data": {
    "id": "uuid",
    "vehicle_id": "uuid",
    "service_type": "minor",
    "service_date": "2024-01-15",
    "reading_at_service": 5000,
    "site_id": "uuid",
    "site_assignment_id": "uuid",
    "notes": "Regular service",
    "total_parts_cost": "0.00",
    "status": "draft",
    "vehicle": {...},
    "site": {...}
  }
}
```

#### Get/Update/Delete Service
```
GET /api/services/{service}
PUT /api/services/{service}
DELETE /api/services/{service}
```

**Constraints:**
- Update/Delete only for `draft` or `rejected` status

#### Submit for Approval
```
POST /api/services/{service}/submit
```

Sets `status` to `pending`, records `submitted_by` and `submitted_at`.

#### Approve Service
```
POST /api/services/{service}/approve
```
**Required Role:** `SENIOR_DPF` or `ADMIN`

Updates vehicle's last service tracking (`last_minor_service_reading`, `last_minor_service_date`, etc.).

#### Reject Service
```
POST /api/services/{service}/reject
```
**Required Role:** `SENIOR_DPF` or `ADMIN`

**Body:**
```json
{
  "rejection_reason": "Missing documentation for parts used"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `rejection_reason` | string | Yes | Minimum 10 characters |

#### Service Parts
```
GET /api/services/{service}/parts
POST /api/services/{service}/parts
DELETE /api/service-parts/{servicePart}
```

---

### Job Cards

Maintenance work beyond standard services (repairs, tyre changes, etc.).

#### List Job Cards
```
GET /api/job-cards
```

**Query Parameters:** Same as Services, plus:
| Parameter | Type | Description |
|-----------|------|-------------|
| `job_type` | enum | `repair`, `tyre_change`, `tyre_repair`, `other` |

#### Create Job Card
```
POST /api/job-cards
```
**Required Role:** `SITE_DPF`, `SENIOR_DPF`, or `ADMIN`

**Body:**
```json
{
  "vehicle_id": "uuid",
  "job_type": "repair",
  "job_date": "2024-01-15",
  "reading_at_job": 5100,
  "site_id": "uuid",
  "description": "Replaced hydraulic hose - leak detected during inspection",
  "status": "draft"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `job_type` | enum | Yes | `repair`, `tyre_change`, `tyre_repair`, `other` |
| `description` | string | Yes | Work description |

#### Submit/Approve/Reject
```
POST /api/job-cards/{jobCard}/submit
POST /api/job-cards/{jobCard}/approve
POST /api/job-cards/{jobCard}/reject
```

Same workflow as Services.

#### Related Watch Items
```
GET /api/job-cards/{jobCard}/related-watch-items
```

Returns active watch list items for the vehicle that can be resolved by this job card.

#### Resolve Watch Items
```
POST /api/job-cards/{jobCard}/resolve-watch-items
```

**Requirement:** Job card must be approved.

**Body:**
```json
{
  "watch_item_ids": ["uuid", "uuid"]
}
```

Sets watch item `status` to `resolved`, links to job card, records `resolved_at`.

#### Components & Parts
```
GET /api/job-cards/{jobCard}/components
POST /api/job-cards/{jobCard}/components
PUT /api/job-card-components/{jobCardComponent}
DELETE /api/job-card-components/{jobCardComponent}

GET /api/job-cards/{jobCard}/parts
POST /api/job-cards/{jobCard}/parts
DELETE /api/job-card-parts/{jobCardPart}
```

---

### Inspections

Vehicle condition assessments using configurable templates.

#### List Inspections
```
GET /api/inspections
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | enum | `draft`, `pending`, `approved`, `rejected` |
| `vehicle_id` | uuid | Filter by vehicle |
| `site_id` | uuid | Filter by site |
| `template_id` | uuid | Filter by template |
| `from_date` | date | Start date filter |
| `to_date` | date | End date filter |

#### Create Inspection
```
POST /api/inspections
```
**Required Role:** `SITE_DPF`, `SENIOR_DPF`, or `ADMIN`

**Body:**
```json
{
  "vehicle_id": "uuid",
  "template_id": "uuid",
  "inspection_date": "2024-01-15",
  "reading_at_inspection": 5200,
  "site_id": "uuid",
  "notes": "Monthly inspection"
}
```

Creates inspection with `status: draft` and `completion_percentage: 0`.

#### Submit for Approval
```
POST /api/inspections/{inspection}/submit
```

**Requirement:** `completion_percentage` must be 100%.

#### Approve Inspection
```
POST /api/inspections/{inspection}/approve
```
**Required Role:** `SENIOR_DPF` or `ADMIN`

**Side Effect:** Creates WatchListItem for each result with rating `service` or `repair`.

#### Get Inspection Results
```
GET /api/inspections/{inspection}/results
```

**Response:** Grouped by category
```json
{
  "data": {
    "Engine": [
      {
        "id": "uuid",
        "checklist_item_id": "uuid",
        "checklist_item": { "name": "Oil Level", "category": "Engine" },
        "rating": "good",
        "notes": null
      }
    ],
    "Hydraulics": [...]
  }
}
```

#### Update Inspection Results (Batch)
```
PUT /api/inspections/{inspection}/results
```

**Constraint:** Only for `draft` or `rejected` inspections.

**Body:**
```json
{
  "results": [
    {
      "checklist_item_id": "uuid",
      "rating": "good",
      "notes": null
    },
    {
      "checklist_item_id": "uuid",
      "rating": "repair",
      "notes": "Hydraulic hose showing wear"
    }
  ]
}
```

| Rating | Description |
|--------|-------------|
| `good` | No issues |
| `service` | Needs attention soon |
| `repair` | Needs repair |
| `replace` | Needs replacement |

Automatically updates `completion_percentage`.

---

### Inspection Templates

#### CRUD Operations
```
GET /api/inspection-templates
POST /api/inspection-templates
GET /api/inspection-templates/{inspectionTemplate}
PUT /api/inspection-templates/{inspectionTemplate}
DELETE /api/inspection-templates/{inspectionTemplate}
```

#### Template Items
```
GET /api/inspection-templates/{inspectionTemplate}/items
POST /api/inspection-templates/{inspectionTemplate}/items
```

---

### Checklists

#### Categories
```
GET /api/checklist-categories
```

#### Items
```
GET /api/checklist-items
POST /api/checklist-items
```

---

### Watch List

Tracks components flagged during inspections for follow-up.

#### List Watch Items
```
GET /api/watch-list
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | enum | `active`, `resolved`, `machine_disposed` |
| `vehicle_id` | uuid | Filter by vehicle |

#### Create Watch Item
```
POST /api/watch-list
```

**Body:**
```json
{
  "vehicle_id": "uuid",
  "component_id": "uuid",
  "rating_at_creation": "repair",
  "review_date": "2024-02-15",
  "notes": "Monitor hydraulic hose condition"
}
```

#### Resolve Watch Item
```
POST /api/watch-list/{watchListItem}/resolve
```

**Body:**
```json
{
  "job_card_id": "uuid"
}
```

---

### Oil Analysis

```
GET /api/vehicles/{vehicle}/oil-analyses
POST /api/vehicles/{vehicle}/oil-analyses
GET /api/oil-analyses/{oilAnalysis}
PUT /api/oil-analyses/{oilAnalysis}
DELETE /api/oil-analyses/{oilAnalysis}
```

---

### Components & Parts Catalog

#### Components
```
GET /api/components
POST /api/components
GET /api/components/{component}
PUT /api/components/{component}
DELETE /api/components/{component}
```

#### Parts Catalog
```
GET /api/parts-catalog
POST /api/parts-catalog
GET /api/parts-catalog/{part}
PUT /api/parts-catalog/{part}
DELETE /api/parts-catalog/{part}
```

---

### Approvals Queue

#### List Pending Approvals
```
GET /api/approvals
```

Returns combined list of pending Services, JobCards, and Inspections.

#### Get Approval Count
```
GET /api/approvals/count
```

---

### Dashboard

#### Fleet Summary
```
GET /api/dashboard/fleet-summary
```

**Response:**
```json
{
  "yellow_machines": {
    "total": 45,
    "ok": 30,
    "due_soon": 10,
    "overdue": 3,
    "unknown": 2
  },
  "pending_approvals": 5,
  "active_watch_items": 12,
  "recent_activity": {
    "services_approved": 8,
    "job_cards_approved": 3
  }
}
```

#### Pending Approvals
```
GET /api/dashboard/pending-approvals?limit=5
```

#### Upcoming Services
```
GET /api/dashboard/upcoming-services?limit=10
```

Returns vehicles with `status: due_soon`, sorted by remaining units.

#### Overdue Services
```
GET /api/dashboard/overdue-services?limit=10
```

Returns vehicles with `status: overdue`, includes `overdue_by` amount.

#### Watch List Summary
```
GET /api/dashboard/watch-list-summary?limit=10
```

**Response:**
```json
{
  "items": [...],
  "summary": {
    "total_active": 12,
    "service_items": 5,
    "repair_items": 7,
    "overdue_reviews": 2
  }
}
```

---

### Reports

#### Fleet Status Report
```
GET /api/reports/fleet-status
```

#### Service History
```
GET /api/reports/service-history
```

#### Job Card History
```
GET /api/reports/job-card-history
```

#### Component Lifespan Analysis
```
GET /api/reports/component-lifespan
```

#### Site Performance Metrics
```
GET /api/reports/site-performance
```

#### Cost Analysis
```
GET /api/reports/cost-analysis
```

---

## Data Models

### Vehicle (Fleet Fields)

| Field | Type | Description |
|-------|------|-------------|
| `is_yellow_machine` | boolean | Distinguishes equipment from road vehicles |
| `machine_type_id` | uuid | Links to MachineType for service intervals |
| `primary_site_id` | uuid | Current site assignment |
| `current_hours` | integer | Current hour meter reading |
| `current_km` | integer | Current odometer reading |
| `last_reading_at` | datetime | Timestamp of latest reading |
| `last_minor_service_reading` | integer | Reading at last minor service |
| `last_minor_service_date` | date | Date of last minor service |
| `last_major_service_reading` | integer | Reading at last major service |
| `last_major_service_date` | date | Date of last major service |
| `warning_threshold_hours` | integer | Override for warning threshold |
| `warning_threshold_km` | integer | Override for warning threshold |
| `reading_stale_days` | integer | Days before reading is considered stale |
| `has_reading_anomaly` | boolean | Flag for backwards reading detected |

### Service Status Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `tracking_unit` | string | `hours` or `kilometers` |
| `current_reading` | integer | Current hours or km |
| `next_minor_service_due` | integer | Reading when minor service due |
| `next_major_service_due` | integer | Reading when major service due |
| `remaining_until_minor_service` | integer | Units remaining |
| `remaining_until_major_service` | integer | Units remaining |
| `is_minor_service_due_soon` | boolean | Within warning threshold |
| `is_major_service_due_soon` | boolean | Within warning threshold |
| `is_minor_service_overdue` | boolean | Past interval |
| `is_major_service_overdue` | boolean | Past interval |
| `service_status` | enum | `overdue`, `due_soon`, `ok` |
| `reading_is_stale` | boolean | No reading in stale period |

---

## Approval Workflow

```
┌───────┐    submit()    ┌─────────┐    approve()    ┌──────────┐
│ draft │ ─────────────→ │ pending │ ──────────────→ │ approved │
└───────┘                └─────────┘                 └──────────┘
    ↑                         │
    │                         │ reject()
    │                         ↓
    │                   ┌──────────┐
    └───────────────────│ rejected │
       (edit & resubmit)└──────────┘
```

**Workflow Fields:**
| Field | Description |
|-------|-------------|
| `status` | Current state: draft, pending, approved, rejected |
| `submitted_by` | User ID who submitted |
| `submitted_at` | Submission timestamp |
| `approved_by` | User ID who approved |
| `approved_at` | Approval timestamp |
| `rejection_reason` | Required reason for rejection (min 10 chars) |
| `previous_submission_id` | Link to previous rejected submission |

**Applies to:** Services, JobCards, Inspections

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Not Found (404)
```json
{
  "message": "Resource not found"
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized action"
}
```

### Conflict (409)
```json
{
  "message": "Cannot delete site with assigned vehicles"
}
```
