// Add clinic_id to drug_inventory table
$sql = "ALTER TABLE `drug_inventory` ADD COLUMN `clinic_id` int(11) NOT NULL DEFAULT 0 AFTER `vendor_id`, ADD KEY `clinic_id` (`clinic_id`)";
sqlStatement($sql); 