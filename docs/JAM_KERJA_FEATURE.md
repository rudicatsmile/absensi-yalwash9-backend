# Jam Kerja Feature Documentation

## Introduction
The "Jam Kerja" feature allows administrators to manage work shifts (jam kerja) independently as master data. This feature mirrors the structure of the existing `shift_kerjas` table but serves as a dedicated module for managing standardized work hours.

## Database Schema

### Table: `jam_kerjas`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint(20) unsigned | No | Primary Key |
| `name` | varchar(255) | No | Shift Name (e.g., Morning Shift) |
| `start_time` | time | No | Shift Start Time |
| `end_time` | time | No | Shift End Time |
| `description` | text | Yes | Optional description |
| `is_cross_day` | tinyint(1) | No | Default 0. 1 if shift crosses midnight |
| `grace_period_minutes` | int(11) | No | Default 10. Late tolerance in minutes |
| `is_active` | tinyint(1) | No | Default 1. 1 = Active, 0 = Inactive |
| `created_at` | timestamp | Yes | Creation timestamp |
| `updated_at` | timestamp | Yes | Update timestamp |

## API Specification

Base URL: `/api/jam-kerjas`
Authentication: Bearer Token (Sanctum)

### 1. List Jam Kerja
**Endpoint:** `GET /api/jam-kerjas`
**Query Parameters:**
- `is_active` (boolean, optional): Filter by active status.

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "Morning Shift",
            "start_time": "08:00:00",
            "end_time": "16:00:00",
            "description": null,
            "is_cross_day": 0,
            "grace_period_minutes": 10,
            "is_active": 1,
            "created_at": "...",
            "updated_at": "..."
        }
    ]
}
```

### 2. Create Jam Kerja
**Endpoint:** `POST /api/jam-kerjas`
**Body:**
```json
{
    "name": "Night Shift",
    "start_time": "23:00",
    "end_time": "07:00",
    "is_cross_day": true,
    "grace_period_minutes": 15,
    "is_active": true,
    "description": "Night shift description"
}
```
**Response:** Status 201 Created

### 3. Get Jam Kerja Detail
**Endpoint:** `GET /api/jam-kerjas/{id}`
**Response:** Status 200 OK

### 4. Update Jam Kerja
**Endpoint:** `PUT /api/jam-kerjas/{id}`
**Body:** (Same as Create, partial updates allowed)
**Response:** Status 200 OK

### 5. Delete Jam Kerja
**Endpoint:** `DELETE /api/jam-kerjas/{id}`
**Response:** Status 200 OK

## User Manual (Admin Panel)

1.  **Access**: Log in to the Admin Panel and navigate to "Master Data" > "Jam Kerja".
2.  **List View**: You will see a list of all configured shifts.
    -   **Filters**: You can filter by Status (Active/Inactive) or Cross Midnight.
    -   **Sort**: Click column headers to sort.
    -   **Search**: Use the search bar to find shifts by name.
3.  **Create**: Click the "New Jam Kerja" button.
    -   Fill in the Shift Name, Start Time, and End Time.
    -   The system will automatically validate for overlapping shifts (checking against other Jam Kerja records).
    -   Set "Cross Midnight" if the shift ends on the next day (e.g., 23:00 - 07:00).
    -   Set "Grace Period" for attendance tolerance.
4.  **Edit**: Click the "Edit" icon on any row to modify details.
5.  **Delete**: Click the "Delete" icon or use bulk delete for multiple records.
6.  **Import/Export**:
    -   **Export**: Click "Export" to download the list as Excel/CSV.
    -   **Import**: Click "Import" to upload a CSV/Excel file to bulk create shifts.
