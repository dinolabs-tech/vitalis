<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php');?>

<body class="appointment-page">

 <?php include('component/header.php');?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Appointment</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h4>Appointment</h4>
      
      </div>
    </div><!-- End Page Title -->

    <!-- Appointmnet Section -->
    <section id="appointmnet" class="appointmnet section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <!-- Appointment Info -->
          <div class="col-lg-6">
            <div class="appointment-info">
              <h3>Quick &amp; Easy Online Booking</h3>
              <p class="mb-4">Book your appointment in just a few simple steps. Our healthcare professionals are ready to provide you with the best medical care tailored to your needs.</p>

              <div class="info-items">
                <div class="info-item d-flex align-items-center mb-3" data-aos="fade-up" data-aos-delay="200">
                  <div class="icon-wrapper me-3">
                    <i class="bi bi-calendar-check"></i>
                  </div>
                  <div>
                    <h5>Flexible Scheduling</h5>
                    <p class="mb-0">Choose from available time slots that fit your busy schedule</p>
                  </div>
                </div><!-- End Info Item -->

                <div class="info-item d-flex align-items-center mb-3" data-aos="fade-up" data-aos-delay="250">
                  <div class="icon-wrapper me-3">
                    <i class="bi bi-stopwatch"></i>
                  </div>
                  <div>
                    <h5>Quick Response</h5>
                    <p class="mb-0">Get confirmation within 15 minutes of submitting your request</p>
                  </div>
                </div><!-- End Info Item -->

                <div class="info-item d-flex align-items-center mb-3" data-aos="fade-up" data-aos-delay="300">
                  <div class="icon-wrapper me-3">
                    <i class="bi bi-shield-check"></i>
                  </div>
                  <div>
                    <h5>Expert Medical Care</h5>
                    <p class="mb-0">Board-certified doctors and specialists at your service</p>
                  </div>
                </div><!-- End Info Item -->
              </div>

              <div class="emergency-contact mt-4" data-aos="fade-up" data-aos-delay="350">
                <div class="emergency-card p-3">
                  <h6 class="mb-2"><i class="bi bi-telephone-fill me-2"></i>Emergency Hotline</h6>
                  <p class="mb-0">Call <strong>+1 (555) 911-4567</strong> for urgent medical assistance</p>
                </div>
              </div>

            </div>
          </div><!-- End Appointment Info -->

          <!-- Appointment Form -->
          <div class="col-lg-6">
            <div class="appointment-form-wrapper" data-aos="fade-up" data-aos-delay="200">
              <form action="forms/appointment.php" method="post" class="appointment-form php-email-form">
                <div class="row gy-3">

                  <div class="col-md-6">
                    <input type="text" name="name" class="form-control" placeholder="Your Full Name" required="">
                  </div>

                  <div class="col-md-6">
                    <input type="email" name="email" class="form-control" placeholder="Your Email" required="">
                  </div>

                  <div class="col-md-6">
                    <input type="tel" name="phone" class="form-control" placeholder="Your Phone Number" required="">
                  </div>

                  <div class="col-md-6">
                    <select name="department" class="form-select" required="">
                      <option value="">Select Department</option>
                      <option value="cardiology">Cardiology</option>
                      <option value="neurology">Neurology</option>
                      <option value="orthopedics">Orthopedics</option>
                      <option value="pediatrics">Pediatrics</option>
                      <option value="dermatology">Dermatology</option>
                      <option value="general">General Medicine</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <input type="date" name="date" class="form-control" required="">
                  </div>

                  <div class="col-md-6">
                    <select name="doctor" class="form-select" required="">
                      <option value="">Select Doctor</option>
                      <option value="dr-johnson">Dr. Sarah Johnson</option>
                      <option value="dr-martinez">Dr. Michael Martinez</option>
                      <option value="dr-chen">Dr. Lisa Chen</option>
                      <option value="dr-patel">Dr. Raj Patel</option>
                      <option value="dr-williams">Dr. Emily Williams</option>
                      <option value="dr-thompson">Dr. David Thompson</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <textarea class="form-control" name="message" rows="5" placeholder="Please describe your symptoms or reason for visit (optional)"></textarea>
                  </div>

                  <div class="col-12">
                    <div class="loading">Loading</div>
                    <div class="error-message"></div>
                    <div class="sent-message">Your appointment request has been sent successfully. We will contact you shortly!</div>

                    <button type="submit" class="btn btn-appointment w-100">
                      <i class="bi bi-calendar-plus me-2"></i>Book Appointment
                    </button>
                  </div>

                </div>
              </form>
            </div>
          </div><!-- End Appointment Form -->

        </div>

        <!-- Process Steps -->
        <div class="process-steps mt-5" data-aos="fade-up" data-aos-delay="300">
          <div class="row text-center gy-4">
            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-number">1</div>
                <div class="step-icon">
                  <i class="bi bi-person-fill"></i>
                </div>
                <h5>Fill Details</h5>
                <p>Provide your personal information and select your preferred department</p>
              </div>
            </div><!-- End Step -->

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-number">2</div>
                <div class="step-icon">
                  <i class="bi bi-calendar-event"></i>
                </div>
                <h5>Choose Date</h5>
                <p>Select your preferred date and time slot from available options</p>
              </div>
            </div><!-- End Step -->

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-number">3</div>
                <div class="step-icon">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h5>Confirmation</h5>
                <p>Receive instant confirmation and appointment details via email or SMS</p>
              </div>
            </div><!-- End Step -->

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-number">4</div>
                <div class="step-icon">
                  <i class="bi bi-heart-pulse"></i>
                </div>
                <h5>Get Treatment</h5>
                <p>Visit our clinic at your scheduled time and receive quality healthcare</p>
              </div>
            </div><!-- End Step -->

          </div>
        </div><!-- End Process Steps -->

      </div>

    </section><!-- /Appointmnet Section -->

  </main>

 <?php include('component/footer.php');?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <?php include('component/script.php');?>

</body>

</html>