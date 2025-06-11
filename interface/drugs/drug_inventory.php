<?php

 // Copyright (C) 2006-2021 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

require_once("../globals.php");
require_once("drugs.inc.php");
require_once("$srcdir/options.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;

// Check authorizations.
$auth_admin = AclMain::aclCheckCore('admin', 'drugs');
$auth_lots  = $auth_admin                             ||
    AclMain::aclCheckCore('inventory', 'lots') ||
    AclMain::aclCheckCore('inventory', 'purchases') ||
    AclMain::aclCheckCore('inventory', 'transfers') ||
    AclMain::aclCheckCore('inventory', 'adjustments') ||
    AclMain::aclCheckCore('inventory', 'consumption') ||
    AclMain::aclCheckCore('inventory', 'destruction');
$auth_anything = $auth_lots                           ||
    AclMain::aclCheckCore('inventory', 'sales') ||
    AclMain::aclCheckCore('inventory', 'reporting');
if (!$auth_anything) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Drug Inventory")]);
    exit;
}
// Note if user is restricted to any facilities and/or warehouses.
$is_user_restricted = isUserRestricted();

// For each sorting option, specify the ORDER BY argument.
//
$ORDERHASH = array(
  'prod' => 'd.name, d.drug_id, di.expiration, di.lot_number',
  'act'  => 'd.active, d.name, d.drug_id, di.expiration, di.lot_number',
  'ndc'  => 'd.ndc_number, d.name, d.drug_id, di.expiration, di.lot_number',
  'con'  => 'd.consumable, d.name, d.drug_id, di.expiration, di.lot_number',
  'form' => 'lof.title, d.name, d.drug_id, di.expiration, di.lot_number',
  'lot'  => 'di.lot_number, d.name, d.drug_id, di.expiration',
  'wh'   => 'lo.title, d.name, d.drug_id, di.expiration, di.lot_number',
  'fac'  => 'f.name, d.name, d.drug_id, di.expiration, di.lot_number',
  'qoh'  => 'di.on_hand, d.name, d.drug_id, di.expiration, di.lot_number',
  'exp'  => 'di.expiration, d.name, d.drug_id, di.lot_number',
);

$form_facility = 0 + empty($_REQUEST['form_facility']) ? 0 : $_REQUEST['form_facility'];
$form_show_empty = empty($_REQUEST['form_show_empty']) ? 0 : 1;
$form_show_inactive = empty($_REQUEST['form_show_inactive']) ? 0 : 1;
$form_consumable = isset($_REQUEST['form_consumable']) ? intval($_REQUEST['form_consumable']) : 0;

// Incoming form_warehouse, if not empty is in the form "warehouse/facility".
// The facility part is an attribute used by JavaScript logic.
$form_warehouse = empty($_REQUEST['form_warehouse']) ? '' : $_REQUEST['form_warehouse'];
$tmp = explode('/', $form_warehouse);
$form_warehouse = $tmp[0];

// Get the order hash array value and key for this request.
$form_orderby = isset($ORDERHASH[$_REQUEST['form_orderby'] ?? '']) ? $_REQUEST['form_orderby'] : 'prod';
$orderby = $ORDERHASH[$form_orderby];

$binds = array();
$where = "WHERE 1 = 1";
if ($form_facility) {
    $where .= " AND lo.option_value IS NOT NULL AND lo.option_value = ?";
    $binds[] = $form_facility;
}
if ($form_warehouse) {
    $where .= " AND di.warehouse_id IS NOT NULL AND di.warehouse_id = ?";
    $binds[] = $form_warehouse;
}
if ($form_show_inactive) {
    $where .= " AND d.active = 0";
} else {
    $where .= " AND d.active = 1";
}
if ($form_consumable) {
    if ($form_consumable == 1) {
        $where .= " AND d.consumable = '1'";
    } else {
        $where .= " AND d.consumable != '1'";
    }
}

