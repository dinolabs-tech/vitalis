<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('database/db_connect.php');
include('includes/config.php');

if (!isset($_SESSION['id'])) {
  header('Location: login.php');
  exit();
}

$user_branch_id = $_SESSION['branch_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';

// Initialize variables for metrics
$totalPatients = 0;
$totalDoctors = 0;
$totalAppointments = 0;
$occupiedRooms = 0;
$availableRooms = 0;
$appointmentsToday = 0;
$pendingLabTests = 0;
$pendingRadiology = 0;
$totalPendingBills = 0;
$totalPaidBills = 0;
$totalCancelledBills = 0;
$expectedMonthlyPayroll = 0;
$predictedAdmissions = 0;
$predictedAdmissionsNextMonth = 0;

// Doctor-specific metrics
$doctorTotalAppointmentsToday = 0;
$doctorTotalPatientsAttended = 0;

// Prepare branch filter for SQL queries
$branch_filter_condition = ''; // Renamed to hold just the condition
$branch_params = [];
$param_types = '';

if ($user_role !== 'admin' || ($user_role === 'admin' && $user_branch_id !== null)) {
    $branch_filter_condition = "branch_id = ?"; // Just the condition, no WHERE
    $branch_params = [$user_branch_id];
    $param_types = 'i';
}

// Helper function to execute queries with optional branch filter
function execute_filtered_query($mysqli, $base_query, $branch_condition_param, $branch_params, $param_types) {
    $query_parts = [];
    $query_parts['select'] = $base_query;
    $query_parts['where'] = '';
    $query_parts['group_by'] = '';
    $query_parts['order_by'] = '';
    $query_parts['limit'] = '';

    // Parse the base query to identify existing clauses
    // Use non-capturing groups for keywords and capture only the conditions/clauses
    if (preg_match('/^(.*?)(?:\sWHERE\s(.*?))?(?:\sGROUP\sBY\s(.*?))?(?:\sORDER\sBY\s(.*?))?(?:\sLIMIT\s(.*?))?$/is', $base_query, $matches)) {
        $query_parts['select'] = trim($matches[1]);
        if (isset($matches[2]) && !empty($matches[2])) $query_parts['where'] = trim($matches[2]); // Capture only condition
        if (isset($matches[3]) && !empty($matches[3])) $query_parts['group_by'] = trim($matches[3]);
        if (isset($matches[4]) && !empty($matches[4])) $query_parts['order_by'] = trim($matches[4]);
        if (isset($matches[5]) && !empty($matches[5])) $query_parts['limit'] = trim($matches[5]);
    }

    // Apply branch filter
    if (!empty($branch_condition_param)) {
        if (!empty($query_parts['where'])) {
            $query_parts['where'] .= " AND " . $branch_condition_param;
        } else {
            $query_parts['where'] = $branch_condition_param; // Only the condition
        }
    }

    // Reconstruct the query
    $final_query = $query_parts['select'];
    if (!empty($query_parts['where'])) $final_query .= " WHERE " . $query_parts['where']; // Add WHERE keyword explicitly
    if (!empty($query_parts['group_by'])) $final_query .= " GROUP BY " . $query_parts['group_by'];
    if (!empty($query_parts['order_by'])) $final_query .= " ORDER BY " . $query_parts['order_by'];
    if (!empty($query_parts['limit'])) $final_query .= " LIMIT " . $query_parts['limit'];

    if (!empty($branch_params)) {
        $stmt = $mysqli->prepare($final_query);
        if ($stmt === false) {
            error_log("Prepare failed: " . $mysqli->error . " Query: " . $final_query);
            return false;
        }
        $stmt->bind_param($param_types, ...$branch_params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    } else {
        return $mysqli->query($final_query);
    }
}

// Fetch general metrics
$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM patients", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $totalPatients = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM login WHERE role ='doctor'", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $totalDoctors = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM appointments", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $totalAppointments = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM rooms WHERE status = 'occupied'", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $occupiedRooms = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM rooms WHERE status = 'available'", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $availableRooms = $result->fetch_assoc()['count'];
}

$today = date('Y-m-d');
$query_appointments_today = "SELECT COUNT(*) AS count FROM appointments WHERE appointment_date = '$today'";
$result = execute_filtered_query($mysqli, $query_appointments_today, $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $appointmentsToday = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM lab_tests WHERE status = 'pending'", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $pendingLabTests = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT COUNT(*) AS count FROM radiology_records WHERE status = 'pending'", $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  $pendingRadiology = $result->fetch_assoc()['count'];
}

$result = execute_filtered_query($mysqli, "SELECT SUM(total_amount) AS total FROM patient_bills WHERE status = 'pending'", $branch_filter_condition, $branch_params, $param_types);
if ($result && $result->num_rows > 0) {
  $totalPendingBills = $result->fetch_assoc()['total'] ?? 0;
}

$result = execute_filtered_query($mysqli, "SELECT SUM(total_amount) AS total FROM patient_bills WHERE status = 'paid'", $branch_filter_condition, $branch_params, $param_types);
if ($result && $result->num_rows > 0) {
  $totalPaidBills = $result->fetch_assoc()['total'] ?? 0;
}

$result = execute_filtered_query($mysqli, "SELECT SUM(total_amount) AS total FROM patient_bills WHERE status = 'cancelled'", $branch_filter_condition, $branch_params, $param_types);
if ($result && $result->num_rows > 0) {
  $totalCancelledBills = $result->fetch_assoc()['total'] ?? 0;
}

// Expected Monthly Payroll (sum of all salaries for the current month)
$currentMonth = date('Y-m');
$query_payroll = "SELECT SUM(net_salary) AS total FROM salary WHERE DATE_FORMAT(salary_date, '%Y-%m') = '$currentMonth'";
$result = execute_filtered_query($mysqli, $query_payroll, $branch_filter_condition, $branch_params, $param_types);
if ($result && $result->num_rows > 0) {
  $expectedMonthlyPayroll = $result->fetch_assoc()['total'] ?? 0;
}

// Get the total number of admissions in the last 30 days.
$query_admissions = "SELECT COUNT(*) AS total_admissions FROM admissions WHERE admission_date >= DATE(NOW()) - INTERVAL 30 DAY";
$result = execute_filtered_query($conn, $query_admissions, $branch_filter_condition, $branch_params, $param_types);
$total_admissions = $result->fetch_assoc()['total_admissions'];

// Simple moving average prediction for the next month's admissions.
// This is a basic prediction model and could be replaced with more sophisticated algorithms.
$predicted_admissions = $total_admissions; // Predict for the next month (31 days).
$predictedAdmissionsNextMonth = round($total_admissions / 30 * 31);; // Predict for the next month (31 days)

// Doctor-specific metrics (if logged in user is a doctor)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'doctor' && isset($_SESSION['id'])) {
  $doctor_id = $_SESSION['id'];
  $doctor_branch_filter_sql = '';
  $doctor_branch_params_array = [$doctor_id];
  $doctor_param_types_string = 'i';

  if ($user_role !== 'admin' || ($user_role === 'admin' && $user_branch_id !== null)) {
      $doctor_branch_filter_sql = " AND branch_id = ?";
      $doctor_branch_params_array[] = $user_branch_id;
      $doctor_param_types_string .= 'i';
  }

  $query_doctor_appointments_today = "SELECT COUNT(*) AS count FROM appointments WHERE doctor_id = ? AND appointment_date = '$today'" . $doctor_branch_filter_sql;
  $stmt = $mysqli->prepare($query_doctor_appointments_today);
  if ($stmt === false) {
      error_log("Prepare failed for doctor appointments today: " . $mysqli->error);
  } else {
      $stmt->bind_param($doctor_param_types_string, ...$doctor_branch_params_array);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result) {
          $doctorTotalAppointmentsToday = $result->fetch_assoc()['count'];
      }
      $stmt->close();
  }

  // Assuming 'attended' status for appointments or a separate table for patient visits
  $query_doctor_patients_attended = "SELECT COUNT(DISTINCT patient_id) AS count FROM appointments WHERE doctor_id = ? AND status = 'completed'" . $doctor_branch_filter_sql; // Assuming 'completed' means attended
  $stmt = $mysqli->prepare($query_doctor_patients_attended);
  if ($stmt === false) {
      error_log("Prepare failed for doctor patients attended: " . $mysqli->error);
  } else {
      $stmt->bind_param($doctor_param_types_string, ...$doctor_branch_params_array);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result) {
          $doctorTotalPatientsAttended = $result->fetch_assoc()['count'];
      }
      $stmt->close();
  }
}

