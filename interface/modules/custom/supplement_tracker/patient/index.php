<?php
/**
 * Supplement Tracker Patient Interface
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Your Name <your.email@example.com>
 * @copyright Copyright (c) 2024 Your Name
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../../../globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/options.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

// Check authorization
if (!AclMain::aclCheckCore('patients', 'supplement_tracker_view')) {
    die(xlt('Not authorized'));
}

// Get patient ID
$pid = $_GET['pid'] ?? 0;
if (!$pid) {
    die(xlt('No patient selected'));
}

// Get current facility
$facility_id = $_SESSION['facility_id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CsrfUtils::verifyCsrfToken($_POST['csrf_token_form'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'assign') {
        // Start transaction
        sqlBeginTrans();
        
        try {
            // Insert usage record
            $sql = "INSERT INTO supplement_usage (patient_id, facility_id, supplement_id, quantity, usage_date, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            sqlStatement($sql, [
                $pid,
                $facility_id,
                $_POST['supplement_id'],
                $_POST['quantity'],
                $_POST['usage_date'],
                $_POST['notes'],
                $_SESSION['authUserID']
            ]);
            
            // Update stock quantity
            $sql = "UPDATE clinic_supplements 
                    SET stock_qty = stock_qty - ? 
                    WHERE id = ? AND facility_id = ?";
            sqlStatement($sql, [
                $_POST['quantity'],
                $_POST['supplement_id'],
                $facility_id
            ]);
            
            sqlCommitTrans();
        } catch (Exception $e) {
            sqlRollbackTrans();
            die(xlt('Error assigning supplement: ') . $e->getMessage());
        }
    }
}

// Get patient info
$patient = getPatientData($pid);

// Get available supplements for current facility
$sql = "SELECT * FROM clinic_supplements 
        WHERE facility_id = ? AND stock_qty > 0 
        ORDER BY supplement_name";
$supplements = sqlStatement($sql, [$facility_id]);

// Get patient's supplement history
$sql = "SELECT su.*, cs.supplement_name, cs.unit 
        FROM supplement_usage su 
        JOIN clinic_supplements cs ON su.supplement_id = cs.id 
        WHERE su.patient_id = ? 
        ORDER BY su.usage_date DESC";
$history = sqlStatement($sql, [$pid]);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Patient Supplements'); ?></title>
    <?php Header::setupHeader(['bootstrap', 'datatables', 'fontawesome']); ?>
    <style>
        .supplement-form { margin-bottom: 20px; }
        .stock-warning { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1><?php echo xlt('Patient Supplements'); ?></h1>
        <h3><?php echo text($patient['fname'] . ' ' . $patient['lname']); ?></h3>
        
        <!-- Assign Supplement Form -->
        <div class="card supplement-form">
            <div class="card-header">
                <h4><?php echo xlt('Assign Supplement'); ?></h4>
            </div>
            <div class="card-body">
                <form method="post" id="assign_supplement_form">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>">
                    <input type="hidden" name="action" value="assign">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="supplement_id"><?php echo xlt('Supplement'); ?></label>
                                <select class="form-control" id="supplement_id" name="supplement_id" required>
                                    <option value=""><?php echo xlt('Select Supplement'); ?></option>
                                    <?php while ($row = sqlFetchArray($supplements)) { ?>
                                        <option value="<?php echo attr($row['id']); ?>"
                                                data-stock="<?php echo attr($row['stock_qty']); ?>"
                                                data-unit="<?php echo attr($row['unit']); ?>">
                                            <?php echo text($row['supplement_name']); ?> 
                                            (<?php echo text($row['stock_qty'] . ' ' . $row['unit']); ?>)
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="quantity"><?php echo xlt('Quantity'); ?></label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="usage_date"><?php echo xlt('Date'); ?></label>
                                <input type="date" class="form-control" id="usage_date" name="usage_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="notes"><?php echo xlt('Notes'); ?></label>
                                <input type="text" class="form-control" id="notes" name="notes">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Supplement History -->
        <div class="card">
            <div class="card-header">
                <h4><?php echo xlt('Supplement History'); ?></h4>
            </div>
            <div class="card-body">
                <table id="history_table" class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo xlt('Date'); ?></th>
                            <th><?php echo xlt('Supplement'); ?></th>
                            <th><?php echo xlt('Quantity'); ?></th>
                            <th><?php echo xlt('Notes'); ?></th>
                            <th><?php echo xlt('Assigned By'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlFetchArray($history)) { ?>
                            <tr>
                                <td><?php echo text(oeFormatShortDate($row['usage_date'])); ?></td>
                                <td><?php echo text($row['supplement_name']); ?></td>
                                <td><?php echo text($row['quantity'] . ' ' . $row['unit']); ?></td>
                                <td><?php echo text($row['notes']); ?></td>
                                <td><?php echo text(getProviderName($row['created_by'])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#history_table').DataTable({
                "language": {
                    "url": "<?php echo $GLOBALS['webroot']; ?>/library/datatables/media/js/datatables-language.js"
                },
                "order": [[0, "desc"]]
            });

            // Supplement selection change
            $('#supplement_id').change(function() {
                var option = $(this).find('option:selected');
                var stock = parseFloat(option.data('stock'));
                var unit = option.data('unit');
                
                // Update quantity field max value
                $('#quantity').attr('max', stock);
                
                // Update quantity field placeholder
                $('#quantity').attr('placeholder', 'Max: ' + stock + ' ' + unit);
            });

            // Form validation
            $('#assign_supplement_form').submit(function(e) {
                var quantity = parseFloat($('#quantity').val());
                var stock = parseFloat($('#supplement_id option:selected').data('stock'));
                
                if (quantity > stock) {
                    e.preventDefault();
                    alert(<?php echo xlj('Quantity cannot exceed available stock'); ?>);
                }
            });
        });
    </script>
</body>
</html> 