$dion = $form_show_empty ? "" : "AND di.on_hand != 0";

// get drugs
$res = sqlStatement(
    "SELECT d.*, " .
    "di.inventory_id, di.lot_number, di.expiration, di.manufacturer, di.on_hand, " .
    "di.warehouse_id, lo.title, lo.option_value AS facid, f.name AS facname " .
    "FROM drugs AS d " .
    "LEFT JOIN drug_inventory AS di ON di.drug_id = d.drug_id " .
    "AND di.destroy_date IS NULL $dion " .
    "LEFT JOIN list_options AS lo ON lo.list_id = 'warehouse' AND " .
    "lo.option_id = di.warehouse_id AND lo.activity = 1 " .
    "LEFT JOIN facility AS f ON f.id = lo.option_value " .
    "LEFT JOIN list_options AS lof ON lof.list_id = 'drug_form' AND " .
    "lof.option_id = d.form AND lof.activity = 1 " .
    "$where ORDER BY d.active DESC, $orderby",
    $binds
);

function generateEmptyTd($n)
{
    $temp = '';
    while ($n > 0) {
        $temp .= "<td></td>";
        $n--;
    }
    echo $temp;
}
function processData($data)
{
    $data['inventory_id'] = [$data['inventory_id']];
    $data['lot_number'] = [$data['lot_number']];
    $data['facname'] =  [$data['facname']];
    $data['title'] =  [$data['title']];
    $data['on_hand'] = [$data['on_hand']];
    $data['expiration'] = [$data['expiration']];
    return $data;
}
function mergeData($d1, $d2)
{
    $d1['inventory_id'] = array_merge($d1['inventory_id'], $d2['inventory_id']);
    $d1['lot_number'] = array_merge($d1['lot_number'], $d2['lot_number']);
    $d1['facname'] = array_merge($d1['facname'], $d2['facname']);
    $d1['title'] = array_merge($d1['title'], $d2['title']);
    $d1['on_hand'] = array_merge($d1['on_hand'], $d2['on_hand']);
    $d1['expiration'] = array_merge($d1['expiration'], $d2['expiration']);
    return $d1;
}
function mapToTable($row)
{
    global $auth_admin, $auth_lots;
    $today = date('Y-m-d');
    if ($row) {
        echo " <tr class='detail'>\n";
        $lastid = $row['drug_id'];
        if ($auth_admin) {
            echo "<td title='" . xla('Click to edit') . "' onclick='dodclick(" . attr(addslashes($lastid)) . ")'>" .
            "<a href='' onclick='return false'>" .
            text($row['name']) . "</a></td>\n";
        } else {
            echo "  <td>" . text($row['name']) . "</td>\n";
        }
        echo "  <td>" . ($row['active'] ? xlt('Yes') : xlt('No')) . "</td>\n";
        echo "  <td>" . ($row['consumable'] ? xlt('Yes') : xlt('No')) . "</td>\n";
        echo "  <td>" . text($row['ndc_number']) . "</td>\n";
        echo "  <td>" .
        generate_display_field(array('data_type' => '1','list_id' => 'drug_form'), $row['form']) .
        "</td>\n";
        echo "  <td>" . text($row['size']) . "</td>\n";
        echo "  <td title='" . xla('Measurement Units') . "'>" .
        generate_display_field(array('data_type' => '1','list_id' => 'drug_units'), $row['unit']) .
        "</td>\n";

        if ($auth_lots && $row['dispensable']) {
            echo "  <td onclick='doiclick(" . intval($lastid) . ",0)' title='" .
                xla('Purchase or Transfer') . "' style='padding:0'>" .
                "<input type='button' value='" . xla('Tran') . "'style='padding:0' /></td>\n";
        } else {
            echo "  <td title='" . xla('Not applicable') . "'>&nbsp;</td>\n";
        }

        if (!empty($row['inventory_id'][0])) {
            echo "<td>";
            foreach ($row['inventory_id'] as $key => $value) {
                if ($auth_lots) {
                    echo "<div title='" .
                        xla('Adjustment, Consumption, Return, or Edit') .
                        "' onclick='doiclick(" . intval($lastid) . "," .
                        intval($row['inventory_id'][$key]) . ")'>" .
                        "<a href='' onclick='return false'>" .
                        text($row['lot_number'][$key]) .
                        "</a></div>";
                } else {
                    echo "  <div>" . text($row['lot_number'][$key]) . "</div>\n";
                }
            }
            echo "</td>\n<td>";

            foreach ($row['facname'] as $value) {
                $value = $value != null ? $value : "N/A";
                echo "<div >" .  text($value) . "</div>";
            }
            echo "</td>\n<td>";

            foreach ($row['title'] as $value) {
                $value = $value != null ? $value : "N/A";
                echo "<div >" .  text($value) . "</div>";
            }
            echo "</td>\n<td>";

            foreach ($row['on_hand'] as $value) {
                $value = $value != null ? $value : "N/A";
                echo "<div >" . text($value) . "</div>";
            }
            echo "</td>\n<td>";

            foreach ($row['expiration'] as $value) {
                // Make the expiration date red if expired.
                $expired = !empty($value) && strcmp($value, $today) <= 0;
                $value = !empty($value) ? oeFormatShortDate($value) : xl('N/A');
                echo "<div" . ($expired ? " style='color:red'" : "") . ">" . text($value) . "</div>";
            }
            echo "</td>\n";
        } else {
                generateEmptyTd(5);
        }
        echo " </tr>\n";
    }
}