// Fetch data for Appointment Trends (e.g., last 6 months)
$appointmentTrendsData = [];
$appointmentTrendsLabels = [];
for ($i = 5; $i >= 0; $i--) {
  $month = date('Y-m', strtotime("-$i month"));
  $monthLabel = date('M Y', strtotime("-$i month"));
  $query_appointment_trends = "SELECT COUNT(*) AS count FROM appointments WHERE DATE_FORMAT(appointment_date, '%Y-%m') = '$month'";
  $result = execute_filtered_query($mysqli, $query_appointment_trends, $branch_filter_condition, $branch_params, $param_types);
  $count = 0;
  if ($result && $result->num_rows > 0) {
    $count = $result->fetch_assoc()['count'];
  }
  $appointmentTrendsData[] = $count;
  $appointmentTrendsLabels[] = $monthLabel;
}

// Fetch data for Top Procedures/Services (e.g., top 5)
$topProceduresData = [];
$topProceduresLabels = [];
$query_top_procedures = "SELECT service_name, COUNT(*) AS count FROM services GROUP BY service_name ORDER BY count DESC LIMIT 5"; // Assuming 'services' table has service_name
$result = execute_filtered_query($mysqli, $query_top_procedures, $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $topProceduresLabels[] = $row['service_name'];
    $topProceduresData[] = $row['count'];
  }
}

