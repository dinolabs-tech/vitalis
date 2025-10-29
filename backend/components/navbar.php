<div class="main-header bg-info text-white">
  <div class="main-header-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="light-blue">
      <a href="index.php" class="logo">
        <!-- <img
                  src="assets/img/kaiadmin/logo_light.svg"
                  alt="navbar brand"
                  class="navbar-brand"
                  height="20"
                /> -->
      </a>
      <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
          <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
          <i class="gg-menu-left"></i>
        </button>
      </div>
      <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
      </button>
    </div>
    <!-- End Logo Header -->
  </div>
  <!-- Navbar Header -->
  <nav
    class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
      <nav
        class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
        <!-- <div class="input-group">
          <div class="input-group-prepend">
            <button type="submit" class="btn btn-search pe-1">
              <i class="fa fa-search search-icon"></i>
            </button>
          </div>
          <input
            type="text"
            placeholder="Search ..."
            class="form-control" />
        </div> -->
      </nav>

      <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
        <?php
        // Include database connection if not already included
        if (!isset($conn)) {
            include_once('database/db_connect.php');
        }

        // Fetch all branches for the dropdown
        $branches_for_dropdown = [];
        $result_branches_dropdown = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
        if ($result_branches_dropdown) {
            while ($row = $result_branches_dropdown->fetch_assoc()) {
                $branches_for_dropdown[] = $row;
            }
        }

        // Handle branch selection
        if (isset($_POST['selected_branch_id'])) {
            $_SESSION['branch_id'] = $_POST['selected_branch_id'];
            // Redirect to the current page to apply the filter
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
        ?>
        <li class="nav-item dropdown hidden-caret">
          <form action="" method="POST" class="form-inline">
            <select name="selected_branch_id" class="form-control form-select" onchange="this.form.submit()">
              <option value="">All Branches</option>
              <?php foreach ($branches_for_dropdown as $branch_option): ?>
                <option value="<?php echo $branch_option['branch_id']; ?>" <?php echo (isset($_SESSION['branch_id']) && $_SESSION['branch_id'] == $branch_option['branch_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($branch_option['branch_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </li>
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="messageDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <i class="fa fa-envelope text-dark"></i>
          </a>
          <ul
            class="dropdown-menu messages-notif-box animated fadeIn"
            aria-labelledby="messageDropdown">
            <li>
              <div
                class="dropdown-title d-flex justify-content-between align-items-center">
                Messages
                <a href="#" class="small">Mark all as read</a>
              </div>
            </li>
            <li>
              <div class="message-notif-scroll scrollbar-outer">
                <div class="notif-center">
                  <a href="#">
                    <div class="notif-img">
                      <img
                        src="assets/img/jm_denis.jpg"
                        alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="subject">Jimmy Denis</span>
                      <span class="block"> How are you ? </span>
                      <span class="time">5 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-img">
                      <img
                        src="assets/img/chadengle.jpg"
                        alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="subject">Chad</span>
                      <span class="block"> Ok, Thanks ! </span>
                      <span class="time">12 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-img">
                      <img
                        src="assets/img/mlane.jpg"
                        alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="subject">Jhon Doe</span>
                      <span class="block">
                        Ready for the meeting today...
                      </span>
                      <span class="time">12 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-img">
                      <img
                        src="assets/img/talha.jpg"
                        alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="subject">Talha</span>
                      <span class="block"> Hi, Apa Kabar ? </span>
                      <span class="time">17 minutes ago</span>
                    </div>
                  </a>
                </div>
              </div>
            </li>
            <li>
              <a class="see-all" href="javascript:void(0);">See all messages<i class="fa fa-angle-right"></i>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="notifDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <i class="fa fa-bell text-dark"></i>
            <span class="notification bg-dark">4</span>
          </a>
          <ul
            class="dropdown-menu notif-box animated fadeIn"
            aria-labelledby="notifDropdown">
            <li>
              <div class="dropdown-title">
                You have 4 new notification
              </div>
            </li>
            <li>
              <div class="notif-scroll scrollbar-outer">
                <div class="notif-center">
                  <a href="#">
                    <div class="notif-icon notif-primary">
                      <i class="fa fa-user-plus"></i>
                    </div>
                    <div class="notif-content">
                      <span class="block"> New user registered </span>
                      <span class="time">5 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-icon notif-success">
                      <i class="fa fa-comment"></i>
                    </div>
                    <div class="notif-content">
                      <span class="block">
                        Rahmad commented on Admin
                      </span>
                      <span class="time">12 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-img">
                      <img
                        src="assets/img/profile2.jpg"
                        alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="block">
                        Reza send messages to you
                      </span>
                      <span class="time">12 minutes ago</span>
                    </div>
                  </a>
                  <a href="#">
                    <div class="notif-icon notif-danger">
                      <i class="fa fa-heart"></i>
                    </div>
                    <div class="notif-content">
                      <span class="block"> Farrah liked Admin </span>
                      <span class="time">17 minutes ago</span>
                    </div>
                  </a>
                </div>
              </div>
            </li>
            <li>
              <a class="see-all" href="javascript:void(0);">See all notifications<i class="fa fa-angle-right"></i>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a
            class="nav-link"
            data-bs-toggle="dropdown"
            href="#"
            aria-expanded="false">
            <i class="fas fa-layer-group text-dark"></i>
          </a>
          <div class="dropdown-menu quick-actions animated fadeIn">
            <div class="quick-actions-header">
              <span class="title mb-1">Quick Actions</span>
              <span class="subtitle op-7">Shortcuts</span>
            </div>
            <div class="quick-actions-scroll scrollbar-outer">
              <div class="quick-actions-items">
                <div class="row m-0">
                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a class="col-6 col-md-4 p-0" href="add-doctor.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-danger rounded-circle">
                          <i class="fas fa-user-md"></i>
                        </div>
                        <span class="text">Add Doctor</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-employee.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-warning rounded-circle">
                          <i class="fas fa-user-plus"></i>
                        </div>
                        <span class="text">Add Employee</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-department.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-info rounded-circle">
                          <i class="fas fa-hospital"></i>
                        </div>
                        <span class="text">Add Department</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-room.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-success rounded-circle">
                          <i class="fas fa-bed"></i>
                        </div>
                        <span class="text">Add Room</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-patient.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-primary rounded-circle">
                          <i class="fas fa-wheelchair"></i>
                        </div>
                        <span class="text">Add Patient</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-invoice.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-secondary rounded-circle">
                          <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <span class="text">Add Invoice</span>
                      </div>
                    </a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'pharmacist'): ?>
                    <a class="col-6 col-md-4 p-0" href="add-product.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-danger rounded-circle">
                          <i class="fas fa-box"></i>
                        </div>
                        <span class="text">Add Product</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-medication.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-warning rounded-circle">
                          <i class="fas fa-pills"></i>
                        </div>
                        <span class="text">Add Medication</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-stock-transfer.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-info rounded-circle">
                          <i class="fas fa-exchange-alt"></i>
                        </div>
                        <span class="text">Add Stock Transfer</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-prescription.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-success rounded-circle">
                          <i class="fas fa-prescription"></i>
                        </div>
                        <span class="text">Add Prescription</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-drug-consultation.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-primary rounded-circle">
                          <i class="fas fa-comment-medical"></i>
                        </div>
                        <span class="text">Add Drug Consultation</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="pharmacy.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-secondary rounded-circle">
                          <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <span class="text">View Pharmacy</span>
                      </div>
                    </a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist'): ?>
                    <a class="col-6 col-md-4 p-0" href="add-patient.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-danger rounded-circle">
                          <i class="fas fa-wheelchair"></i>
                        </div>
                        <span class="text">Add Patient</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-appointment.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-warning rounded-circle">
                          <i class="fas fa-calendar-plus"></i>
                        </div>
                        <span class="text">Add Appointment</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-opd-visit.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-info rounded-circle">
                          <i class="fas fa-stethoscope"></i>
                        </div>
                        <span class="text">Add OPD Visit</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-patient-bill.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-success rounded-circle">
                          <i class="fas fa-money-bill-alt"></i>
                        </div>
                        <span class="text">Add Patient Bill</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="patients.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-primary rounded-circle">
                          <i class="fas fa-users"></i>
                        </div>
                        <span class="text">View Patients</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="appointments.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-secondary rounded-circle">
                          <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="text">View Appointments</span>
                      </div>
                    </a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'nurse'): ?>
                    <a class="col-6 col-md-4 p-0" href="add-patient-vital.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-danger rounded-circle">
                          <i class="fas fa-heartbeat"></i>
                        </div>
                        <span class="text">Add Patient Vital</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-drug-administration.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-warning rounded-circle">
                          <i class="fas fa-syringe"></i>
                        </div>
                        <span class="text">Add Drug Admin.</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-ipd-admission.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-info rounded-circle">
                          <i class="fas fa-hospital-user"></i>
                        </div>
                        <span class="text">Add IPD Admission</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-medical-record.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-success rounded-circle">
                          <i class="fas fa-file-medical"></i>
                        </div>
                        <span class="text">Add Medical Record</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-doctor-note.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-primary rounded-circle">
                          <i class="fas fa-notes-medical"></i>
                        </div>
                        <span class="text">Add Doctor Note</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-icu-monitoring.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-secondary rounded-circle">
                          <i class="fas fa-monitor-heart-rate"></i>
                        </div>
                        <span class="text">Add ICU Monitoring</span>
                      </div>
                    </a>
                  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'doctor'): ?>
                    <a class="col-6 col-md-4 p-0" href="add-patient.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-danger rounded-circle">
                          <i class="fas fa-wheelchair"></i>
                        </div>
                        <span class="text">Add Patient</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-appointment.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-warning rounded-circle">
                          <i class="fas fa-calendar-plus"></i>
                        </div>
                        <span class="text">Add Appointment</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-opd-visit.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-info rounded-circle">
                          <i class="fas fa-stethoscope"></i>
                        </div>
                        <span class="text">Add OPD Visit</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-prescription.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-success rounded-circle">
                          <i class="fas fa-prescription"></i>
                        </div>
                        <span class="text">Add Prescription</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-doctor-note.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-primary rounded-circle">
                          <i class="fas fa-notes-medical"></i>
                        </div>
                        <span class="text">Add Doctor Note</span>
                      </div>
                    </a>
                    <a class="col-6 col-md-4 p-0" href="add-lab-test.php">
                      <div class="quick-actions-item">
                        <div class="avatar-item bg-secondary rounded-circle">
                          <i class="fas fa-flask"></i>
                        </div>
                        <span class="text">Add Lab Test</span>
                      </div>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </li>

        <li class="nav-item topbar-user dropdown hidden-caret">
          <a
            class="dropdown-toggle profile-pic"
            data-bs-toggle="dropdown"
            href="#"
            aria-expanded="false">
            <div class="avatar-sm">
              <img
                src="assets/img/profile/<?php echo htmlentities($_SESSION['profile_picture']); ?>"
                alt="..."
                class="avatar-img rounded-circle" />
            </div>
            <span class="profile-username">
              <span class="op-7 fw-bold text-dark">Hi,</span>
              <span class="fw-bold text-dark"><?php echo htmlentities($_SESSION['staffname']); ?></span>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-user animated fadeIn">
            <div class="dropdown-user-scroll scrollbar-outer">
              <li>
                <div class="user-box">
                  <div class="avatar-lg">
                    <img
                      src="assets/img/profile/<?php echo htmlentities($_SESSION['profile_picture']); ?>"
                      alt="image profile"
                      class="avatar-img rounded" />
                  </div>
                  <div class="u-text">
                    <h4><?php echo htmlentities($_SESSION['staffname']); ?></h4>
                    <p class="text-muted"><?php echo htmlentities($_SESSION['email']); ?></p>
                    <a
                      href="profile.php"
                      class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                  </div>
                </div>
              </li>
              <li>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="profile.php">My Profile</a>
                <a class="dropdown-item" href="#">Inbox</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php">Logout</a>
              </li>
            </div>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
  <!-- End Navbar -->
</div>
