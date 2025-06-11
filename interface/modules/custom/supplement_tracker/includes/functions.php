<?php
/**
 * Supplement Tracker Helper Functions
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Your Name <your.email@example.com>
 * @copyright Copyright (c) 2024 Your Name
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Common\Acl\AclMain;

/**
 * Get provider name by ID
 *
 * @param int $provider_id Provider ID
 * @return string Provider name
 */
function getProviderName($provider_id) {
    $sql = "SELECT fname, lname FROM users WHERE id = ?";
    $result = sqlQuery($sql, [$provider_id]);
    return $result ? $result['fname'] . ' ' . $result['lname'] : '';
}

/**
 * Get supplement stock level
 *
 * @param int $supplement_id Supplement ID
 * @param int $facility_id Facility ID
 * @return array|false Supplement data or false if not found
 */
function getSupplementStock($supplement_id, $facility_id) {
    $sql = "SELECT * FROM clinic_supplements WHERE id = ? AND facility_id = ?";
    return sqlQuery($sql, [$supplement_id, $facility_id]);
}

/**
 * Update supplement stock
 *
 * @param int $supplement_id Supplement ID
 * @param int $facility_id Facility ID
 * @param float $quantity Quantity to add (positive) or subtract (negative)
 * @return bool Success
 */
function updateSupplementStock($supplement_id, $facility_id, $quantity) {
    $sql = "UPDATE clinic_supplements 
            SET stock_qty = stock_qty + ? 
            WHERE id = ? AND facility_id = ?";
    return sqlStatement($sql, [$quantity, $supplement_id, $facility_id]);
}

/**
 * Get patient supplement history
 *
 * @param int $patient_id Patient ID
 * @param int $limit Optional limit
 * @return array Supplement history
 */
function getPatientSupplementHistory($patient_id, $limit = null) {
    $sql = "SELECT su.*, cs.supplement_name, cs.unit 
            FROM supplement_usage su 
            JOIN clinic_supplements cs ON su.supplement_id = cs.id 
            WHERE su.patient_id = ? 
            ORDER BY su.usage_date DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        return sqlStatement($sql, [$patient_id, $limit]);
    }
    
    return sqlStatement($sql, [$patient_id]);
}

/**
 * Get facility supplements
 *
 * @param int $facility_id Facility ID
 * @param bool $in_stock_only Only return supplements with stock > 0
 * @return array Facility supplements
 */
function getFacilitySupplements($facility_id, $in_stock_only = false) {
    $sql = "SELECT * FROM clinic_supplements WHERE facility_id = ?";
    
    if ($in_stock_only) {
        $sql .= " AND stock_qty > 0";
    }
    
    $sql .= " ORDER BY supplement_name";
    return sqlStatement($sql, [$facility_id]);
}

/**
 * Add supplement usage record
 *
 * @param array $data Usage data
 * @return bool Success
 */
function addSupplementUsage($data) {
    $sql = "INSERT INTO supplement_usage (
                patient_id, facility_id, supplement_id, 
                quantity, usage_date, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    return sqlStatement($sql, [
        $data['patient_id'],
        $data['facility_id'],
        $data['supplement_id'],
        $data['quantity'],
        $data['usage_date'],
        $data['notes'],
        $data['created_by']
    ]);
}

/**
 * Check if user has required permissions
 *
 * @param string $permission Permission to check
 * @return bool Has permission
 */
function checkSupplementPermission($permission) {
    return AclMain::aclCheckCore('patients', $permission);
} 