function genWarehouseList($name, $default = '', $title = '', $class = '') {
    $s = "<select name='$name' id='$name' class='$class'";
    if ($title) {
        $s .= " title='" . attr($title) . "'";
    }
    $s .= ">\n";
    $s .= "<option value=''>" . xlt('None') . "</option>\n";

    // Get current facility
    $facility_id = $_SESSION['facility_id'] ?? 0;

    // Get warehouses for current facility
    $res = sqlStatement(
        "SELECT lo.*, f.name as facility_name FROM list_options AS lo " .
        "LEFT JOIN facility AS f ON f.id = lo.option_value " .
        "WHERE lo.list_id = 'warehouse' AND lo.activity = 1 " .
        "AND (lo.option_value = ? OR lo.option_value IS NULL) " .
        "ORDER BY lo.seq, lo.title",
        array($facility_id)
    );

    while ($row = sqlFetchArray($res)) {
        $s .= "<option value='" . attr($row['option_id']) . "'";
        if ($row['option_id'] == $default) {
            $s .= " selected";
        }
        $s .= ">" . text($row['title']);
        if (!empty($row['facility_name'])) {
            $s .= " (" . text($row['facility_name']) . ")";
        }
        $s .= "</option>\n";
    }
    $s .= "</select>\n";
    return $s;
}
?>
<html>

<head>

<title><?php echo xlt('Drug Inventory'); ?></title>

<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background-color: #f9f9fb;
  margin: 0;
  padding: 20px;
  font-size: 14px;
  color: #333;
}

h3, .inventory-heading {
  margin-bottom: 20px;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -1px;
  color: #222;
}

/* Filters and search bar row */
.filters, #inventory-filters, .dataTables_filter, .dataTables_length {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 20px;
}

/* Remove DataTables 'Show entries' dropdown from left */
.dataTables_length {
  display: none !important;
}

/* DataTables search bar left-aligned and prominent */
.dataTables_filter {
  flex: 1 1 100%;
  justify-content: flex-start !important;
  margin-bottom: 20px !important;
}
.dataTables_filter label {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 0;
  justify-content: flex-start;
}
.dataTables_filter input[type="search"] {
  padding: 12px 18px !important;
  border-radius: 14px !important;
  border: 1.5px solid #bfc4d1 !important;
  background-color: #f0f0f5 !important;
  font-size: 1.1rem !important;
  width: 320px !important;
  box-shadow: none !important;
  margin-left: 0 !important;
  font-weight: 500;
}

