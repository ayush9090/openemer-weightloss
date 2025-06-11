# Supplement Tracker Module for OpenEMR

A custom module for OpenEMR that allows tracking supplements per clinic and patient usage.

## Features

- Track supplements per clinic (facility)
- Manage supplement inventory
- Assign supplements to patients
- Automatic stock reduction when supplements are assigned
- View supplement history per patient
- Facility-based access control

## Requirements

- OpenEMR 7.0.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Installation

1. Copy the `supplement_tracker` directory to `interface/modules/custom/`
2. Run the SQL installation script:
   ```sql
   source interface/modules/custom/supplement_tracker/sql/supplement_tracker.sql
   ```
3. Clear OpenEMR's cache:
   - Delete contents of `sites/default/documents/temp`
   - Delete contents of `sites/default/documents/cache`

## Usage

### Admin Interface

Access the admin interface through:
- Administration > Supplement Tracker

Features:
- Add/edit/delete supplements
- Set initial stock quantities
- View current inventory
- Filter by facility

### Patient Interface

Access the patient interface through:
- Patient > Supplements

Features:
- Assign supplements to patients
- View supplement history
- Add usage notes
- Automatic stock reduction

## Database Structure

### clinic_supplements
- id (PK)
- facility_id (FK)
- supplement_name
- stock_qty
- unit
- created_at
- updated_at

### supplement_usage
- id (PK)
- patient_id (FK)
- facility_id (FK)
- supplement_id (FK)
- quantity
- usage_date
- notes
- created_at
- created_by (FK)

## Security

The module uses OpenEMR's built-in:
- Authentication system
- ACL (Access Control List)
- CSRF protection
- SQL injection prevention

## Permissions

Required permissions:
- supplement_tracker_admin
- supplement_tracker_edit
- supplement_tracker_view
- supplement_tracker_assign

## Support

For support, please:
1. Check the [OpenEMR Forums](https://community.open-emr.org/)
2. Create an issue on GitHub
3. Contact the module author

## License

This module is licensed under the GNU General Public License v3.0. 