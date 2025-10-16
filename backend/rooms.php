<?php
session_start();
include_once('database/db_connect.php'); // Include your database connection

// Check if user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Handle Delete Room
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_room = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt_room->bind_param("i", $delete_id);

    if (!$stmt_room->execute()) {
      throw new Exception("Error deleting room record: " . $stmt_room->error);
    }

    $stmt_room->close();
    $conn->commit();

    $success_message = "Room deleted successfully!";
    header("Location: rooms.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Room: " . $e->getMessage();
    header("Location: rooms.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch rooms data
$sql = "SELECT r.*, b.branch_name FROM rooms r LEFT JOIN branches b ON r.branch_id = b.branch_id";
$result = $conn->query($sql);
$rooms = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
  }
}

// Handle session messages
if (isset($_SESSION['status']) && isset($_SESSION['status_code'])) {
  if ($_SESSION['status_code'] == 'success') {
    $success_message = $_SESSION['status'];
  } else {
    $error_message = $_SESSION['status'];
  }
  unset($_SESSION['status']);
  unset($_SESSION['status_code']);
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
            <h3 class="fw-bold mb-3">Rooms</h3>
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
                <a href="#">Patients</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-room.php" class="btn btn-primary btn-round">Add Room</a>
              </div>
            <?php endif; ?>
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
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-border table-striped" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Room Number</th>
                          <th>Room Type</th>
                          <th>Capacity</th>
                          <th>Room Cost</th>
                          <th>Bed Cost</th>
                          <th>Status</th>
                          <th>Branch</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($rooms)): ?>
                          <tr>
                            <td colspan="9" class="text-center">No rooms found.</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($rooms as $room) : ?>
                            <tr>
                              <td><?php echo $room['id']; ?></td>
                              <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                              <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                              <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                              <td><?php echo htmlspecialchars($room['room_cost']); ?></td>
                              <td><?php echo htmlspecialchars($room['bed_cost']); ?></td>
                              <td><?php echo htmlspecialchars($room['status']); ?></td>
                              <td><?php echo htmlspecialchars($room['branch_name'] ? $room['branch_name'] : 'N/A'); ?></td>
                              <td class="text-right d-flex">
                                <a href="edit-room.php?id=<?php echo $room['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $room['id']; ?>" data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-room"><i class="fas fa-trash"></i></a>

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
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="delete_room_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-room-message">Are you sure you want to delete this room record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteRoomForm" method="POST" action="rooms.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-room-id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-room').on('click', function(e) {
        e.preventDefault();
        var roomId = $(this).data('id');
        var roomNumber = $(this).data('room-number');
        $('#delete-room-id').val(roomId);
        $('#delete-room-message').text("Are you sure you want to delete room number '" + roomNumber + "'?");
        $('#delete_room_modal').modal('show');
      });
    });
  </script>
</body>

</html>
