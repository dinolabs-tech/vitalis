<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or doctor role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';
$doctors = [];
$sql = "SELECT l.*, b.branch_name 
        FROM login l 
        LEFT JOIN branches b ON l.branch_id = b.branch_id 
        WHERE l.role = 'doctor'";

// Filter by branch_id if the user is not an admin
if ($_SESSION['role'] !== 'admin' && isset($_SESSION['branch_id'])) {
    $sql .= " AND l.branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['branch_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    // Delete from doctors table first (due to foreign key constraint)
    $stmt_doctor = $conn->prepare("DELETE FROM doctors WHERE staff_id = ?");
    $stmt_doctor->bind_param("i", $delete_id);
    if (!$stmt_doctor->execute()) {
      throw new Exception("Error deleting doctor profile: " . $stmt_doctor->error);
    }
    $stmt_doctor->close();

    // Then delete from login table
    $stmt_login = $conn->prepare("DELETE FROM login WHERE id = ? AND role = 'doctor'");
    $stmt_login->bind_param("i", $delete_id);
    if (!$stmt_login->execute()) {
      throw new Exception("Error deleting login account: " . $stmt_login->error);
    }
    $stmt_login->close();

    $conn->commit();
    $success_message = "Doctor deleted successfully!";
    header("Location: doctors.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete doctor: " . $stmt->error;
    header("Location: doctors.php?error=" . urlencode($error_message));
    exit;
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
            <h3 class="fw-bold mb-3">Doctors</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Doctors</a>
              </li>
            </ul>
          </div>

        
          <div class="row g-3">
            <?php foreach ($doctors as $doctor): ?>
            <div class="col-md-3">
              <div class="card align-items-center">
                <div class="card-body text-center">
                  <img src="assets/img/profile/<?php echo htmlentities($doctor['profile_picture']); ?>" alt="profile picture" class="rounded-circle">
                  <h5><?php echo htmlspecialchars($doctor['staffname'])?></h5>
                  <h6><?php echo htmlspecialchars($doctor['specialization'])?></h6>
                  <h6><?php echo htmlspecialchars($doctor['mobile'])?></h6>
                  <h6><?php echo htmlspecialchars($doctor['branch_name'] ?? 'N/A')?></h6>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
         
          
        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-doctor').on('click', function(e) {
        e.preventDefault();
        var doctorId = $(this).data('id');
        $('#delete-doctor-id').val(doctorId);
        $('#delete_doctor').modal('show');
      });
    });
  </script>
</body>

</html>