/* Modern dropdowns */
select, .filters select, #inventory-filters select, .dataTables_filter select, .dataTables_length select {
  padding: 12px 18px !important;
  border-radius: 14px !important;
  border: 1.5px solid #bfc4d1 !important;
  background-color: #f0f0f5 !important;
  font-size: 1.1rem !important;
  color: #222 !important;
  font-weight: 500;
  box-shadow: none !important;
  min-width: 160px;
  margin-right: 0;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
}

.filters input[type="text"],
#inventory-filters input[type="text"] {
  width: 320px;
  padding: 12px 18px;
  border-radius: 14px;
  border: 1.5px solid #bfc4d1;
  background-color: #f0f0f5;
  font-size: 1.1rem;
  font-weight: 500;
}

.filters button,
#inventory-filters button {
  background-color: #fff;
  cursor: pointer;
  padding: 12px 18px;
  border-radius: 14px;
  border: 1.5px solid #bfc4d1;
  font-size: 1.1rem;
  font-weight: 500;
  transition: background 0.2s, color 0.2s;
}
.filters button:hover,
#inventory-filters button:hover {
  background: #e0e7ff;
  color: #1976d2;
}

/* Inventory Table */
table, #mymaintable, table.dataTable {
  width: 100%;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}
th, td, #mymaintable th, #mymaintable td, table.dataTable th, table.dataTable td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}
th, #mymaintable th, table.dataTable th {
  background-color: #f3f4f6;
  font-weight: 600;
  border-right: 1.5px solid #e0e0e0;
}
th:last-child, #mymaintable th:last-child, table.dataTable th:last-child {
  border-right: none;
}
tr:hover, #mymaintable tr:hover, table.dataTable tr:hover {
  background-color: #f9fafb;
}
.expired {
  color: red;
  font-weight: bold;
}

/* Pagination */
.pagination, .dataTables_wrapper .dataTables_paginate {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-right: 10px;
}
.pagination .pages button,
.dataTables_wrapper .dataTables_paginate .paginate_button {
  padding: 5px 10px;
  margin-left: 4px;
  border: 1px solid #ccc !important;
  background: white !important;
  border-radius: 6px !important;
  color: #333 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.pagination .pages button:focus,
.pagination .pages button:hover {
  background: #e0e7ff !important;
  color: #1976d2 !important;
  font-weight: 600;
}

/* Add button and link styles */
.add-button {
  margin-top: 20px;
  padding: 10px 20px;
  border-radius: 10px;
  background-color: #007bff;
  color: white;
  border: none;
  font-size: 14px;
  cursor: pointer;
}
.link {
  color: #007bff;
  text-decoration: underline;
  cursor: pointer;
}

/* Responsive tweaks */
@media (max-width: 900px) {
  h3, .inventory-heading {
    font-size: 1.3rem;
  }
  th, td, #mymaintable th, #mymaintable td, table.dataTable th, table.dataTable td {
    font-size: 13px;
    padding: 8px 4px;
  }
  .filters, #inventory-filters, .dataTables_filter, .dataTables_length {
    flex-direction: column;
    align-items: stretch;
  }
  .dataTables_filter input[type="search"],
  .filters input[type="text"],
  #inventory-filters input[type="text"] {
    width: 100% !important;
    min-width: 0;
  }
  select, .filters select, #inventory-filters select, .dataTables_filter select, .dataTables_length select {
    min-width: 0;
    width: 100%;
  }
}

/* Add new styles for search box */
.filters input[type="text"] {
  width: 300px;
  height: 38px;
  padding: 8px 12px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  transition: border-color 0.15s ease-in-out;
}

.filters input[type="text"]:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.filters select {
  min-width: 200px;
  padding: 6px 12px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background-color: #fff;
}

