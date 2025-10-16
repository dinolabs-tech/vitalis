<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}


// Fetch appointment data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $department_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
  $stmt->bind_param("i", $department_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $department = $result->fetch_assoc();
    $department_id = $department['id'];
    $name = $department['name'];
    $description = $department['description'];
  } else {
    $error_message = "Department not found.";
  }
  $stmt->close();
}

// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : '';
  $name = isset($_POST['name']) ? $_POST['name'] : '';
  $description = isset($_POST['description']) ? $_POST['description'] : '';

  $stmt = $conn->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
  $stmt->bind_param("sss", $name, $description, $department_id);

  if ($stmt->execute()) {
    $success_message = "Department updated successfully!";
    header("Location: departments.php");
    exit();
  } else {
    $error_message = "Error updating department: " . $conn->error;
  }
  $stmt->close();
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
            <h4 class="page-title">Edit Department</h4>
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
                <a href="departments.php">Departments</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Department</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
                <?php if (!empty($success_message)): ?>
                  <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                  <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                  <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($department_id); ?>">
                  <div class="form-group">
                    <input class="form-control" placeholder="Department Name" style="border: 1px solid red;" name="name" type="text" value="<?= htmlspecialchars($name) ?>">
                  </div>
                  <div class="form-group">
                    <textarea cols="30" rows="4" placeholder="Description" name="description" class="form-control"><?= htmlspecialchars($description) ?></textarea>
                  </div>

                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                  </div>
                </form>
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