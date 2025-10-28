<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    // Delete from branches table
    $stmt_branch = $conn->prepare("DELETE FROM branches WHERE branch_id = ?");
    $stmt_branch->bind_param("s", $delete_id);
    if (!$stmt_branch->execute()) {
      throw new Exception("Error deleting branch record: " . $stmt_branch->error);
    }
    $stmt_branch->close();

    $conn->commit();
    $success_message = "Branch deleted successfully!";
    header("Location: branches.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Error deleting branch: " . $stmt_branch->error;
    header("Location: branches.php?error=" . urlencode($error_message));
    exit;
  }
}

// Handle Add/Edit Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $branch_name = $_POST['branch_name'] ?? '';
  $address = $_POST['address'] ?? '';
  $contact_info = $_POST['contact_info'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null; // For editing

  if (empty($branch_name) || empty($address) || empty($contact_info)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($branch_id) {
      // Update existing branch
      $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, address = ?, contact_info = ? WHERE branch_id = ?");
      $stmt->bind_param("sssi", $branch_name, $address, $contact_info, $branch_id);
      if ($stmt->execute()) {
        $success_message = "Branch updated successfully!";
      } else {
        $error_message = "Error updating branch: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new branch
      $stmt = $conn->prepare("INSERT INTO branches (branch_name, address, contact_info) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $branch_name, $address, $contact_info);
      if ($stmt->execute()) {
        $success_message = "Branch added successfully!";
      } else {
        $error_message = "Error adding branch: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}



// Fetch all branches
$branches = [];
$result = $conn->query("SELECT * FROM branches ORDER BY branch_name ASC");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
  }
}

// Fetch branch data for editing if ID is provided in GET
$edit_branch_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_branch_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
  $stmt->bind_param("i", $edit_branch_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_branch_data = $result->fetch_assoc();
  } else {
    $error_message = "Branch not found for editing.";
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
            <h3 class="fw-bold mb-3">Branches</h3>
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
                <a href="#">Branches</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-branch.php" class="btn btn-primary btn-round">Add Branch</a>
              </div>
            <?php endif; ?>
          </div>

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


          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped custom-table" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Branch Name</th>
                          <th>Address</th>
                          <th>Contact Info</th>
                          <th>State</th>
                          <th>Country</th>
                          <th>Created At</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($branches) > 0): ?>
                          <?php foreach ($branches as $branch): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                              <td><?php echo htmlspecialchars($branch['address']); ?></td>
                              <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                              <td><?php echo htmlspecialchars($branch['state']); ?></td>
                              <td><?php echo htmlspecialchars($branch['country']); ?></td>
                              <td><?php echo htmlspecialchars($branch['created_at']); ?></td>
                              <td class="text-right">
                                <div class="d-flex">
                                  <a href="edit-branch.php?id=<?php echo $branch['branch_id']; ?>" class="btn-primary btn-icon btn-round text-white me-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" class="btn-icon btn-danger btn-round text-white btn-delete-branch" data-id="<?= $branch['branch_id'] ?>"><i class="fas fa-trash"></i></a>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="7" class="text-center">No branches found.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>


            </div>
          </div>

        </div>
      </div>
      <!-- Delete Modal -->
      <div id="delete_branch_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-branch-message">Are you sure you want to delete this Branch?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteBranchForm" method="POST" action="branches.php" style="display: inline;">
                  <input type="hidden" name="id" id="delete-branch-id">
                  <button type="submit" class="btn btn-danger btn-round">Delete</button>
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
  <script>
    // This script assumes you use jQuery and Bootstrap's modal
    // To use: add data-id="BRANCH_ID" to your delete buttons/links
    $(document).ready(function() {
      // When a delete button is clicked
      $(document).on('click', '.btn-delete-branch', function(e) {
        e.preventDefault();
        var branchId = $(this).data('id');
        var branchName = $(this).data('branch-name');
        $('#delete-branch-id').val(branchId);
        $('#delete-branch-message').text("Are you sure you want to delete the branch '" + branchName + "'?");
        $('#delete_branch_modal').modal('show');
      });
    });
  </script>
</body>

</html>