.filters label {
  margin-bottom: 0;
  font-size: 14px;
}

.filters button {
  height: 38px;
  padding: 6px 12px;
  font-size: 14px;
}

/* Set search box height to 50% for .form-control.form-control-sm */
input[type="search"].form-control.form-control-sm {
    height: 50% !important;
}
</style>

<?php Header::setupHeader(['datatables', 'datatables-dt', 'datatables-bs', 'report-helper']); ?>

<script>

// callback from add_edit_drug.php or add_edit_drug_inventory.php:
function refreshme() {
 // Avoiding reload() here because it generates a browser warning about repeating a POST.
 location.href = location.href;
}

// Process click on drug title.
function dodclick(id) {
 dlgopen('add_edit_drug.php?drug=' + id, '_blank', 900, 600);
}

// Process click on drug QOO or lot.
function doiclick(id, lot) {
 dlgopen('add_edit_lot.php?drug=' + id + '&lot=' + lot, '_blank', 600, 475);
}

// Enable/disable warehouse options depending on current facility.
function facchanged() {
    var f = document.forms[0];
    var facid = f.form_facility.value;
    var theopts = f.form_warehouse.options;
    for (var i = 1; i < theopts.length; ++i) {
        var tmp = theopts[i].value.split('/');
        var dis = facid && (tmp.length < 2 || tmp[1] != facid);
        theopts[i].disabled = dis;
        if (dis) {
            theopts[i].selected = false;
        }
    }
}

$(function () {
  $('#mymaintable').DataTable({
            stripeClasses:['stripe1','stripe2'],
            orderClasses: false,
            <?php // Bring in the translations ?>
            <?php require($GLOBALS['srcdir'] . '/js/xl/datatables-net.js.php'); ?>
        });
});

window.addEventListener('DOMContentLoaded', function() {
    var productCheckbox = document.querySelector('input[name="form_show_inactive"]');
    if (productCheckbox) {
        productCheckbox.addEventListener('change', toggleInventoryFilters);
        toggleInventoryFilters();
    }
});
</script>

</head>

<body class="body_top">
<form method='post' action='drug_inventory.php' onsubmit='return top.restoreSession()'>

<h3 class="inventory-heading"><?php echo xlt('Inventory Management'); ?></h3>

<div class="filters">
  <!-- Facility Dropdown -->
  <?php
  $query = "SELECT id, name FROM facility ORDER BY name";
  $fres = sqlStatement($query);
  echo "<select name='form_facility' onchange='facchanged()'>\n";
  echo "<option value=''>-- " . xlt('All Facilities') . " --\n";
  while ($frow = sqlFetchArray($fres)) {
      $facid = $frow['id'];
      if ($is_user_restricted && !isFacilityAllowed($facid)) {
          continue;
      }
      echo "<option value='" . attr($facid) . '"';
      if ($facid == $form_facility) {
          echo " selected";
      }
      echo ">" . text($frow['name']) . "\n";
  }
  echo "</select>\n";
  ?>

  <!-- Warehouse Dropdown -->
  <?php
  echo "<select name='form_warehouse'>\n";
  echo "<option value=''>" . xlt('All Warehouses') . "</option>\n";
  $lres = sqlStatement(
      "SELECT * FROM list_options " .
      "WHERE list_id = 'warehouse' ORDER BY seq, title"
  );
  while ($lrow = sqlFetchArray($lres)) {
      $whid  = $lrow['option_id'];
      $facid = $lrow['option_value'];
      if ($is_user_restricted && !isWarehouseAllowed($facid, $whid)) {
          continue;
      }
      echo "<option value='" . attr("$whid/$facid") . '"';
      echo " id='fac" . attr($facid) . "'";
      if (strlen($form_warehouse)  > 0 && $whid == $form_warehouse) {
          echo " selected";
      }
      echo ">" . text(xl_list_label($lrow['title'])) . "</option>\n";
  }
  echo "</select>\n";
  ?>

  <!-- Show empty lots -->
  <label style="display:flex;align-items:center;gap:6px;margin-bottom:0;">
    <input type='checkbox' name='form_show_empty' value='1'<?php if ($form_show_empty) { echo " checked"; } ?> />
    <?php echo xlt('Medicine'); ?>
  </label>

  <!-- Show inactive -->
  <label style="display:flex;align-items:center;gap:6px;margin-bottom:0;">
    <input type='checkbox' name='form_show_inactive' value='1'<?php if ($form_show_inactive) { echo " checked"; } ?> />
    <?php echo xlt('Product'); ?>
  </label>

  <!-- Refresh Button -->
  <button type='submit' name='form_refresh'><?php echo xla('Refresh'); ?></button>
