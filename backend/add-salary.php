<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();
if (isset($_POST['submit'])) {
  $employee_id = $_POST['employee_id'];
  $basic_salary = $_POST['basic_salary'];
  $da = $_POST['da'] ?? 0.00;
  $hra = $_POST['hra'] ?? 0.00;
  $conveyance = $_POST['conveyance'] ?? 0.00;
  $allowance = $_POST['allowance'] ?? 0.00;
  $medical_allowance = $_POST['medical_allowance'] ?? 0.00;
  $others_additions = $_POST['others_additions'] ?? 0.00;
  $total_additions = $_POST['total_additions'] ?? 0.00; // This will be calculated on client-side, but good to have a fallback
  $tds = $_POST['tds'] ?? 0.00;
  $esi = $_POST['esi'] ?? 0.00;
  $pf = $_POST['pf'] ?? 0.00;
  $leave_deduction = $_POST['leave_deduction'] ?? 0.00;
  $prof_tax = $_POST['prof_tax'] ?? 0.00;
  $labour_welfare_fund = $_POST['labour_welfare_fund'] ?? 0.00;
  $others_deductions = $_POST['others_deductions'] ?? 0.00;
  $total_deductions = $_POST['total_deductions'] ?? 0.00; // This will be calculated on client-side, but good to have a fallback
  $gross_salary = $_POST['gross_salary'] ?? 0.00; // This will be calculated on client-side, but good to have a fallback
  $net_salary = $_POST['net_salary'];
  $salary_date = $_POST['salary_date'];
  $notes = $_POST['notes'] ?? '';

  $query = "INSERT INTO salary (employee_id, basic_salary, da, hra, conveyance, allowance, medical_allowance, others_additions, total_additions, tds, esi, pf, leave_deduction, prof_tax, labour_welfare_fund, others_deductions, total_deductions, gross_salary, net_salary, salary_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('iddddddddddddddddddss', $employee_id, $basic_salary, $da, $hra, $conveyance, $allowance, $medical_allowance, $others_additions, $total_additions, $tds, $esi, $pf, $leave_deduction, $prof_tax, $labour_welfare_fund, $others_deductions, $total_deductions, $gross_salary, $net_salary, $salary_date, $notes);
  $stmt->execute();
  $stmt->close();
  header("Location: salary.php?success=" . urlencode("Salary added successfully"));
  exit();
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
            <h4 class="page-title">Add Salary</h4>
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
                <a href="#">Add Salary</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="post" class="row">

                    <div class="col-12">
                      <h5 class="form-title mx-3"><span>Salary Information</span></h5>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <select name="employee_id" class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" id="employee_id_select" required>
                          <option value="" selected disabled>Select Employee</option>
                          <?php
                          $ret = "SELECT id, staffname, username FROM login WHERE role != 'patient' ORDER BY staffname ASC";
                          $stmt = $mysqli->prepare($ret);
                          $stmt->execute();
                          $res = $stmt->get_result();
                          while ($row = $res->fetch_object()) {
                          ?>
                            <option value="<?php echo $row->id; ?>"><?php echo htmlspecialchars($row->staffname . ' (' . $row->username . ')'); ?></option>
                          <?php } ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" style="border: 1px solid red;" placeholder="Basic Salary" name="basic_salary" id="basic_salary_input" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="date" style="border: 1px solid red;" placeholder="Salary Date" name="salary_date" class="form-control" required>
                      </div>
                    </div>

                    <div class="col-md-12">
                      <h5 class="form-title mx-3"><span>Additions</span></h5>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Dearness Allowance (0.00)" name="da" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="House Rent Allowance (0.00)" name="hra" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Conveyance (0.00)" name="conveyance" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Allowance (0.00)" name="allowance" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Medical Allowance (0.00)" name="medical_allowance" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Other Additions (0.00)" name="others_additions" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Total Additions (0.00)" name="total_additions" class="form-control" readonly>
                      </div>
                    </div>

                    <div class="col-12">
                      <h5 class="form-title mx-3"><span>Deductions</span></h5>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Tax Deducted at Source (0.00)" name="tds" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Employee State Insurance (0.00)" name="esi" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Provident Fund (0.00)" name="pf" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Leave Deduction (0.00)" name="leave_deduction" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Professional Tax (0.00)" name="prof_tax" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Labour Welfare Fund (0.00)" name="labour_welfare_fund" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Other Deductions (0.00)" name="others_deductions" class="form-control">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Total Deductions (0.00)" name="total_deductions" class="form-control" readonly>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" name="gross_salary" placeholder="Gross Salary (0.00)" class="form-control" readonly>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Net Salary (0.00)" style="border: 1px solid red;" name="net_salary" class="form-control" required readonly>
                      </div>
                    </div>
                    <div class="col-md-8">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes" rows="3"></textarea>
                      </div>
                    </div>
                    <div class="col-md-12 text-center">
                      <button type="submit" name="submit" class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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
        // When employee changes, basic salary should be fetched from the database or set to 0
        // For now, we'll just recalculate with the current basic salary input
        calculateSalary();
      });

      $('input[name="basic_salary"], input[name="da"], input[name="hra"], input[name="conveyance"], input[name="allowance"], input[name="medical_allowance"], input[name="others_additions"], input[name="tds"], input[name="esi"], input[name="pf"], input[name="leave_deduction"], input[name="prof_tax"], input[name="labour_welfare_fund"], input[name="others_deductions"]').on('keyup change', calculateSalary);

      // Initial calculation
      calculateSalary();
    });
  </script>
</body>

</html>