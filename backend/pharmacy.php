<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or pharmacist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'pharmacist'])) {
    header("Location: login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle dispense request
if (isset($_POST['dispense_id']) && isset($_POST['quantity']) && isset($_POST['patient_id']) && isset($_POST['drug_id'])) {
    $dispense_id = $_POST['dispense_id'];
    $patient_id = $_POST['patient_id'];
    $drug_id = $_POST['drug_id'];
    $quantity = (int)$_POST['quantity'];
    $pharmacist_id = $_SESSION['id']; // Assuming pharmacist's ID is stored in session

    if ($quantity <= 0) {
        $_SESSION['error_message'] = "Quantity must be a positive number.";
        header("Location: pharmacy.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Fetch drug details (name and unit_price) from products table
        $stmt_product = $conn->prepare("SELECT name, sell_price, qty FROM products WHERE id = ?");
        $stmt_product->bind_param("i", $drug_id);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        if ($result_product->num_rows === 0) {
            throw new Exception("Drug not found in products.");
        }
        $product_data = $result_product->fetch_assoc();
        $medication_name = $product_data['name'];
        $unit_price = $product_data['sell_price'];
        $current_stock = $product_data['qty'];
        $stmt_product->close();

        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for " . htmlspecialchars($medication_name) . ". Available: " . $current_stock . ", Requested: " . $quantity);
        }

        // 2. Update drug_consultations table
        $stmt_consultation = $conn->prepare("UPDATE drug_consultations SET dispensed = 1, dispensed_by = ?, dispense_date = NOW() WHERE id = ?");
        $stmt_consultation->bind_param("ii", $pharmacist_id, $dispense_id);
        if (!$stmt_consultation->execute()) {
            throw new Exception("Error updating drug consultation: " . $stmt_consultation->error);
        }
        $stmt_consultation->close();

        // 3. Insert into patient_bills table
        $item_type = 'medication';
        $description = "Dispensed: " . $medication_name;
        $total_amount = $quantity * $unit_price;
        $status = 'pending';
        $bill_date = date('Y-m-d H:i:s');

        $stmt_bill = $conn->prepare("INSERT INTO patient_bills (patient_id, item_type, item_id, description, quantity, unit_price, total_amount, status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Note: patient_id in patient_bills is VARCHAR(255), but in drug_consultations it's VARCHAR(255) and links to patients.patient_id (VARCHAR).
        // The patient_id from the form is the patient's unique ID string, not the auto-incremented 'id' from the patients table.
        // We need to get the 'id' from the patients table using the patient_id string.
        $stmt_get_patient_internal_id = $conn->prepare("SELECT id FROM patients WHERE patient_id = ?");
        $stmt_get_patient_internal_id->bind_param("s", $patient_id);
        $stmt_get_patient_internal_id->execute();
        $result_patient_internal_id = $stmt_get_patient_internal_id->get_result();
        if ($result_patient_internal_id->num_rows === 0) {
            throw new Exception("Patient not found for billing.");
        }
        $patient_internal_id_row = $result_patient_internal_id->fetch_assoc();
        $patient_internal_id = $patient_internal_id_row['id'];
        $stmt_get_patient_internal_id->close();

        $stmt_bill->bind_param("isissddss", $patient_internal_id, $item_type, $drug_id, $description, $quantity, $unit_price, $total_amount, $status, $bill_date);
        if (!$stmt_bill->execute()) {
            throw new Exception("Error adding to patient bills: " . $stmt_bill->error);
        }
        $stmt_bill->close();

        // 4. Update products stock quantity
        $stmt_update_stock = $conn->prepare("UPDATE products SET qty = qty - ? WHERE id = ?");
        $stmt_update_stock->bind_param("ii", $quantity, $drug_id);
        if (!$stmt_update_stock->execute()) {
            throw new Exception("Error updating product stock: " . $stmt_update_stock->error);
        }
        $stmt_update_stock->close();

        $conn->commit();
        $_SESSION['success_message'] = "Drug dispensed and billed successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to dispense drug: " . $e->getMessage();
    }
    header("Location: pharmacy.php");
    exit;
}

// Handle delete request (similar to drug_consultation.php)
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM drug_consultations WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Drug consultation record deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting record: " . $stmt->error;
    }
    $stmt->close();
    header("Location: pharmacy.php");
    exit;
}

// Placeholder for drug consultation data retrieval
$drug_consultations = [];
$sql = "SELECT dc.*,
            p.first_name,
            p.last_name,
            l.staffname AS doctor_name,
            pr.name AS medication_name
        FROM drug_consultations dc
        LEFT JOIN patients p ON dc.patient_id = p.patient_id
        LEFT JOIN login l ON dc.doctor_id = l.id
        LEFT JOIN products pr ON dc.drug_id = pr.id
        ORDER BY dc.consultation_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $drug_consultations[] = $row;
    }
}

