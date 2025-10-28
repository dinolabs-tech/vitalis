<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require_once 'db_connect.php';


function createTable($conn, $sql, $tableName)
{
    if ($conn->query($sql) === TRUE) {
        // echo "Table '$tableName' created successfully or already exists.<br>";
        return true;
    } else {
        echo "Error creating table '$tableName': " . $conn->error . "<br>";
        error_log("Error creating table '$tableName': " . $conn->error);
        return false;
    }
}


function insertAdminUser($conn, $username, $password, $email, $role, $country = "N/A", $state = "N/A", $branch_id = NULL)
{
    // Check if the user already exists
    $check_stmt = $conn->prepare("SELECT id FROM login WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // User already exists, do not create
        // echo "Admin user '$username' already exists.<br>";
        $check_stmt->close();
        return;
    }
    $check_stmt->close();

    // User does not exist, proceed with insertion
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO login (staffname, username, password, email, address, mobile, country, state, role, status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $default_address = "N/A";
    $default_mobile = "N/A";
    $default_status = "active";
    $stmt->bind_param("sssssssssss", $username, $username, $hashed_password, $email, $default_address, $default_mobile, $country, $state, $role, $default_status, $branch_id);

    if ($stmt->execute()) {
        // echo "Default admin user '$username' created successfully.<br>";
    } else {
        echo "Error creating default admin user '$username': " . $stmt->error . "<br>";
        error_log("Error creating default admin user '$username': " . $stmt->error);
    }
    $stmt->close();
}

// Array of table schemas
$tables = [
    [
        'name' => 'branches',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `branches` (
                `branch_id` INT AUTO_INCREMENT PRIMARY KEY,
                `branch_name` VARCHAR(255) NOT NULL UNIQUE,
                `address` TEXT,
                `phone` VARCHAR(100),
                `email` VARCHAR(255),
                `state` VARCHAR(100) NULL,
                `country` VARCHAR(100) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'login',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `login` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `staffname` VARCHAR(100) NOT NULL,
                `username` VARCHAR(100) NOT NULL,
                `password` VARCHAR(100) NOT NULL,
                `fingerprint_data` TEXT DEFAULT NULL,
                `reset_token` varchar(255) DEFAULT NULL,
                `reset_token_expiry` datetime DEFAULT NULL,
                `email` VARCHAR(100) NOT NULL,
                `specialization` VARCHAR(225) NOT NULL,
                `license_number` VARCHAR(225) NOT NULL,
                `address` VARCHAR(225) NOT NULL,
                `mobile` VARCHAR(100) NOT NULL,
                `country` VARCHAR(100) NOT NULL,
                `state` VARCHAR(100) NOT NULL,
                `profile_picture` VARCHAR(255) DEFAULT 'default.jpg',
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `role` VARCHAR(100) NOT NULL,
                `staff_type` ENUM('doctor', 'nurse', 'admin', 'pharmacist', 'lab_technician', 'receptionist') NOT NULL DEFAULT 'admin',
                `department_id` INT DEFAULT NULL,
                `branch_id` INT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `salary` DECIMAL(10, 2) DEFAULT 0.00,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'departments',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `departments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL UNIQUE,
            `description` TEXT,
            `branch_id` INT NULL,
            `staff_id` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'patients',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `patients` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL UNIQUE,
                `first_name` VARCHAR(255) NOT NULL,
                `last_name` VARCHAR(255) NOT NULL,
                `date_of_birth` DATE NOT NULL,
                `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
                `email` VARCHAR(255) UNIQUE,
                `phone` VARCHAR(100) NOT NULL,
                `address` TEXT NOT NULL,
                `country` VARCHAR(100) NOT NULL,
                `state` VARCHAR(100),
                `blood_group` VARCHAR(10),
                `genotype` VARCHAR(10),
                `allergies` TEXT,
                `emergency_contact_name` VARCHAR(255),
                `emergency_contact_phone` VARCHAR(100),
                `profile_picture` VARCHAR(255) DEFAULT 'default.jpg',
                `account_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'payments',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `paymentId` VARCHAR(255) NOT NULL UNIQUE,
                `invoiceId` INT NOT NULL,
                `paymentDate` DATE NOT NULL,
                `amount` DECIMAL(10, 2) NOT NULL,
                `paymentMethod` VARCHAR(255) NOT NULL,
                `transactionId` VARCHAR(255) NULL,
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'payroll',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `payroll` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `staff_id` INT NOT NULL,
                `month` INT NOT NULL,
                `year` INT NOT NULL,
                `total_hours_worked` DECIMAL(10, 2) DEFAULT 0.00,
                `gross_salary` DECIMAL(10, 2) DEFAULT 0.00,
                `deductions` DECIMAL(10, 2) DEFAULT 0.00,
                `net_salary` DECIMAL(10, 2) DEFAULT 0.00,
                `payment_date` DATE NULL,
                `status` ENUM('pending', 'paid', 'generated') NOT NULL DEFAULT 'generated',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE (`staff_id`, `month`, `year`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'products',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT(222) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(222) NOT NULL,
                `sku` VARCHAR(222) NOT NULL,
                `location` VARCHAR(222) NOT NULL,
                `unit_price` DECIMAL(10, 2) NOT NULL,
                `sell_price` DECIMAL(10, 2) NOT NULL,
                `qty` INT NOT NULL,
                `total` DECIMAL(10, 2) NOT NULL,
                `profit` DECIMAL(10, 2) NOT NULL,
                `description` TEXT NOT NULL,
                `reorder_level` INT NOT NULL,
                `reorder_qty` INT NOT NULL,
                `product_type` ENUM('medication', 'supply', 'equipment', 'service') NOT NULL DEFAULT 'supply',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `branch_id` INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'orders',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                `total_amount` DECIMAL(10, 2) NOT NULL,
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'order_items',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `product_id` INT NULL,
                `service_id` INT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `price` DECIMAL(10, 2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'settings',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(255) NOT NULL UNIQUE,
                `setting_value` VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'medical_records',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `medical_records` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `doctor_id` INT NOT NULL,
                `appointment_id` INT NULL,
                `record_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `diagnosis` TEXT,
                `treatment` TEXT,
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'appointments',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `appointments` (
                `id` INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
                `patient_id` varchar(255) NOT NULL,
                `doctor_id` int(11) DEFAULT NULL,
                `appointment_date` datetime NOT NULL,
                `reason` text DEFAULT NULL,
                `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
                `branch_id` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'rooms',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `rooms` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `room_number` VARCHAR(50) NOT NULL UNIQUE,
                `room_type` VARCHAR(100) NOT NULL,
                `capacity` INT NOT NULL DEFAULT 1,
                `room_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `bed_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `status` ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'admissions',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `admissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `room_id` INT NOT NULL,
                `admission_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `discharge_date` DATETIME NULL,
                `reason` TEXT,
                `status` ENUM('admitted', 'discharged', 'transferred') NOT NULL DEFAULT 'admitted',
                `admitted_by_staff_id` INT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'opd_visits',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `opd_visits` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` INT NOT NULL,
                `visit_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `doctor_id` INT NULL,
                `reason_for_visit` TEXT,
                `symptoms` TEXT,
                `diagnosis` TEXT,
                `treatment` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'ipd_admissions',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `ipd_admissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `admission_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `discharge_date` DATETIME NULL,
                `room_id` INT NULL,
                `doctor_id` INT NULL,
                `reason_for_admission` TEXT,
                `diagnosis` TEXT,
                `treatment` TEXT,
                `notes` TEXT,
                `status` ENUM('admitted', 'discharged', 'transferred') NOT NULL DEFAULT 'admitted',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'lab_tests',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `lab_tests` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` INT NOT NULL,
                `doctor_id` INT NULL,
                `test_name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `test_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                `branch_id` INT NULL,
                `performed_by_staff_id` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'services',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `services` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `service_name` VARCHAR(255) NOT NULL UNIQUE,
                `description` TEXT,
                `price` DECIMAL(10, 2) NOT NULL,
                `department_id` INT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'invoices',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `invoices` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `invoiceId` VARCHAR(50) NOT NULL UNIQUE,   -- e.g. INV-0001
            `patientId` INT NOT NULL,
            `doctorId` INT NULL,
            `invoiceDate` DATE NOT NULL,
            `dueDate` DATE NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            `tax` DECIMAL(10,2) NOT NULL,
            `totalAmount` DECIMAL(10,2) NOT NULL,
            `status` ENUM('Pending', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Pending',
            `notes` TEXT NULL,
            `branch_id` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'invoice_items',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `invoice_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `invoiceId` INT NOT NULL,
            `itemName` VARCHAR(255) NOT NULL, 
            `description` TEXT NULL,
            `unitCost` DECIMAL(10,2) NOT NULL,
            `quantity` INT NOT NULL,
            `total` DECIMAL(10,2) GENERATED ALWAYS AS (`unitCost` * `quantity`) STORED
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'invoice_payments',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `invoice_payments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `invoiceId` INT NOT NULL,
            `bankName` VARCHAR(255) NOT NULL,
            `country` VARCHAR(100) NOT NULL,
            `city` VARCHAR(100) NOT NULL,
            `address` VARCHAR(255) NOT NULL,
            `iban` VARCHAR(50) NOT NULL,
            `swiftCode` VARCHAR(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'medications',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `medications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT NOT NULL,
                `dosage` VARCHAR(255),
                `administration_method` VARCHAR(255),
                `side_effects` TEXT,
                `expiry_date` DATE,
                `storage_conditions` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'expenses',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `expenses` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `itemName` VARCHAR(255) NOT NULL,
                `purchaseFrom` VARCHAR(255) NOT NULL,
                `purchaseDate` DATE NOT NULL,
                `amount` DECIMAL(10, 2) NOT NULL,
                `paidBy` VARCHAR(255) NOT NULL,
                `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
                `notes` TEXT NULL,
                `branch_id` INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'audit_logs',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `log_id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NULL,
                `userName` VARCHAR(255) NOT NULL,
                `action` VARCHAR(255) NOT NULL,
                `module` VARCHAR(255) NOT NULL,
                `actionDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `ipAddress` VARCHAR(45) NOT NULL,
                `details` TEXT NULL,
                `branch_id` INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'stock_transfers',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `stock_transfers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `from_branch_id` INT NOT NULL,
                `to_branch_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `quantity` INT NOT NULL,
                `transfer_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `notes` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'session_logs',
        'sql' => "
            CREATE TABLE IF NOT EXISTS session_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            event_type VARCHAR(50) NOT NULL,  -- 'login', 'logout', 'timeout', 'hijack'
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            branch_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'branch_product_inventory',
        'sql' => "
            CREATE TABLE IF NOT EXISTS branch_product_inventory (
                inventory_id INT AUTO_INCREMENT PRIMARY KEY,
                branch_id INT NOT NULL,
                productid INT NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'vaccinations',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `vaccinations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `vaccine_name` VARCHAR(255) NOT NULL,
                `administration_date` DATE NOT NULL,
                `administered_by_staff_id` INT NULL,
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'test_samples',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `test_samples` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `lab_test_id` INT NOT NULL,
                `sample_type` VARCHAR(100) NOT NULL,
                `collection_date` DATETIME NOT NULL,
                `collected_by_staff_id` INT NULL,
                `status` ENUM('collected', 'in_progress', 'analyzed', 'rejected') NOT NULL DEFAULT 'collected',
                `results_file_path` VARCHAR(255) NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'staff_attendance',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `staff_attendance` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `staff_id` INT NOT NULL,
                `date` DATE NOT NULL,
                `punch_in` TIME NULL,
                `punch_out` TIME NULL,
                `production_time` VARCHAR(50) NULL,
                `break_time` VARCHAR(50) NULL,
                `overtime` VARCHAR(50) NULL,
                `status` ENUM('present', 'absent', 'on_leave') NOT NULL DEFAULT 'absent',
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'radiology_records',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `radiology_records` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` INT NOT NULL,
                `doctor_id` INT NULL,
                `test_name` VARCHAR(255) NOT NULL,
                `test_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `description` TEXT,
                `radiology_image_path` VARCHAR(255) NULL,
                `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'operations',
        'sql' => "
               CREATE TABLE IF NOT EXISTS `operations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `doctor_id` INT NULL,
                `room_id` INT NULL,
                `operation_date` DATE NOT NULL,
                `start_time` TIME NULL,
                `end_time` TIME NULL,
                `procedure_name` VARCHAR(255) NOT NULL,
                `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'icu_patient_monitoring',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `icu_patient_monitoring` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `admission_id` INT NOT NULL,
                `parameter_name` VARCHAR(255) NOT NULL,
                `value` VARCHAR(255) NOT NULL,
                `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'er_visits',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `er_visits` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `arrival_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `chief_complaint` TEXT NOT NULL,
                `triage_level` ENUM('resuscitation', 'emergency', 'urgency', 'less_urgent', 'non_urgent') NOT NULL DEFAULT 'non_urgent',
                `discharge_time` DATETIME NULL,
                `initial_findings` TEXT,
                `subsequent_care` VARCHAR(255),
                `outcome` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'doctor_notes',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `doctor_notes` (
                `note_id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` INT NOT NULL,
                `doctor_id` INT NOT NULL,
                `note_title` VARCHAR(255) NOT NULL,
                `note_content` TEXT NOT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'prescriptions',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `prescriptions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` INT NOT NULL,
                `medication_id` INT NOT NULL,
                `dosage` VARCHAR(255) NOT NULL,
                `prescription_date` DATETIME NOT NULL,
                `notes` TEXT,
                `doctor_id` INT NOT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'patient_vitals',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `patient_vitals` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `recorded_by_staff_id` INT NULL,
                `temperature` DECIMAL(5, 2) NULL,
                `blood_pressure_systolic` INT NULL,
                `blood_pressure_diastolic` INT NULL,
                `heart_rate` INT NULL,
                `respiration_rate` INT NULL,
                `weight_kg` DECIMAL(6, 2) NULL,
                `height_cm` DECIMAL(6, 2) NULL,
                `blood_oxygen_saturation` INT NULL,
                `notes` TEXT,
                `branch_id` INT NULL,
                `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'patient_bills',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `patient_bills` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `admission_id` INT NULL,
                `item_type` ENUM('room_charge', 'medication', 'service', 'lab_test', 'other') NOT NULL,
                `item_id` INT NULL, -- Corresponds to room_id, product_id, service_id, lab_test_id etc.
                `description` TEXT NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `unit_price` DECIMAL(10, 2) NOT NULL,
                `total_amount` DECIMAL(10, 2) NOT NULL,
                `bill_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'drug_consultations',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `drug_consultations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(255) NOT NULL,
                `doctor_id` INT NOT NULL,
                `drug_id` INT NOT NULL,
                `consultation_notes` TEXT,
                `consultation_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `dispensed` TINYINT(1) DEFAULT 0,
                `dispensed_by` INT NULL,
                `dispense_date` DATETIME NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'balance_sheets',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `balance_sheets` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `date` DATE NOT NULL,
                `assets` DECIMAL(10, 2) NOT NULL,
                `liabilities` DECIMAL(10, 2) NOT NULL,
                `equity` DECIMAL(10, 2) NOT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'schedules',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `schedules` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `doctor_id` INT NOT NULL,
                `available_days` VARCHAR(255),
                `start_time` TIME,
                `end_time` TIME,
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'holidays',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `holidays` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `holidayName` VARCHAR(255) NOT NULL,
                `holidayDate` DATE NOT NULL,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'doctor_schedules',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `doctor_schedules` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `doctor_id` INT NOT NULL,
                `day_of_week` ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `doctor_day` (`doctor_id`, `day_of_week`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'leaves',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `leaves` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `employeeId` INT NOT NULL,
                `leaveTypeId` INT NOT NULL,
                `fromDate` DATE NOT NULL,
                `toDate` DATE NOT NULL,
                `reason` TEXT,
                `numDays` VARCHAR(255) NOT NULL,
                `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'leave_types',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `leave_types` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `leaveType` VARCHAR(255) NOT NULL UNIQUE,
                `leaveDays` INT NOT NULL DEFAULT 0,
                `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
                `description` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'provident_fund',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `provident_fund` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `employeeId` INT NOT NULL,
                `providentFundAmount` DECIMAL(10, 2) NOT NULL,
                `employeeShare` DECIMAL(10, 2) NOT NULL,
                `organizationShare` DECIMAL(10, 2) NOT NULL,
                `description` TEXT,
                `branch_id` INT NULL,
                `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'salary',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `salary` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `employee_id` INT NOT NULL,
                `basic_salary` DECIMAL(10, 2) NOT NULL,
                `da` DECIMAL(10, 2) DEFAULT 0.00,
                `hra` DECIMAL(10, 2) DEFAULT 0.00,
                `conveyance` DECIMAL(10, 2) DEFAULT 0.00,
                `allowance` DECIMAL(10, 2) DEFAULT 0.00,
                `medical_allowance` DECIMAL(10, 2) DEFAULT 0.00,
                `others_additions` DECIMAL(10, 2) DEFAULT 0.00,
                `total_additions` DECIMAL(10, 2) DEFAULT 0.00,
                `tds` DECIMAL(10, 2) DEFAULT 0.00,
                `esi` DECIMAL(10, 2) DEFAULT 0.00,
                `pf` DECIMAL(10, 2) DEFAULT 0.00,
                `leave_deduction` DECIMAL(10, 2) DEFAULT 0.00,
                `prof_tax` DECIMAL(10, 2) DEFAULT 0.00,
                `labour_welfare_fund` DECIMAL(10, 2) DEFAULT 0.00,
                `others_deductions` DECIMAL(10, 2) DEFAULT 0.00,
                `total_deductions` DECIMAL(10, 2) DEFAULT 0.00,
                `gross_salary` DECIMAL(10, 2) DEFAULT 0.00,
                `net_salary` DECIMAL(10, 2) NOT NULL,
                `salary_date` DATE NOT NULL,
                `notes` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    [
        'name' => 'taxes',
        'sql' => "
            CREATE TABLE IF NOT EXISTS `taxes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `tax_name` VARCHAR(255) NOT NULL UNIQUE,
                `tax_rate` DECIMAL(5, 2) NOT NULL,
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `description` TEXT,
                `branch_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ]
];

// Execute table creation
foreach ($tables as $table) {
    createTable($conn, $table['sql'], $table['name']);

    // Special handling for settings table to insert default delivery fee and OPD visit fee
    if ($table['name'] === 'settings') {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('delivery_fee', '1000') ON DUPLICATE KEY UPDATE setting_key=setting_key");
        $stmt->execute();
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('opd_visit_fee', '500') ON DUPLICATE KEY UPDATE setting_key=setting_key");
        $stmt->execute();
        // Add default language setting
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('default_language', 'en') ON DUPLICATE KEY UPDATE setting_key=setting_key");
        $stmt->execute();
        // Add default currency setting
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('default_currency', 'USD') ON DUPLICATE KEY UPDATE setting_key=setting_key");
        $stmt->execute();
    }
}

// Create default admin user
insertAdminUser($conn, 'dinolabs', 'dinolabs', 'admin@dinolabs.com', 'admin', 'N/A', 'N/A', NULL);

// Close the connection
$conn = null;