// Fetch data for Bed Occupancy Map
$bedOccupancyData = [];
$query_bed_occupancy = "SELECT room_number, status FROM rooms";
$result = execute_filtered_query($mysqli, $query_bed_occupancy, $branch_filter_condition, $branch_params, $param_types);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $bedOccupancyData[] = [
      'room_number' => $row['room_number'],
      'status' => $row['status']
    ];
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
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Dashboard</h3>
              <h6 class="op-7 mb-2 fw-bold">Welcome <?= $_SESSION['role'] ?> <?= $_SESSION['staffname'] ?> to Vitalis</h6>
            </div>
            <!-- <div class="ms-md-auto py-2 py-md-0">
              <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
              <a href="#" class="btn btn-primary btn-round">Add Customer</a>
            </div> -->
          </div>
          <div class="row">
            <!-- Total Patients -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-procedures"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Patients</p>
                        <h4 class="card-title"><?php echo $totalPatients; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Doctors -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-user-md"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Doctors</p>
                        <h4 class="card-title"><?php echo $totalDoctors; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Appointments -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-calendar-check"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Total Appointments</p>
                        <h4 class="card-title"><?php echo $totalAppointments; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Occupied Rooms -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-secondary bubble-shadow-small">
                        <i class="fas fa-bed"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Occupied Rooms</p>
                        <h4 class="card-title"><?php echo $occupiedRooms; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Available Rooms -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-door-open"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Available Rooms</p>
                        <h4 class="card-title"><?php echo $availableRooms; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Appointments Today -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-calendar"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Appointments Today</p>
                        <h4 class="card-title"><?php echo $appointmentsToday; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Pending Lab Tests -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-warning bubble-shadow-small">
                        <i class="fas fa-vial"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Lab Tests</p>
                        <h4 class="card-title"><?php echo $pendingLabTests; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Pending Radiology -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-danger bubble-shadow-small">
                        <i class="fas fa-x-ray"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Radiology</p>
                        <h4 class="card-title"><?php echo $pendingRadiology; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Pending Bills -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-warning bubble-shadow-small">
                        <i class="fas fa-file-invoice-dollar"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Bills</p>
                        <h4 class="card-title">$<?php echo number_format($totalPendingBills, 2); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Paid Bills -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-money-bill-wave"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Paid Bills</p>
                        <h4 class="card-title">$<?php echo number_format($totalPaidBills, 2); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Cancelled Bills -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-danger bubble-shadow-small">
                        <i class="fas fa-times-circle"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Cancelled Bills</p>
                        <h4 class="card-title">$<?php echo number_format($totalCancelledBills, 2); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Expected Monthly Payroll -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-money-check-alt"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Monthly Payroll</p>
                        <h4 class="card-title">$<?php echo number_format($expectedMonthlyPayroll, 2); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Predicted Admissions -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-chart-line"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Predicted Admissions</p>
                        <h4 class="card-title"><?php echo $predictedAdmissions; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Predicted Admissions for Next Month -->
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-chart-bar"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Admissions Next Month</p>
                        <h4 class="card-title"><?php echo $predictedAdmissionsNextMonth; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <?php
          // Fetch upcoming appointments
          $upcomingAppointments = [];
          $currentDateTime = date('Y-m-d H:i:s');
          $query = "SELECT a.appointment_date, p.first_name, p.last_name, l.staffname
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    JOIN login l ON a.doctor_id = l.id
                    WHERE a.appointment_date > '$currentDateTime'
                    ORDER BY a.appointment_date ASC
                    LIMIT 5"; // Limit to 5 upcoming appointments
          $result = $mysqli->query($query);
          if ($result) {
              while ($row = $result->fetch_assoc()) {
                  $upcomingAppointments[] = $row;
              }
          }
          ?>

          <div class="row">
            <div class="col-md-12">
              <div class="card card-round">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Upcoming Appointments</div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-striped" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Patient Name</th>
                          <th>Doctor Name</th>
                          <th>Date</th>
                          <th>Time</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($upcomingAppointments) > 0): ?>
                          <?php foreach ($upcomingAppointments as $appointment): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                              <td><?php echo htmlspecialchars($appointment['staffname']); ?></td>
                              <td><?php echo htmlspecialchars(date('M d, Y', strtotime($appointment['appointment_date']))); ?></td>
                              <td><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['appointment_date']))); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="4" class="text-center">No upcoming appointments.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'doctor') : ?>
            <div class="row">
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Appointments Today</p>
                          <h4 class="card-title"><?php echo $doctorTotalAppointmentsToday; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-success bubble-shadow-small">
                          <i class="fas fa-user-check"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Patients Attended</p>
                          <h4 class="card-title"><?php echo $doctorTotalPatientsAttended; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-8">
              <div class="card card-round">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Appointment Trends</div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="chart-container" style="min-height: 375px">
                    <canvas id="appointmentTrendsChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card card-round">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Top Procedures/Services</div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="chart-container">
                    <canvas id="topProceduresChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card card-round">
                <div class="card-header">
                  <div class="card-head-row card-tools-still-right">
                    <h4 class="card-title">Bed Occupancy Map</h4>
                  </div>
                  <p class="card-category">
                    Visual representation of room occupancy
                  </p>
                </div>
                <div class="card-body">
                  <div id="bedOccupancyMap" class="w-100" style="height: auto">
                    <!-- Bed occupancy map will be rendered here -->
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
  <script>
    var appointmentTrendsLabels = <?php echo json_encode($appointmentTrendsLabels); ?>;
    var appointmentTrendsData = <?php echo json_encode($appointmentTrendsData); ?>;
    var topProceduresLabels = <?php echo json_encode($topProceduresLabels); ?>;
    var topProceduresData = <?php echo json_encode($topProceduresData); ?>;
    var bedOccupancyData = <?php echo json_encode($bedOccupancyData); ?>;
  </script>
  <?php include('components/script.php'); ?>
</body>

</html>
<?php include 'backup.php'; ?>
