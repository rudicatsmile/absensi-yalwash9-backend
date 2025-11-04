# Dynamic Attendance Report Display System Documentation

## System Overview

The attendance report system now supports a dual-mode display system that adapts dynamically based on parameters received from the ReportController. The system can switch between two display modes:

### Display Modes

1. **Detail Mode (Default)**: Traditional table showing individual attendance records with complete information
2. **Matrix Mode**: Dynamic matrix view with columns for each date and status codes (O/L/A/X/-)

### Key Features

- **Automatic Parameter Detection**: Automatically detects report format and adjusts display
- **Responsive Design**: Adapts to mobile and desktop screens
- **Data Validation**: Validates data before display with proper fallbacks
- **Fallback Mechanisms**: Automatically falls back to detail mode when matrix data is invalid
- **Consistent Design**: Maintains visual consistency with existing system

## Parameter Documentation

### Supported Parameters

| Parameter | Type | Default | Description | Example |
|-----------|------|---------|-------------|---------|
| `report_mode` | string | 'detail' | Display mode: 'detail' or 'matrix' | `report_mode=matrix` |
| `api_rows` | array | null | Data from ReportController API | Pass via view variable |
| `start_date` | string | Current month start | Start date for report | `start_date=2024-01-01` |
| `end_date` | string | Current month end | End date for report | `end_date=2024-01-31` |
| `shift_id` | integer | null | Specific shift ID | `shift_id=1` |

### Usage Examples

#### Detail Mode (Default)
```
URL: /admin/attendance-report
or
URL: /admin/attendance-report?report_mode=detail
```

#### Matrix Mode
```
URL: /admin/attendance-report?report_mode=matrix
```

#### Matrix Mode with API Data
```php
// In controller
$reportData = ReportController::attendanceReport($request);
return view('filament.pages.attendance-report', [
    'report_mode' => 'matrix',
    'api_rows' => $reportData['data']
]);
```

## Data Structure Requirements

### Matrix Mode Requirements

For matrix mode to work properly, the data must contain:

1. **Identity Columns**:
   - `No` (row number)
   - `Nama_Pegawai` or `Nama` (employee name)

2. **Date Columns**:
   - Format: `DD-MM-YYYY` (e.g., `01-01-2024`)
   - Status codes: `O`, `L`, `A`, `X`, `-`

3. **Summary Columns**:
   - `Total_kehadiran` (total attendance)
   - `Total_tidak_hadir` (total absent)
   - `Total_jam_kerja` (total working hours)

### Status Code Reference

| Code | Meaning | Color Badge | Description |
|------|---------|-------------|-------------|
| `O` | On Time | Green (`bg-green-100 text-green-800`) | Employee arrived on time |
| `L` | Late | Yellow (`bg-yellow-100 text-yellow-800`) | Employee arrived late |
| `A` | Absent | Red (`bg-red-100 text-red-800`) | Employee was absent |
| `X` | Holiday | Blue (`bg-blue-100 text-blue-800`) | Company holiday |
| `-` | No Data | Gray (`bg-slate-100 text-slate-700`) | No attendance record |

## Implementation Details

### Parameter Detection Logic

```php
$reportMode = $report_mode ?? request()->query('report_mode', 'detail');
$apiRows = $api_rows ?? (isset($rows) ? $rows : null);
$isMatrix = $reportMode === 'matrix' && is_array($apiRows) && !empty($apiRows);
```

### Data Validation

The system validates:
1. Presence of identity columns (`No`, `Nama_Pegawai`)
2. Presence of date columns (DD-MM-YYYY format)
3. Presence of summary columns
4. Non-empty data array

### Fallback Mechanism

If matrix mode is requested but data validation fails:
1. System automatically switches to detail mode
2. No error messages displayed to user
3. Seamless user experience maintained

## Responsive Design

### Mobile View
- Detail mode: Cards layout (commented out but available)
- Matrix mode: Horizontal scroll with sticky headers

