<?php
/**
 * Supplement Tracker Admin Interface
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
if (!AclMain::aclCheckCore('admin', 'supplement_tracker_admin')) {
    die(xlt('Not authorized'));
}

// Get current facility
$facility_id = $_SESSION['facility_id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CsrfUtils::verifyCsrfToken($_POST['csrf_token_form'])) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $sql = "INSERT INTO clinic_supplements (facility_id, supplement_name, stock_qty, unit) 
                        VALUES (?, ?, ?, ?)";
                sqlStatement($sql, [
                    $facility_id,
                    $_POST['supplement_name'],
                    $_POST['stock_qty'],
                    $_POST['unit']
                ]);
                break;
                
            case 'update':
                $sql = "UPDATE clinic_supplements 
                        SET supplement_name = ?, stock_qty = ?, unit = ? 
                        WHERE id = ? AND facility_id = ?";
                sqlStatement($sql, [
                    $_POST['supplement_name'],
                    $_POST['stock_qty'],
                    $_POST['unit'],
                    $_POST['id'],
                    $facility_id
                ]);
                break;
                
            case 'delete':
                $sql = "DELETE FROM clinic_supplements WHERE id = ? AND facility_id = ?";
                sqlStatement($sql, [$_POST['id'], $facility_id]);
                break;
        }
    }
}

// Get supplements for current facility
$sql = "SELECT * FROM clinic_supplements WHERE facility_id = ? ORDER BY supplement_name";
$supplements = sqlStatement($sql, [$facility_id]);

// Get facilities for dropdown
$facilities = getFacilities();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Supplement Tracker'); ?></title>
    <?php Header::setupHeader(['bootstrap', 'datatables', 'fontawesome']); ?>
    <style>
        .supplement-form { margin-bottom: 20px; }
        .stock-warning { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1><?php echo xlt('Supplement Tracker'); ?></h1>
        
        <!-- Facility Selector -->
        <div class="row mb-3">
            <div class="col-md-4">
                <select id="facility_selector" class="form-control">
                    <?php foreach ($facilities as $fac) { ?>
                        <option value="<?php echo attr($fac['id']); ?>" 
                                <?php echo $fac['id'] == $facility_id ? 'selected' : ''; ?>>
                            <?php echo text($fac['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <!-- Add Supplement Form -->
        <div class="card supplement-form">
            <div class="card-header">
                <h4><?php echo xlt('Add New Supplement'); ?></h4>
            </div>
            <div class="card-body">
                <form method="post" id="add_supplement_form">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="supplement_name"><?php echo xlt('Supplement Name'); ?></label>
                                <input type="text" class="form-control" id="supplement_name" name="supplement_name" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="stock_qty"><?php echo xlt('Stock Quantity'); ?></label>
                                <input type="number" class="form-control" id="stock_qty" name="stock_qty" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="unit"><?php echo xlt('Unit'); ?></label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <?php echo xlt('Add'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Supplements Table -->
        <div class="card">
            <div class="card-header">
                <h4><?php echo xlt('Current Supplements'); ?></h4>
            </div>
            <div class="card-body">
                <table id="supplements_table" class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo xlt('Supplement Name'); ?></th>
                            <th><?php echo xlt('Stock Quantity'); ?></th>
                            <th><?php echo xlt('Unit'); ?></th>
                            <th><?php echo xlt('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlFetchArray($supplements)) { ?>
                            <tr>
                                <td><?php echo text($row['supplement_name']); ?></td>
                                <td class="<?php echo $row['stock_qty'] < 10 ? 'stock-warning' : ''; ?>">
                                    <?php echo text($row['stock_qty']); ?>
                                </td>
                                <td><?php echo text($row['unit']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-supplement" 
                                            data-id="<?php echo attr($row['id']); ?>"
                                            data-name="<?php echo attr($row['supplement_name']); ?>"
                                            data-qty="<?php echo attr($row['stock_qty']); ?>"
                                            data-unit="<?php echo attr($row['unit']); ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-supplement"
                                            data-id="<?php echo attr($row['id']); ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo xlt('Edit Supplement'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="post" id="edit_supplement_form">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="form-group">
                            <label for="edit_supplement_name"><?php echo xlt('Supplement Name'); ?></label>
                            <input type="text" class="form-control" id="edit_supplement_name" name="supplement_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_stock_qty"><?php echo xlt('Stock Quantity'); ?></label>
                            <input type="number" class="form-control" id="edit_stock_qty" name="stock_qty" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_unit"><?php echo xlt('Unit'); ?></label>
                            <input type="text" class="form-control" id="edit_unit" name="unit" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <?php echo xlt('Close'); ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo xlt('Save Changes'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#supplements_table').DataTable({
                "language": {
                    "url": "<?php echo $GLOBALS['webroot']; ?>/library/datatables/media/js/datatables-language.js"
                }
            });

            // Facility selector change
            $('#facility_selector').change(function() {
                window.location.href = 'index.php?facility_id=' + $(this).val();
            });

            // Edit supplement
            $('.edit-supplement').click(function() {
                var data = $(this).data();
                $('#edit_id').val(data.id);
                $('#edit_supplement_name').val(data.name);
                $('#edit_stock_qty').val(data.qty);
                $('#edit_unit').val(data.unit);
                $('#editModal').modal('show');
            });

            // Delete supplement
            $('.delete-supplement').click(function() {
                if (confirm(<?php echo xlj('Are you sure you want to delete this supplement?'); ?>)) {
                    var form = $('<form method="post">')
                        .append($('<input type="hidden" name="csrf_token_form">').val('<?php echo attr(CsrfUtils::collectCsrfToken()); ?>'))
                        .append($('<input type="hidden" name="action">').val('delete'))
                        .append($('<input type="hidden" name="id">').val($(this).data('id')));
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 