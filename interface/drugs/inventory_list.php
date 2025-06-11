<?php
// Add clinic filter
$clinic_id = isset($_GET['clinic_id']) ? $_GET['clinic_id'] : 0;

// Modify the query to include clinic_id
$query = "SELECT di.*, d.name, d.ndc_number, d.form, d.size, d.unit, d.route, " .
         "w.title as warehouse_name, v.name as vendor_name, f.name as clinic_name " .
         "FROM drug_inventory di " .
         "LEFT JOIN drugs d ON d.drug_id = di.drug_id " .
         "LEFT JOIN list_options w ON w.list_id = 'warehouse' AND w.option_id = di.warehouse_id " .
         "LEFT JOIN vendors v ON v.id = di.vendor_id " .
         "LEFT JOIN facility f ON f.id = di.clinic_id " .
         "WHERE 1=1 ";

if ($clinic_id) {
    $query .= "AND di.clinic_id = ? ";
}

$query .= "ORDER BY d.name, di.lot_number";

// Add clinic filter to the form
echo "<div class='form-group'>";
echo "<label>" . xlt('Clinic') . ":</label>";
echo "<select name='clinic_id' class='form-control' onchange='this.form.submit()'>";
echo "<option value='0'>" . xlt('All Clinics') . "</option>";
$clinics = sqlStatement("SELECT id, name FROM facility WHERE active = 1 ORDER BY name");
while ($clinic = sqlFetchArray($clinics)) {
    echo "<option value='" . attr($clinic['id']) . "'";
    if ($clinic['id'] == $clinic_id) echo " selected";
    echo ">" . text($clinic['name']) . "</option>";
}
echo "</select>";
echo "</div>";

// Add clinic name to the table headers
echo "<th>" . xlt('Clinic') . "</th>";

// Add clinic name to the table rows
echo "<td>" . text($row['clinic_name']) . "</td>"; 