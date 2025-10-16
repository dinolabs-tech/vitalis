<!-- Sidebar -->
<div class="sidebar" data-background-color="dark2">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="light-blue">
      <a href="index.php" class="logo">
        <!-- <img
          src="assets/img/kaiadmin/logo_light.svg"
          alt="navbar brand"
          class="navbar-brand"
          height="20" /> -->
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
  <div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
      <ul class="nav nav-secondary">
        <li class="nav-item">
          <a href="../index.php">
            <i class="fas fa-globe"></i>
            <p>Visit Website</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="index.php">
            <i class="fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist')): ?>
          <li class="nav-item">
            <a href="pharmacy.php">
              <i class="fas fa-prescription-bottle-alt"></i>
              <p>Pharmacy</p>
            </a>
          </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <li class="nav-item">
            <a href="doctors.php">
              <i class="fas fa-user-md"></i>
              <p>Doctors</p>
            </a>
          </li>
        <?php endif; ?>
        <!-- receptionist and admin only  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist' || $_SESSION['role'] === 'admin' || $_SESSION['role'] === 'doctor'): ?>
          <li class="nav-item">
            <a href="patients.php">
              <i class="fas fa-wheelchair"></i>
              <p>Patients</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="appointments.php">
              <i class="fas fa-calendar"></i>
              <p>Appointments</p>
            </a>
          </li>
        <?php endif; ?>
        <!-- receptionist  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist'): ?>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#patient-care">
              <i class="fas fa-stethoscope"></i>
              <p>Patient Care</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="patient-care">
              <ul class="nav nav-collapse">
                <li>
                  <a href="opd-visits.php">
                    <span class="sub-item">OPD Visits</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#accounts">
              <i class="fas fa-money-bill-alt"></i>
              <p>Accounts</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="accounts">
              <ul class="nav nav-collapse">
                <li>
                  <a href="patient-bills.php">
                    <span class="sub-item">Patient Bills</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
        <?php endif; ?>
        <!-- nurse  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'nurse'): ?>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#patient-care">
              <i class="fas fa-stethoscope"></i>
              <p>Patient Care</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="patient-care">
              <ul class="nav nav-collapse">
                <li>
                  <a href="patient-vitals.php">
                    <span class="sub-item">Patient Vitals</span>
                  </a>
                </li>
                <li>
                  <a href="drug_administration.php">
                    <span class="sub-item">Drug Administrations</span>
                  </a>
                </li>
                <li>
                  <a href="ipd-admissions.php">
                    <span class="sub-item">IPD Admissions</span>
                  </a>
                </li>
                <li>
                  <a href="medical_record_management.php">
                    <span class="sub-item">Medical Record Management</span>
                  </a>
                </li>
                <li>
                  <a href="doctor-notes.php">
                    <span class="sub-item">Doctor Notes</span>
                  </a>
                </li>
                <li>
                  <a href="icu_monitoring.php">
                    <span class="sub-item">ICU Monitoring</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
        <?php endif; ?>
        <!-- pharmacist  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'pharmacist'): ?>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#inventory">
              <i class="fas fa-medkit"></i>
              <p>Inventory</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="inventory">
              <ul class="nav nav-collapse">
                <li>
                  <a href="products.php">
                    <span class="sub-item">Products</span>
                  </a>
                </li>
                <li>
                  <a href="medications.php">
                    <span class="sub-item">Medications</span>
                  </a>
                </li>
                <li>
                  <a href="stock-transfers.php">
                    <span class="sub-item">Stock Transfers</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#patient-care">
              <i class="fas fa-stethoscope"></i>
              <p>Patient Care</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="patient-care">
              <ul class="nav nav-collapse">
                <li>
                  <a href="drug_consultation.php">
                    <span class="sub-item">Drug Consultations</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
        <?php endif; ?>
        <!-- doctor  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'doctor'): ?>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#patient-care">
              <i class="fas fa-stethoscope"></i>
              <p>Patient Care</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="patient-care">
              <ul class="nav nav-collapse">
                <li>
                  <a href="opd-visits.php">
                    <span class="sub-item">OPD Visits</span>
                  </a>
                </li>
                <li>
                  <a href="ipd-admissions.php">
                    <span class="sub-item">IPD Admissions</span>
                  </a>
                </li>
                <li>
                  <a href="er_visits.php">
                    <span class="sub-item">ER Visits</span>
                  </a>
                </li>
                <li>
                  <a href="doctor-notes.php">
                    <span class="sub-item">Doctor Notes</span>
                  </a>
                </li>
                <li>
                  <a href="prescriptions.php">
                    <span class="sub-item">Prescriptions</span>
                  </a>
                </li>
                <li>
                  <a href="drug_administration.php">
                    <span class="sub-item">Drug Administrations</span>
                  </a>
                </li>
                <li>
                  <a href="drug_consultation.php">
                    <span class="sub-item">Drug Consultations</span>
                  </a>
                </li>
                <li>
                  <a href="medical_record_management.php">
                    <span class="sub-item">Medical Record Management</span>
                  </a>
                </li>
                <li>
                  <a href="patient-vitals.php">
                    <span class="sub-item">Patient Vitals</span>
                  </a>
                </li>
                <li>
                  <a href="operations.php">
                    <span class="sub-item">Operations</span>
                  </a>
                </li>
                <li>
                  <a href="icu_monitoring.php">
                    <span class="sub-item">ICU Monitoring</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#lab&radiology">
              <i class="fas fa-flask"></i>
              <p>Lab & Radiology</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="lab&radiology">
              <ul class="nav nav-collapse">
                <li>
                  <a href="lab-tests.php">
                    <span class="sub-item">Lab Tests</span>
                  </a>
                </li>
                <li>
                  <a href="radiology-records.php">
                    <span class="sub-item">Radiology Records</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#inventory">
              <i class="fas fa-medkit"></i>
              <p>Inventory</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="inventory">
              <ul class="nav nav-collapse">
                <li>
                  <a href="medications.php">
                    <span class="sub-item">Medications</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
        <?php endif; ?>
        <!-- admin  -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <li class="nav-item">
            <a href="drug_administration.php">
              <i class="fas fa-pills"></i>
              <p>Drug Administration</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="schedule.php">
              <i class="fas fa-calendar"></i>
              <p>Doctor Schedule</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="departments.php">
              <i class="fas fa-hospital"></i>
              <p>Departments</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="branches.php">
              <i class="fas fa-building"></i>
              <p>Branches</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="rooms.php">
              <i class="fas fa-bed"></i>
              <p>Rooms</p>
            </a>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#employees">
              <i class="fas fa-user"></i>
              <p>Employees</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="employees">
              <ul class="nav nav-collapse">
                <li>
                  <a href="employees.php">
                    <span class="sub-item">Employees List</span>
                  </a>
                </li>
                <li>
                  <a href="leaves.php">
                    <span class="sub-item">Leaves</span>
                  </a>
                </li>
                <li>
                  <a href="leave-type.php">
                    <span class="sub-item">Leave Type</span>
                  </a>
                </li>
                <li>
                  <a href="holidays.php">
                    <span class="sub-item">Holidays</span>
                  </a>
                </li>
                <li>
                  <a href="attendance.php">
                    <span class="sub-item">Attendance</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#inventory">
              <i class="fas fa-medkit"></i>
              <p>Inventory</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="inventory">
              <ul class="nav nav-collapse">
                <li>
                  <a href="products.php">
                    <span class="sub-item">Products</span>
                  </a>
                </li>
                <li>
                  <a href="medications.php">
                    <span class="sub-item">Medications</span>
                  </a>
                </li>
                <li>
                  <a href="services.php">
                    <span class="sub-item">Services</span>
                  </a>
                </li>
                <li>
                  <a href="stock-transfers.php">
                    <span class="sub-item">Stock Transfers</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#lab&radiology">
              <i class="fas fa-flask"></i>
              <p>Lab & Radiology</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="lab&radiology">
              <ul class="nav nav-collapse">
                <li>
                  <a href="lab-tests.php">
                    <span class="sub-item">Lab Tests</span>
                  </a>
                </li>
                <li>
                  <a href="test-samples.php">
                    <span class="sub-item">Test Samples</span>
                  </a>
                </li>
                <li>
                  <a href="radiology-records.php">
                    <span class="sub-item">Radiology Records</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#patient-care">
              <i class="fas fa-stethoscope"></i>
              <p>Patient Care</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="patient-care">
              <ul class="nav nav-collapse">
                <li>
                  <a href="admissions.php">
                    <span class="sub-item">Admissions</span>
                  </a>
                </li>
                <li>
                  <a href="opd-visits.php">
                    <span class="sub-item">OPD Visits</span>
                  </a>
                </li>
                <li>
                  <a href="ipd-admissions.php">
                    <span class="sub-item">IPD Admissions</span>
                  </a>
                </li>
                <li>
                  <a href="operations.php">
                    <span class="sub-item">Operations</span>
                  </a>
                </li>
                <li>
                  <a href="vaccinations.php">
                    <span class="sub-item">Vaccinations</span>
                  </a>
                </li>
                <li>
                  <a href="patient-vitals.php">
                    <span class="sub-item">Patient Vitals</span>
                  </a>
                </li>
                <li>
                  <a href="doctor-notes.php">
                    <span class="sub-item">Doctor Notes</span>
                  </a>
                </li>
                <li>
                  <a href="prescriptions.php">
                    <span class="sub-item">Prescriptions</span>
                  </a>
                </li>
                <li>
                  <a href="drug_consultation.php">
                    <span class="sub-item">Drug Consultations</span>
                  </a>
                </li>
                <li>
                  <a href="er_visits.php">
                    <span class="sub-item">ER Visits</span>
                  </a>
                </li>
                <li>
                  <a href="medical_record_management.php">
                    <span class="sub-item">Medical Record Management</span>
                  </a>
                </li>
                <li>
                  <a href="icu_monitoring.php">
                    <span class="sub-item">ICU Monitoring</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#accounts">
              <i class="fas fa-money-bill-alt"></i>
              <p>Accounts</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="accounts">
              <ul class="nav nav-collapse">
                <li>
                  <a href="invoices.php">
                    <span class="sub-item">Invoices</span>
                  </a>
                </li>
                <li>
                  <a href="patient-bills.php">
                    <span class="sub-item">Patient Bills</span>
                  </a>
                </li>
                <li>
                  <a href="payments.php">
                    <span class="sub-item">Payments</span>
                  </a>
                </li>
                <li>
                  <a href="expenses.php">
                    <span class="sub-item">Expenses</span>
                  </a>
                </li>
                <li>
                  <a href="taxes.php">
                    <span class="sub-item">Taxes</span>
                  </a>
                </li>
                <li>
                  <a href="provident-fund.php">
                    <span class="sub-item">Provident Fund</span>
                  </a>
                </li>
                <li>
                  <a href="fee-settings.php">
                    <span class="sub-item">Fee Settings</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a href="salary.php">
              <i class="fas fa-book"></i>
              <p>Employee Salary</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="general-payroll.php">
              <i class="fas fa-money-check-alt"></i>
              <p>General Payroll</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="audit-logs.php">
              <i class="fas fa-user"></i>
              <p>Audit Logs</p>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<!-- End Sidebar -->
