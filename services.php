<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php'); ?>

<body class="services-page">

  <?php include('component/header.php'); ?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Services</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h4>Services</h4>

      </div>
    </div><!-- End Page Title -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="services-tabs">
          <ul class="nav nav-tabs" role="tablist" data-aos="fade-up" data-aos-delay="200">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="services-primary-tab" data-bs-toggle="tab" data-bs-target="#services-primary" type="button" role="tab">Primary Care</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-specialty-tab" data-bs-toggle="tab" data-bs-target="#services-specialty" type="button" role="tab">Specialty Care</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-diagnostics-tab" data-bs-toggle="tab" data-bs-target="#services-diagnostics" type="button" role="tab">Diagnostics</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="services-emergency-tab" data-bs-toggle="tab" data-bs-target="#services-emergency" type="button" role="tab">Emergency</button>
            </li>
          </ul>

          <div class="tab-content" data-aos="fade-up" data-aos-delay="300">

            <div class="tab-pane fade show active" id="services-primary" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-stethoscope"></i>
                    </div>
                    <div class="service-details">
                      <h5>General Consultation</h5>
                      <p>Our experienced physicians provide thorough checkups, accurate diagnoses, and personalized medical advice. Whether you’re feeling unwell or just need routine health monitoring, our team is here to guide you to better health.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Comprehensive Health Assessment</li>
                        <li><i class="fa fa-check-circle"></i>Preventive Care Planning</li>
                        <li><i class="fa fa-check-circle"></i>Health Monitoring</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-syringe"></i>
                    </div>
                    <div class="service-details">
                      <h5>Vaccination Services</h5>
                      <p>Stay protected with our complete range of immunizations for children and adults. We provide routine and travel vaccinations in a safe, professional environment to help prevent common infectious diseases.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Adult Immunizations</li>
                        <li><i class="fa fa-check-circle"></i>Travel Vaccines</li>
                        <li><i class="fa fa-check-circle"></i>Flu Shots</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-baby"></i>
                    </div>
                    <div class="service-details">
                      <h5>Maternal Health</h5>
                      <p>Your mental well-being matters. Our qualified counselors and psychiatrists offer confidential therapy and support for stress, anxiety, depression, and emotional health — helping you find balance and peace of mind.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Prenatal Care</li>
                        <li><i class="fa fa-check-circle"></i>Delivery Support</li>
                        <li><i class="fa fa-check-circle"></i>Postnatal Care</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-user-md"></i>
                    </div>
                    <div class="service-details">
                      <h5>Family Medicine</h5>
                      <p>We provide comprehensive healthcare for every member of your family — from infants to seniors. Our family doctors focus on prevention, wellness, and long-term care to keep your loved ones healthy at every stage of life</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>All-Age Care</li>
                        <li><i class="fa fa-check-circle"></i>Chronic Disease Management</li>
                        <li><i class="fa fa-check-circle"></i>Wellness Programs</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-specialty" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item featured">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-heartbeat"></i>
                    </div>
                    <div class="service-details">
                      <h5>Cardiology</h5>
                      <p>Expert care for heart and vascular conditions using advanced medical technology.
                      </p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Heart Disease Treatment</li>
                        <li><i class="fa fa-check-circle"></i>Cardiac Surgery</li>
                        <li><i class="fa fa-check-circle"></i>Rehabilitation Programs</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-brain"></i>
                    </div>
                    <div class="service-details">
                      <h5>Neurology</h5>
                      <p>Comprehensive treatment for brain, spine, and nerve disorders.
                      </p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Neurological Assessment</li>
                        <li><i class="fa fa-check-circle"></i>Stroke Treatment</li>
                        <li><i class="fa fa-check-circle"></i>Memory Care</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-bone"></i>
                    </div>
                    <div class="service-details">
                      <h5>Orthopedics</h5>
                      <p>Specialized bone and joint care to restore movement and strength.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Joint Replacement</li>
                        <li><i class="fa fa-check-circle"></i>Sports Medicine</li>
                        <li><i class="fa fa-check-circle"></i>Pain Management</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-user-nurse"></i>
                    </div>
                    <div class="service-details">
                      <h5>Oncology</h5>
                      <p>Comprehensive cancer care with advanced treatment and compassionate support</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Cancer Treatment</li>
                        <li><i class="fa fa-check-circle"></i>Chemotherapy</li>
                        <li><i class="fa fa-check-circle"></i>Support Services</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-diagnostics" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-vial"></i>
                    </div>
                    <div class="service-details">
                      <h5>Laboratory Testing</h5>
                      <p>We provide fast and accurate lab tests using modern equipment to support precise diagnosis and treatment decisions.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Blood Analysis</li>
                        <li><i class="fa fa-check-circle"></i>Pathology Services</li>
                        <li><i class="fa fa-check-circle"></i>Quick Results</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="service-item">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-x-ray"></i>
                    </div>
                    <div class="service-details">
                      <h5>Diagnostic Imaging</h5>
                      <p>Our imaging center offers high-quality X-rays, ultrasounds, and scans for quick and reliable results.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>MRI Scans</li>
                        <li><i class="fa fa-check-circle"></i>CT Imaging</li>
                        <li><i class="fa fa-check-circle"></i>Ultrasound</li>
                      </ul>
                      <a href="service-details.php" class="service-link">
                        <span>Learn More</span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="services-emergency" role="tabpanel">
              <div class="row g-4">
                <div class="col-lg-12">
                  <div class="service-item emergency-highlight">
                    <div class="service-icon-wrapper">
                      <i class="fa fa-ambulance"></i>
                    </div>
                    <div class="service-details">
                      <h5>24/7 Emergency Care</h5>
                      <p>
                        Round-the-clock medical attention for urgent and life-threatening conditions, available every day of the year.</p>
                      <ul class="service-benefits">
                        <li><i class="fa fa-check-circle"></i>Round-the-Clock Availability</li>
                        <li><i class="fa fa-check-circle"></i>Trauma Center</li>
                        <li><i class="fa fa-check-circle"></i>Critical Care Unit</li>
                        <li><i class="fa fa-check-circle"></i>Emergency Surgery</li>
                      </ul>
                      <div class="emergency-actions">
                        <a href="tel:911" class="btn-emergency">
                          <i class="fa fa-phone"></i>
                          <span>Call Emergency</span>
                        </a>
                        <a href="directions.php" class="btn-directions">
                          <i class="fa fa-map-marker-alt"></i>
                          <span>Get Directions</span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="services-cta" data-aos="fade-up" data-aos-delay="400">
          <div class="row">
            <div class="col-lg-8 mx-auto text-center">
              <div class="cta-content">
                <i class="fa fa-calendar-check"></i>
                <h3>Ready to Schedule Your Appointment?</h3>
                <p>Booking your visit is quick and easy. Choose your preferred doctor, date, and time — and our team will be ready to welcome you with the care you deserve.</p>
                <div class="cta-buttons">
                  <a href="appointment.php" class="btn-book">Book Now</a>
                  <a href="contact.php" class="btn-contact">Contact Us</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Services Section -->

  </main>

  <?php include('component/footer.php'); ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <?php include('component/script.php'); ?>

</body>

</html>