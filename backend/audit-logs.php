<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php';
session_start();
?>

<?php
// Fetch branches for dropdown
$branches = [];
$result_branches = $mysqli->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
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
            <h4 class="page-title">Audit Logs</h4>
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
                <a href="#">Audit Logs</a>
              </li>
            </ul>
          </div>

          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div class="ms-md-auto py-2 py-md-0 d-flex align-items-center">
              <form method="GET" action="audit-logs.php" class="form-inline me-3">
                <label for="branch_filter" class="form-label me-2">Filter by Branch:</label>
                <select class="form-control" id="branch_filter" name="branch_id" onchange="this.form.submit()">
                  <option value="">All Branches</option>
                  <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo htmlspecialchars($branch['branch_id']); ?>" <?php echo (isset($_GET['branch_id']) && $_GET['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($branch['branch_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
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
                          <th>User</th>
                          <th>Action</th>
                          <th>Module</th>
                          <th>Action Date</th>
                          <th>IP Address</th>
                          <th>Branch</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sql = "SELECT al.*, b.branch_name
                                FROM audit_logs al
                                LEFT JOIN branches b ON al.branch_id = b.branch_id";

                        $conditions = [];
                        $params = [];
                        $types = "";

                        if (isset($_GET['branch_id']) && $_GET['branch_id'] !== '') {
                            $conditions[] = "al.branch_id = ?";
                            $params[] = $_GET['branch_id'];
                            $types .= "i";
                        }

                        if (count($conditions) > 0) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $sql .= " ORDER BY al.actionDate DESC";

                        $stmt = $mysqli->prepare($sql);
                        if ($stmt) {
                            if (count($params) > 0) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($row = $res->fetch_object()) {
                        ?>
                              <tr>
                                <td><?php echo $row->userName; ?></td>
                                <td><?php echo $row->action; ?></td>
                                <td><?php echo $row->module; ?></td>
                                <td><?php echo $row->actionDate; ?></td>
                                <td><?php echo $row->ipAddress; ?></td>
                                <td><?php echo $row->branch_name ?? 'N/A'; ?></td>
                              </tr>
                            <?php }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>Failed to prepare statement: " . $mysqli->error . "</td></tr>";
                        } ?>
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
