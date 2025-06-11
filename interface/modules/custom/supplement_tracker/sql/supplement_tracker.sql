-- Create clinic_supplements table
CREATE TABLE IF NOT EXISTS `@prefix@clinic_supplements` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `facility_id` int(11) NOT NULL,
    `supplement_name` varchar(255) NOT NULL,
    `stock_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
    `unit` varchar(50) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `facility_id` (`facility_id`),
    CONSTRAINT `fk_clinic_supplements_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplement_usage table
CREATE TABLE IF NOT EXISTS `@prefix@supplement_usage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `patient_id` int(11) NOT NULL,
    `facility_id` int(11) NOT NULL,
    `supplement_id` int(11) NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `usage_date` date NOT NULL,
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `patient_id` (`patient_id`),
    KEY `facility_id` (`facility_id`),
    KEY `supplement_id` (`supplement_id`),
    CONSTRAINT `fk_supplement_usage_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_data` (`pid`) ON DELETE CASCADE,
    CONSTRAINT `fk_supplement_usage_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supplement_usage_supplement` FOREIGN KEY (`supplement_id`) REFERENCES `@prefix@clinic_supplements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_supplement_usage_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 