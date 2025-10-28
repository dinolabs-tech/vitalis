<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Fetch all salary records
$salaries = [];
$sql = "SELECT s.*, e.staffname AS employeeName, b.branch_name 
        FROM salary s 
        JOIN login e ON s.employee_id = e.id 
        LEFT JOIN branches b ON e.branch_id = b.branch_id";

$conditions = [];
$params = [];
$types = "";

// Filter by branch_id if the user is not an admin
if ($_SESSION['role'] !== 'admin' && isset($_SESSION['branch_id'])) {
    $conditions[] = "e.branch_id = ?";
    $params[] = $_SESSION['branch_id'];
    $types .= "i";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY s.salary_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$salaries = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
            <h3 class="fw-bold mb-3">General Staff Payroll</h3>
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
                <a href="#">Payroll</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">General Staff Payroll</a>
              </li>
            </ul>
             <div class="d-flex justify-content-end mb-3 ms-auto">
                <a href="generate_csv.php" class="btn btn-success btn-round" target="_blank"><i class="fas fa-file-csv"></i> Download CSV</a>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Employee Name</th>
                          <th>Basic Salary</th>
                          <th>Total Additions</th>
                          <th>Total Deductions</th>
                          <th>Net Salary</th>
                          <th>Branch</th>
                          <th>Salary Date</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if (count($salaries) > 0) {
                          foreach ($salaries as $row) {
                        ?>
                          <tr>
                            <td><?php echo $row['employeeName']; ?></td>
                            <td>$<?php echo $row['basic_salary']; ?></td>
                            <td>$<?php echo $row['total_additions']; ?></td>
                            <td>$<?php echo $row['total_deductions']; ?></td>
                            <td>$<?php echo $row['net_salary']; ?></td>
                            <td><?php echo htmlspecialchars($row['branch_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d', strtotime($row['salary_date'])); ?></td>
                            <td class="text-right d-flex">
                                <a href="salary-view.php?id=<?php echo $row['id']; ?>" class="btn-icon btn-round btn-primary text-white"><i class="fas fa-eye"></i></a>
                               </td>
                          </tr>
                        <?php
                          }
                        } else {
                          echo '<tr><td colspan="8" class="text-center">No payroll records found.</td></tr>';
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
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