</div>

<!-- TODO: Why are we not using the BS4 table class here? !-->
<table id='mymaintable' class="table table-striped">
    <thead>
        <tr>
            <th><?php echo xlt('Name'); ?> </a></th>
            <th><?php echo xlt('Act'); ?></th>
            <th><?php echo xlt('Cons'); ?></th>
            <th><?php echo xlt('NDC'); ?> </a></th>
            <th><?php echo xlt('Form'); ?> </a></th>
            <th><?php echo xlt('Size'); ?></th>
            <th title='<?php echo xlt('Measurement Units'); ?>'><?php echo xlt('Unit'); ?></th>
            <th title='<?php echo xla('Purchase or Transfer'); ?>'><?php echo xlt('Tran'); ?></th>
            <th><?php echo xlt('Lot'); ?> </a></th>
            <th><?php echo xlt('Facility'); ?> </a></th>
            <th><?php echo xlt('Warehouse'); ?> </a></th>
            <th><?php echo xlt('QOH'); ?> </a></th>
            <th><?php echo xlt('Expires'); ?> </a></th>
        </tr>
    </thead>
 <tbody>
<?php
 $prevRow = '';
while ($row = sqlFetchArray($res)) {
    if (!empty($row['inventory_id']) && $is_user_restricted && !isWarehouseAllowed($row['facid'], $row['warehouse_id'])) {
        continue;
    }
    $row = processData($row);
    if ($prevRow == '') {
        $prevRow = $row;
        continue;
    }
    if ($prevRow['drug_id'] == $row['drug_id']) {
        $row = mergeData($prevRow, $row);
    } else {
        mapToTable($prevRow);
    }
    $prevRow = $row;
} // end while
mapToTable($prevRow);
?>
 </tbody>
</table>

<input class="btn btn-primary btn-block w-25 mx-auto" type='button' value='<?php echo xla('Add Drug'); ?>' onclick='dodclick(0)' />

<input type="hidden" name="form_orderby" value="<?php echo attr($form_orderby) ?>" />

<div class="form-group mt-3" style="display:none">
        <label>
            Templates:
        </label>
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th class="drugsonly">Name</th>
                    <th class="drugsonly">Schedule</th>
                    <th class="drugsonly">Interval</th>
                    <th class="drugsonly">Basic Units</th>
                    <th class="drugsonly">Refills</th>
                         <th>Standard</th>
                </tr>
            </thead>
            <tbody>
             <tr>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[1][selector]" value="" size="8" maxlength="100"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[1][dosage]" value="" size="6" maxlength="10"></td>
  <td class="tmplcell drugsonly"><select name="form_tmpl[1][period]" id="form_tmpl[1][period]" class="form-control" title=""><option value="0"></option>
