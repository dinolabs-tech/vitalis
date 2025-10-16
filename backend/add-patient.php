<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or receptionist role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist')) {
    header("Location: login.php");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $country = $_POST['country'] ?? '';
    $state = $_POST['state'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $genotype = $_POST['genotype'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
    $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? '';
    $branch_id = $_POST['branch_id'] ?? null;

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender) || empty($phone) || empty($address)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Generate a simple patient_id
        $patient_id = strtoupper('PT' . uniqid());

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into patients table
            $stmt_patient = $conn->prepare("INSERT INTO patients (patient_id, first_name, last_name, date_of_birth, gender, email, phone, address, country, state, blood_group, genotype, allergies, emergency_contact_name, emergency_contact_phone, account_status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
            $stmt_patient->bind_param(
                "ssssssssssssssss",
                $patient_id,
                $first_name,
                $last_name,
                $date_of_birth,
                $gender,
                $email,
                $phone,
                $address,
                $country,
                $state,
                $blood_group,
                $genotype,
                $allergies,
                $emergency_contact_name,
                $emergency_contact_phone,
                $branch_id
            );

            if (!$stmt_patient->execute()) {
                throw new Exception("Error creating patient record: " . $stmt_patient->error);
            }
            $stmt_patient->close();

            $conn->commit();
            $success_message = "Patient added successfully!";
            // Clear form fields
            $_POST = array();
            header("Location: patients.php?success=" . urlencode($success_message));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to add patient: " . $e->getMessage();
        }
    }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
if ($result_branches) {
    while ($row = $result_branches->fetch_assoc()) {
        $branches[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('components/head.php'); ?>

<body>
    <div class="wrapper">
        <?php include('components/sidebar.php'); ?>

        <div class="main-panel">
            <?php include('components/navbar.php'); ?>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Add Patient</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="index.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="patients.php">Patients</a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Add Patient</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">

                        <div class="col-md-12">
                            <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                            <div class="card">
                                <div class="card-title">
                                    <h6 class="text-danger mx-4 mt-3"><small>All placeholders with red border are compulsory</small></h6>
                                    <hr>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="row g-3">

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="first_name" placeholder="First Name" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="last_name" placeholder="Last Name" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input type="date" class="form-control" name="date_of_birth" placeholder="Date of Birth" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <select name="gender" id="" style="border: 1px solid red;" class="form-control form-select">
                                                <option value="" selected disabled>Select Gender</option>
                                                <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="phone" placeholder="Phone" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="address" placeholder="Address" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="country" placeholder="Country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="state" placeholder="State" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="blood_group" placeholder="Blood Group" value="<?php echo htmlspecialchars($_POST['blood_group'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" name="genotype" placeholder="Genotype" value="<?php echo htmlspecialchars($_POST['genotype'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <textarea class="form-control" placeholder="Allergies" name="allergies"><?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" placeholder="Emergency Contact Name" name="emergency_contact_name" value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <input class="form-control" type="text" placeholder="Emergency Contact Phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <select class="form-control form-select" name="branch_id">
                                                <option value="" selected disabled>Select Branch</option>
                                                <?php foreach ($branches as $branch): ?>
                                                    <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mt-2">
                                            <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-plus"></i></button>
                                        </div>

                                    </form>
                                </div>



                            </div>
                        </div>
                    </div>


                    
                </div>
            </div>

            <?php include('components/footer.php'); ?>
        </div>

    </div>
    <?php include('components/script.php'); ?>
</body>

</html>
