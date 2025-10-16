<?php
session_start();
require_once 'database/db_connect.php'; // Adjust path as necessary

$leave = null;
$leave_types = [];
$employees = [];
$error_message = '';
$success_message = '';

// Fetch leave types for the dropdown
$stmt_leave_types = $conn->prepare("SELECT id, leaveType, leaveDays FROM leave_types WHERE status = 'Active'");
$stmt_leave_types->execute();
$result_leave_types = $stmt_leave_types->get_result();
while ($row = $result_leave_types->fetch_assoc()) {
  $leave_types[] = $row;
}
$stmt_leave_types->close();

// Fetch employees for the dropdown (assuming 'login' table stores employees)
$stmt_employees = $conn->prepare("SELECT id, staffname FROM login WHERE role != 'patient' AND status = 'active'");
$stmt_employees->execute();
$result_employees = $stmt_employees->get_result();
while ($row = $result_employees->fetch_assoc()) {
  $employees[] = $row;
}
$stmt_employees->close();


// Handle form submission for updating leave
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $leave_id = $_POST['leave_id'] ?? null;
  $employee_id = $_POST['employee_id'] ?? null;
  $leave_type_id = $_POST['leave_type_id'] ?? null;
  $from_date = $_POST['from_date'] ?? null;
  $to_date = $_POST['to_date'] ?? null;
  $reason = $_POST['reason'] ?? null;
  $num_days = $_POST['num_days'] ?? null; // This should be calculated or validated

  if ($leave_id && $employee_id && $leave_type_id && $from_date && $to_date && $reason && $num_days) {
    $stmt = $conn->prepare("UPDATE leaves SET employeeId = ?, leaveTypeId = ?, fromDate = ?, toDate = ?, reason = ?, numDays = ? WHERE id = ?");
    $stmt->bind_param("iissssi", $employee_id, $leave_type_id, $from_date, $to_date, $reason, $num_days, $leave_id);

    if ($stmt->execute()) {
      $success_message = "Leave updated successfully!";
      header("Location: leaves.php");
      exit();
    } else {
      $error_message = "Error updating leave: " . $stmt->error;
    }
    $stmt->close();
  } else {
    $error_message = "All fields are required.";
  }
}

// Fetch existing leave data if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $leave_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT l.*, lt.leaveType, lt.leaveDays AS maxLeaveDays, e.staffname AS employeeName FROM leaves l JOIN leave_types lt ON l.leaveTypeId = lt.id JOIN login e ON l.employeeId = e.id WHERE l.id = ?");
  $stmt->bind_param("i", $leave_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $leave = $result->fetch_assoc();
  } else {
    $error_message = "Leave not found.";
  }
  $stmt->close();
} else if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
  $error_message = "No leave ID provided.";
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
            <h4 class="page-title">Edit Leave</h4>
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
                <a href="leaves.php">Leaves</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Leave</a>
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
                  <form method="POST" action="">
                    <input type="hidden" name="leave_id" value="<?php echo $leave['id'] ?? ''; ?>">
                    <div class="form-group">
                      <select class="form-control from-select" style="border:1px solid red;" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                          <option value="<?php echo $employee['id']; ?>" <?php echo (isset($leave['employeeId']) && $leave['employeeId'] == $employee['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($employee['staffname']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <select class="form-control from-select" style="border:1px solid red;" name="leave_type_id" required>
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leave_types as $type): ?>
                          <option value="<?php echo $type['id']; ?>" data-leavedays="<?php echo $type['leaveDays']; ?>" <?php echo (isset($leave['leaveTypeId']) && $leave['leaveTypeId'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['leaveType']) . ' (' . $type['leaveDays'] . ' Days)'; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" name="from_date" style="border:1px solid red;" placeholder="From" value="<?php echo $leave['fromDate'] ?? ''; ?>" type="date" required>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" style="border:1px solid red;" placeholder="To" name="to_date" value="<?php echo $leave['toDate'] ?? ''; ?>" type="date" required>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" name="num_days" style="border:1px solid red;" placeholder="Number of days" readonly type="text" value="<?php echo $leave['numDays'] ?? ''; ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" name="remaining_leaves" placeholder="Remaining Leaves" style="border:1px solid red;" readonly value="<?php echo (isset($leave['maxLeaveDays']) && isset($leave['numDays'])) ? ($leave['maxLeaveDays'] - $leave['numDays']) : ''; ?>" type="text">
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <textarea rows="4" cols="5" class="form-control" placeholder="Leave Reason" name="reason" style="border:1px solid red;" required><?php echo $leave['reason'] ?? ''; ?></textarea>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round" type="submit"><i class="fas fa-save"></i></button>
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
    document.addEventListener('DOMContentLoaded', function() {
      const fromDateInput = document.querySelector('input[name="from_date"]');
      const toDateInput = document.querySelector('input[name="to_date"]');
      const leaveTypeSelect = document.querySelector('select[name="leave_type_id"]');
      const numDaysInput = document.querySelector('input[name="num_days"]');
      const remainingLeavesInput = document.querySelector('input[name="remaining_leaves"]');

      function calculateLeaveDays() {
        const fromDate = new Date(fromDateInput.value);
        const toDate = new Date(toDateInput.value);

        if (fromDate && toDate && fromDate <= toDate) {
          const timeDiff = toDate.getTime() - fromDate.getTime();
          const diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
          numDaysInput.value = diffDays;

          updateRemainingLeaves(diffDays);
        } else {
          numDaysInput.value = '';
          remainingLeavesInput.value = '';
        }
      }

      function updateRemainingLeaves(currentNumDays) {
        const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
        const maxLeaveDays = parseInt(selectedOption.dataset.leavedays || 0);

        if (!isNaN(maxLeaveDays) && currentNumDays !== '') {
          remainingLeavesInput.value = maxLeaveDays - currentNumDays;
        } else {
          remainingLeavesInput.value = '';
        }
      }

      fromDateInput.addEventListener('change', calculateLeaveDays);
      toDateInput.addEventListener('change', calculateLeaveDays);
      leaveTypeSelect.addEventListener('change', function() {
        calculateLeaveDays(); // Recalculate num_days and remaining_leaves when leave type changes
      });

      // Initial calculation on page load if dates are already set
      if (fromDateInput.value && toDateInput.value) {
        calculateLeaveDays();
      }
    });
  </script>
</body>

</html>