// Retrieve messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
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
                        <h4 class="fw-bold mb-3">Pharmacy Management</h4>
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
                                <a href="#">Pharmacy</a>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div class="ms-md-auto py-2 py-md-0">
                            <a href="add-drug-consultation.php" class="btn btn-primary btn-round">Add Drug Consultation</a>
                        </div>
                    </div>
                    <div class="card p-3">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-border table-striped" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th>Patient Name</th>
                                                <th>Doctor Name</th>
                                                <th>Drug Name</th>
                                                <th>Consultation Date</th>
                                                <th>Notes</th>
                                                <th>Status</th>
                                                <th class="text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($drug_consultations)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No drug consultations found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($drug_consultations as $consult): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($consult['first_name'] . ' ' . $consult['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($consult['doctor_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($consult['medication_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($consult['consultation_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($consult['consultation_notes']); ?></td>
                                                        <td>
                                                            <?php if ($consult['dispensed']): ?>
                                                                <span class="badge bg-success">Dispensed</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-right d-flex">
                                                            <?php if (!$consult['dispensed']): ?>
                                                                <button type="button" class="btn btn-success btn-icon btn-round text-white mx-2 btn-dispense-drug"
                                                                    data-id="<?php echo $consult['id']; ?>"
                                                                    data-patient-id="<?php echo htmlspecialchars($consult['patient_id']); ?>"
                                                                    data-drug-id="<?php echo htmlspecialchars($consult['drug_id']); ?>"
                                                                    data-medication-name="<?php echo htmlspecialchars($consult['medication_name']); ?>"
                                                                    title="Dispense Drug"><i class="fas fa-prescription-bottle"></i></button>
                                                            <?php endif; ?>
                                                            <a href="edit-drug-consultation.php?id=<?php echo $consult['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2" title="Edit Consultation"><i class="fas fa-edit"></i></a>
                                                            <a href="#" data-id="<?php echo $consult['id']; ?>" data-patient-name="<?php echo htmlspecialchars($consult['first_name'] . ' ' . $consult['last_name']); ?>" data-medication-name="<?php echo htmlspecialchars($consult['medication_name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-consultation" title="Delete Consultation"><i class="fas fa-trash"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Delete Modal -->
            <div id="delete_drug_consultation_modal" class="modal fade" role="dialog">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <img src="assets/img/sent.png" alt="" width="50" height="46">
                            <h3 id="delete-drug-consultation-message">Are you sure you want to delete this Drug Consultation Record?</h3>
                            <div class="m-t-20">
                                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                                <form id="deleteDrugConsultationForm" method="POST" action="pharmacy.php" style="display: inline;">
                                    <input type="hidden" name="delete_id" id="delete-drug-consultation-id">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('components/footer.php'); ?>
        </div>
    </div>

    <!-- Dispense Drug Modal -->
    <div id="dispense_drug_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Dispense Drug</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="dispenseDrugForm" method="POST" action="pharmacy.php">
                        <input type="hidden" name="dispense_id" id="dispense-consultation-id">
                        <input type="hidden" name="patient_id" id="dispense-patient-id">
                        <input type="hidden" name="drug_id" id="dispense-drug-id">
                        <div class="form-group">
                            <label for="medication-name">Medication:</label>
                            <input type="text" class="form-control" id="dispense-medication-name" readonly>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <input type="number" class="form-control" id="dispense-quantity" name="quantity" min="1" required>
                        </div>
                        <div class="m-t-20 text-center">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Dispense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include('components/script.php'); ?>
    <script>
        $(document).ready(function() {
            $('#basic-datatables').DataTable(); // Initialize DataTable

            $('.btn-delete-consultation').on('click', function(e) {
                e.preventDefault();
                var consultationId = $(this).data('id');
                var patientName = $(this).data('patient-name');
                var medicationName = $(this).data('medication-name');
                $('#delete-drug-consultation-id').val(consultationId);
                $('#delete-drug-consultation-message').text("Are you sure you want to delete the drug consultation for '" + patientName + "' regarding '" + medicationName + "'?");
                $('#delete_drug_consultation_modal').modal('show');
            });

            $('.btn-dispense-drug').on('click', function() {
                var consultationId = $(this).data('id');
                var patientId = $(this).data('patient-id');
                var drugId = $(this).data('drug-id');
                var medicationName = $(this).data('medication-name');

                $('#dispense-consultation-id').val(consultationId);
                $('#dispense-patient-id').val(patientId);
                $('#dispense-drug-id').val(drugId);
                $('#dispense-medication-name').val(medicationName);
                $('#dispense-quantity').val(1); // Default quantity to 1
                $('#dispense_drug_modal').modal('show');
            });
        });
    </script>
</body>
</html>
