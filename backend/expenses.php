<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();
if (isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $adn = "delete from expenses where id=?";
  $stmt = $mysqli->prepare($adn);
  $stmt->bind_param('i', $id);
  $stmt->execute();
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
                <a href="expenses.php">Expenses</a>
              </li>
            </ul>
          </div>
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h4 class="fw-bold mb-3">Expenses</h4>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-expense.php" class="btn btn-primary btn-round">Add Expense</a>
              </div>
            <?php endif; ?>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Item Name</th>
                          <th>Purchase From</th>
                          <th>Purchase Date</th>
                          <th>Amount</th>
                          <th>Paid By</th>
                          <th>Status</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT * FROM expenses";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->itemName; ?></td>
                            <td><?php echo $row->purchaseFrom; ?></td>
                            <td><?php echo $row->purchaseDate; ?></td>
                            <td>$<?php echo $row->amount; ?></td>
                            <td><?php echo $row->paidBy; ?></td>
                            <td><?php echo $row->status; ?></td>
                            <td class="text-right">
                              <div class="d-flex">
                                <a href="edit-expense.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white me-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-item-name="<?php echo htmlspecialchars($row->itemName); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-expense"><i class="fas fa-trash"></i> </a>
                              </div>
                            </td>
                          </tr>
                        <?php } ?>
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
  <div id="delete_expense_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-expense-message">Are you sure you want to delete this expense?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteExpenseForm" method="POST" action="expenses.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-expense-id">
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
      $('.btn-delete-expense').on('click', function(e) {
        e.preventDefault();
        var expenseId = $(this).data('id');
        var itemName = $(this).data('item-name');
        $('#delete-expense-id').val(expenseId);
        $('#delete-expense-message').text("Are you sure you want to delete the expense for '" + itemName + "'?");
        $('#delete_expense_modal').modal('show');
      });
    });
  </script>
</body>

</html>