<option value="1">b.i.d.</option>
<option value="2">t.i.d.</option>
<option value="3">q.i.d.</option>
<option value="4">q.3h</option>
<option value="5">q.4h</option>
<option value="6">q.5h</option>
<option value="7">q.6h</option>
<option value="8">q.8h</option>
<option value="9">Daily</option>
<option value="10">a.c.</option>
<option value="11">p.c.</option>
<option value="12">a.m.</option>
<option value="13">p.m.</option>
<option value="14">ante</option>
<option value="15">h</option>
<option value="16">h.s.</option>
<option value="17">p.r.n.</option>
<option value="18">stat</option>
<option value="19">Weekly</option>
<option value="20">Monthly</option></select></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[1][quantity]" value="" size="3" maxlength="7"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[1][refills]" value="" size="3" maxlength="5"></td>
  <td class="tmplcell"><input class="form-control" name="form_tmpl[1][price][standard]" value="" size="6" maxlength="12"></td>
 </tr>
 <tr>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[2][selector]" value="" size="8" maxlength="100"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[2][dosage]" value="" size="6" maxlength="10"></td>
  <td class="tmplcell drugsonly"><select name="form_tmpl[2][period]" id="form_tmpl[2][period]" class="form-control" title=""><option value="0"></option>
<option value="1">b.i.d.</option>
<option value="2">t.i.d.</option>
<option value="3">q.i.d.</option>
<option value="4">q.3h</option>
<option value="5">q.4h</option>
<option value="6">q.5h</option>
<option value="7">q.6h</option>
<option value="8">q.8h</option>
<option value="9">Daily</option>
<option value="10">a.c.</option>
<option value="11">p.c.</option>
<option value="12">a.m.</option>
<option value="13">p.m.</option>
<option value="14">ante</option>
<option value="15">h</option>
<option value="16">h.s.</option>
<option value="17">p.r.n.</option>
<option value="18">stat</option>
<option value="19">Weekly</option>
<option value="20">Monthly</option></select></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[2][quantity]" value="" size="3" maxlength="7"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[2][refills]" value="" size="3" maxlength="5"></td>
  <td class="tmplcell"><input class="form-control" name="form_tmpl[2][price][standard]" value="" size="6" maxlength="12"></td>
 </tr>
 <tr>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[3][selector]" value="" size="8" maxlength="100"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[3][dosage]" value="" size="6" maxlength="10"></td>
  <td class="tmplcell drugsonly"><select name="form_tmpl[3][period]" id="form_tmpl[3][period]" class="form-control" title=""><option value="0"></option>
<option value="1">b.i.d.</option>
<option value="2">t.i.d.</option>
<option value="3">q.i.d.</option>
<option value="4">q.3h</option>
<option value="5">q.4h</option>
<option value="6">q.5h</option>
<option value="7">q.6h</option>
<option value="8">q.8h</option>
<option value="9">Daily</option>
<option value="10">a.c.</option>
<option value="11">p.c.</option>
<option value="12">a.m.</option>
<option value="13">p.m.</option>
<option value="14">ante</option>
<option value="15">h</option>
<option value="16">h.s.</option>
<option value="17">p.r.n.</option>
<option value="18">stat</option>
<option value="19">Weekly</option>
<option value="20">Monthly</option></select></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[3][quantity]" value="" size="3" maxlength="7"></td>
  <td class="tmplcell drugsonly"><input class="form-control" name="form_tmpl[3][refills]" value="" size="3" maxlength="5"></td>
  <td class="tmplcell"><input class="form-control" name="form_tmpl[3][price][standard]" value="" size="6" maxlength="12"></td>
 </tr>
            </tbody>
        </table>
    </div>
<div class="form-group mt-3" style="display:none">
        <label>RXCUI Code:</label>
        <input class="form-control w-100" type="text" size="50" name="form_drug_code" value="&lt;br /&gt;&lt;b&gt;Warning&lt;/b&gt;:  Un" onclick="sel_related(&quot;?codetype=RXCUI&amp;limit=1&amp;target_element=form_drug_code&quot;)" title="" data-toggle="tooltip" data-placement="top" readonly="" data-original-title="Click to select RXCUI code">
    </div>

</form>

<script>
facchanged();
</script>

</body>
</html>
