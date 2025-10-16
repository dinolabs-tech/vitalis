<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php');?>

<body class="service-details-page">

 <?php include('component/header.php');?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Service Details</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h4>Service Details</h4>
 
      </div>
    </div><!-- End Page Title -->

    <!-- Service Details 2 Section -->
    <section id="service-details-2" class="service-details-2 section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5">

          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="service-image">
              <img src="assets/img/health/cardiology-3.webp" alt="Cardiology Services" class="img-fluid">
              <div class="service-tag">
                <span>Specialized Care</span>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="service-content">
              <h2>Comprehensive Cardiology Services</h2>
              <p class="service-tagline">Advanced heart care with cutting-edge technology and compassionate expertise</p>

              <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

              <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

              <div class="service-features">
                <h4>Our Services Include:</h4>
                <ul>
                  <li><i class="bi bi-check-circle"></i> Comprehensive cardiac examinations</li>
                  <li><i class="bi bi-check-circle"></i> Advanced diagnostic imaging</li>
                  <li><i class="bi bi-check-circle"></i> Preventive heart screening programs</li>
                  <li><i class="bi bi-check-circle"></i> Cardiac rehabilitation therapy</li>
                  <li><i class="bi bi-check-circle"></i> Emergency cardiac intervention</li>
                  <li><i class="bi bi-check-circle"></i> Post-operative care and monitoring</li>
                </ul>
              </div>

              <div class="service-actions">
                <a href="#" class="btn-primary">Schedule Consultation</a>
                <a href="#" class="btn-secondary">Learn More</a>
              </div>
            </div>
          </div>

        </div>

        <div class="row mt-5">

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="service-card">
              <div class="card-icon">
                <i class="bi bi-heart-pulse"></i>
              </div>
              <h4>Diagnostic Testing</h4>
              <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam.</p>
              <a href="#" class="card-link">
                <span>Book Now</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            <div class="service-card">
              <div class="card-icon">
                <i class="bi bi-hospital"></i>
              </div>
              <h4>Surgical Procedures</h4>
              <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque.</p>
              <a href="#" class="card-link">
                <span>Schedule Surgery</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
            <div class="service-card">
              <div class="card-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <h4>Preventive Care</h4>
              <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
              <a href="#" class="card-link">
                <span>Get Screened</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

        </div>

        <div class="row mt-5">

          <div class="col-lg-8" data-aos="fade-right" data-aos-delay="100">
            <div class="booking-section">
              <h3>Ready to Schedule Your Appointment?</h3>
              <p>Our cardiology specialists are available for consultations Monday through Friday. Same-day appointments available for urgent cases.</p>

              <div class="availability-info">
                <div class="info-item">
                  <i class="bi bi-clock"></i>
                  <div>
                    <strong>Office Hours</strong>
                    <span>Mon-Fri: 8:00 AM - 6:00 PM</span>
                  </div>
                </div>
                <div class="info-item">
                  <i class="bi bi-telephone"></i>
                  <div>
                    <strong>Emergency Line</strong>
                    <span>+1 (555) 123-4567</span>
                  </div>
                </div>
                <div class="info-item">
                  <i class="bi bi-geo-alt"></i>
                  <div>
                    <strong>Location</strong>
                    <span>123 Medical Center Dr, Boston, MA 02101</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-left" data-aos-delay="200">
            <div class="appointment-card">
              <h4>Book Your Visit</h4>
              <p>Quick and easy online scheduling</p>
              <a href="appointment.php" class="btn-appointment">Book Appointment</a>
              <div class="contact-alternative">
                <span>Or call us at</span>
                <a href="tel:+15551234567">+1 (555) 123-4567</a>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Service Details 2 Section -->

  </main>

 <?php include('component/footer.php');?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

 <?php include('component/script.php');?>
</body>

</html>