### Desktop View
- Full table display with proper column widths
- Sticky headers for easy navigation
- Hover effects for better user experience

## Integration Guide

### Using with ReportController API

1. **Direct API Call**:
```php
use App\Http\Controllers\Api\ReportController;

$controller = new ReportController();
$request = new Request([
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'shift_id' => 1
]);

$response = $controller->attendanceReport($request);
$data = json_decode($response->getContent(), true);
```

2. **Via HTTP Request**:
```javascript
fetch('/api/reports/attendance?start_date=2024-01-01&end_date=2024-01-31')
    .then(response => response.json())
    .then(data => {
        // Use data.data for matrix display
        window.location.href = `/admin/attendance-report?report_mode=matrix`;
    });
```

### View Integration

```php
// In your controller
public function showReport(Request $request)
{
    $reportMode = $request->get('report_mode', 'detail');
    
    if ($reportMode === 'matrix') {
        $apiData = $this->getReportData($request);
        return view('filament.pages.attendance-report', [
            'report_mode' => 'matrix',
            'api_rows' => $apiData['data']
        ]);
    }
    
    return view('filament.pages.attendance-report');
}
```

## Accessibility Features

### ARIA Labels
- Table regions have proper `aria-label` attributes
- Status badges include `aria-label` for screen readers
- Proper `role` attributes for semantic HTML

### Keyboard Navigation
- Tables are keyboard navigable with `tabindex="0"`
- Proper focus indicators
- Sticky headers for better orientation

## Troubleshooting

### Common Issues

1. **Matrix Mode Not Working**
   - Check if data contains required columns
   - Verify date column format (DD-MM-YYYY)
   - Ensure identity columns are present

2. **Data Not Displaying**
   - Verify `api_rows` is properly passed
   - Check data structure matches requirements
   - Ensure array is not empty

3. **Styling Issues**
   - Verify Tailwind CSS is properly loaded
   - Check for CSS conflicts
   - Ensure custom styles are applied

### Debug Mode

Add debug information to verify data structure:
```php
@if(app()->environment('local'))
    <pre>{{ json_encode($apiRows[0] ?? [], JSON_PRETTY_PRINT) }}</pre>
@endif
```

## Performance Considerations

1. **Large Datasets**: Matrix mode with many dates may create wide tables
2. **Pagination**: Consider implementing pagination for large datasets
3. **Caching**: Cache API responses for frequently accessed reports

## Security Considerations

1. **Data Validation**: All parameters are validated before use
2. **SQL Injection**: Protected through Laravel's query builder
3. **XSS Protection**: All output is properly escaped
4. **Access Control**: Ensure proper authentication is implemented

## Future Enhancements

1. **Export Functionality**: Add export buttons for matrix view
2. **Sorting**: Implement column sorting for matrix mode
3. **Filtering**: Add client-side filtering capabilities
4. **Print Optimization**: Improve print styles for reports
5. **Mobile Cards**: Implement mobile card layout for matrix mode

## Code Examples

### Complete Implementation Example

```php
// Controller method
public function generateReport(Request $request)
{
    $validator = Validator::make($request->all(), [
        'start_date' => 'required|date_format:Y-m-d',
        'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        'report_mode' => 'in:detail,matrix',
        'shift_id' => 'nullable|integer'
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator);
    }

    $reportMode = $request->get('report_mode', 'detail');
    
    if ($reportMode === 'matrix') {
        $controller = new ReportController();
        $response = $controller->attendanceReport($request);
        $data = json_decode($response->getContent(), true);
        
        return view('filament.pages.attendance-report', [
            'report_mode' => 'matrix',
            'api_rows' => $data['data'] ?? [],
            'filters' => $data['filters'] ?? []
        ]);
    }

    return view('filament.pages.attendance-report');
}
```

This documentation provides comprehensive coverage of the dynamic attendance report display system, including all parameters, usage examples, integration guides, and troubleshooting information.