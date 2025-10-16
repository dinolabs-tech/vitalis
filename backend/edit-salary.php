<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$salary_id = $_GET['id'] ?? '';
$salary_record = null;

if (!empty($salary_id)) {
  $stmt = $conn->prepare("SELECT s.*, e.staffname AS employee_name, e.username AS employee_unique_id, e.email, e.created_at AS joining_date, e.role
                            FROM salary s
                            JOIN login e ON s.employee_id = e.id
                            WHERE s.id = ?");
  $stmt->bind_param("i", $salary_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $salary_record = $result->fetch_assoc();
  } else {
    $error_message = "Salary record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No salary ID provided.";
}

// Fetch employees for dropdown
$employees = [];
$result_employees = $conn->query("SELECT id, staffname AS name, username AS employee_id, email, created_at AS joining_date, role FROM login WHERE role != 'patient' ORDER BY staffname ASC");
if ($result_employees) {
  while ($row = $result_employees->fetch_assoc()) {
    $employees[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $salary_record) {
  $employee_id = $_POST['employee_id'] ?? '';
  $basic_salary = $_POST['basic_salary'] ?? '';
  $da = $_POST['da'] ?? 0;
  $hra = $_POST['hra'] ?? 0;
  $conveyance = $_POST['conveyance'] ?? 0;
  $allowance = $_POST['allowance'] ?? 0;
  $medical_allowance = $_POST['medical_allowance'] ?? 0;
  $others_additions = $_POST['others_additions'] ?? 0;
  $total_additions = $_POST['total_additions'] ?? 0;
  $tds = $_POST['tds'] ?? 0;
  $esi = $_POST['esi'] ?? 0;
  $pf = $_POST['pf'] ?? 0;
  $leave_deduction = $_POST['leave_deduction'] ?? 0;
  $prof_tax = $_POST['prof_tax'] ?? 0;
  $labour_welfare_fund = $_POST['labour_welfare_fund'] ?? 0;
  $others_deductions = $_POST['others_deductions'] ?? 0;
  $total_deductions = $_POST['total_deductions'] ?? 0;
  $gross_salary = $_POST['gross_salary'] ?? 0;
  $net_salary = $_POST['net_salary'] ?? 0;
  $salary_date = $_POST['salary_date'] ?? date('Y-m-d');
  $notes = $_POST['notes'] ?? '';

  if (empty($employee_id) || empty($basic_salary) || empty($salary_date)) {
    $error_message = "Please fill in all required fields (Employee, Basic Salary, Salary Date).";
  } elseif (!is_numeric($basic_salary) || $basic_salary < 0) {
    $error_message = "Basic Salary must be a non-negative number.";
  } else {
    $stmt = $conn->prepare("UPDATE salary SET employee_id = ?, basic_salary = ?, da = ?, hra = ?, conveyance = ?, allowance = ?, medical_allowance = ?, others_additions = ?, total_additions = ?, tds = ?, esi = ?, pf = ?, leave_deduction = ?, prof_tax = ?, labour_welfare_fund = ?, others_deductions = ?, total_deductions = ?, gross_salary = ?, net_salary = ?, salary_date = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("iddddddddddddddddddssi", $employee_id, $basic_salary, $da, $hra, $conveyance, $allowance, $medical_allowance, $others_additions, $total_additions, $tds, $esi, $pf, $leave_deduction, $prof_tax, $labour_welfare_fund, $others_deductions, $total_deductions, $gross_salary, $net_salary, $salary_date, $notes, $salary_id);

    if ($stmt->execute()) {
      $success_message = "Salary record updated successfully!";

      header("Location: salary.php");
      exit();
    } else {
      $error_message = "Error updating salary record: " . $stmt->error;
    }
    $stmt->close();
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
            <h4 class="page-title">Edit Salary</h4>
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
                <a href="salary.php">Salary</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Salary</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <?php if ($salary_record): ?>
                      <div class="form-group col-md-4">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="employee_id" id="employee_id_select" required>
                          <option value="">Select Employee</option>
                          <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>"
                              <?php echo ($salary_record['employee_id'] == $employee['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($employee['name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4 form-group">
                        <input class="form-control" placeholder="Basic Salary" style="border: 1px solid red;" type="text" name="basic_salary" id="basic_salary_input" value="<?php echo htmlspecialchars($salary_record['basic_salary']); ?>" required>
                      </div>
                      <div class="col-md-4 form-group">
                        <input class="form-control" placeholder="Salary Date" style="border: 1px solid red;" type="date" name="salary_date" value="<?php echo htmlspecialchars($salary_record['salary_date']); ?>" required>
                      </div>

                      <h4 class="card-title mx-3 col-md-12">Additions</h4>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Dearness Allowance</label>
                        <input class="form-control" placeholder="DA" type="text" name="da" value="<?php echo htmlspecialchars($salary_record['da']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>House Rent Allowance</label>
                        <input class="form-control" placeholder="HRA" type="text" name="hra" value="<?php echo htmlspecialchars($salary_record['hra']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Conveyance</label>
                        <input class="form-control" placeholder="Conveyance" type="text" name="conveyance" value="<?php echo htmlspecialchars($salary_record['conveyance']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Allowance</label>
                        <input class="form-control" placeholder="Allowance" type="text" name="allowance" value="<?php echo htmlspecialchars($salary_record['allowance']); ?>">
                      </div>
                      <div class="mt-3 col-md-4 form-group">
                        <label>Medical Allowance</label>
                        <input class="form-control" placeholder="Medical Allowance" type="text" name="medical_allowance" value="<?php echo htmlspecialchars($salary_record['medical_allowance']); ?>">
                      </div>
                      <div class="mt-3 col-md-4 form-group">
                        <label>Others</label>
                        <input class="form-control" placeholder="Others" type="text" name="others_additions" value="<?php echo htmlspecialchars($salary_record['others_additions']); ?>">
                      </div>
                      <div class="mt-3 col-md-4 form-group">
                        <label>Total Additions</label>
                        <input class="form-control" placeholder="Total Additions" type="text" name="total_additions" value="<?php echo htmlspecialchars($salary_record['total_additions']); ?>" readonly>
                      </div>

                      <h4 class="card-title mx-3 pt-3 col-md-12">Deductions</h4>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Tax Deducted at Source</label>
                        <input class="form-control" placeholder="TDS" type="text" name="tds" value="<?php echo htmlspecialchars($salary_record['tds']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Employee State Insurance</label>
                        <input class="form-control" placeholder="ESI" type="text" name="esi" value="<?php echo htmlspecialchars($salary_record['esi']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Provident Fund</label>
                        <input class="form-control" placeholder="PF" type="text" name="pf" value="<?php echo htmlspecialchars($salary_record['pf']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Leave Deduction</label>
                        <input class="form-control" placeholder="Leave Deduction" type="text" name="leave_deduction" value="<?php echo htmlspecialchars($salary_record['leave_deduction']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Professional Tax</label>
                        <input class="form-control" placeholder="Prof. Tax" type="text" name="prof_tax" value="<?php echo htmlspecialchars($salary_record['prof_tax']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Labour Welfare Fund</label>
                        <input class="form-control" placeholder="Labour Welfare Fund" type="text" name="labour_welfare_fund" value="<?php echo htmlspecialchars($salary_record['labour_welfare_fund']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Other Deductions</label>
                        <input class="form-control" placeholder="Others" type="text" name="others_deductions" value="<?php echo htmlspecialchars($salary_record['others_deductions']); ?>">
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Total Deductions</label>
                        <input class="form-control" placeholder="Total Deductions" type="text" name="total_deductions" value="<?php echo htmlspecialchars($salary_record['total_deductions']); ?>" readonly>
                      </div>

                      <div class="mt-3 col-md-3 form-group">
                        <label>Gross Salary</label>
                        <input class="form-control" placeholder="Gross Salary" type="text" name="gross_salary" value="<?php echo htmlspecialchars($salary_record['gross_salary']); ?>" readonly>
                      </div>
                      <div class="mt-3 col-md-3 form-group">
                        <label>Net Salary</label>
                        <input class="form-control" placeholder="Net Salary" type="text" name="net_salary" value="<?php echo htmlspecialchars($salary_record['net_salary']); ?>" readonly>
                      </div>
                      <div class="mt-3 col-md-6 form-group">
                        <label>Notes</label>
                        <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($salary_record['notes']); ?></textarea>
                      </div>

                      <div class="col-md-12 text-center">
                        <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p>No salary record to edit.</p>
                    <?php endif; ?>
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
  <script>
    $(document).ready(function() {
      function calculateSalary() {
        var basicSalary = parseFloat($('#basic_salary_input').val()) || 0;
        var da = parseFloat($('input[name="da"]').val()) || 0;
        var hra = parseFloat($('input[name="hra"]').val()) || 0;
        var conveyance = parseFloat($('input[name="conveyance"]').val()) || 0;
        var allowance = parseFloat($('input[name="allowance"]').val()) || 0;
        var medicalAllowance = parseFloat($('input[name="medical_allowance"]').val()) || 0;
        var othersAdditions = parseFloat($('input[name="others_additions"]').val()) || 0;

        var totalAdditions = da + hra + conveyance + allowance + medicalAllowance + othersAdditions;
        $('input[name="total_additions"]').val(totalAdditions.toFixed(2));

        var tds = parseFloat($('input[name="tds"]').val()) || 0;
        var esi = parseFloat($('input[name="esi"]').val()) || 0;
        var pf = parseFloat($('input[name="pf"]').val()) || 0;
        var leaveDeduction = parseFloat($('input[name="leave_deduction"]').val()) || 0;
        var profTax = parseFloat($('input[name="prof_tax"]').val()) || 0;
        var labourWelfareFund = parseFloat($('input[name="labour_welfare_fund"]').val()) || 0;
        var othersDeductions = parseFloat($('input[name="others_deductions"]').val()) || 0;

        var totalDeductions = tds + esi + pf + leaveDeduction + profTax + labourWelfareFund + othersDeductions;
        $('input[name="total_deductions"]').val(totalDeductions.toFixed(2));

        var grossSalary = basicSalary + totalAdditions;
        $('input[name="gross_salary"]').val(grossSalary.toFixed(2));

        var netSalary = grossSalary - totalDeductions;
        $('input[name="net_salary"]').val(netSalary.toFixed(2));
      }

      $('#employee_id_select').on('change', function() {
        var selectedEmployee = $(this).find('option:selected');
        var basicSalary = selectedEmployee.data('basic-salary');
        $('#basic_salary_input').val(basicSalary);
        calculateSalary();
      });

      $('input[name="basic_salary"], input[name="da"], input[name="hra"], input[name="conveyance"], input[name="allowance"], input[name="medical_allowance"], input[name="others_additions"], input[name="tds"], input[name="esi"], input[name="pf"], input[name="leave_deduction"], input[name="prof_tax"], input[name="labour_welfare_fund"], input[name="others_deductions"]').on('keyup change', calculateSalary);

      // Initial calculation
      calculateSalary();
    });
  </script>
</body>

</html>
