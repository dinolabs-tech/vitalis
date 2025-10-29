<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php');?>

<body class="contact-page">

 <?php include('component/header.php');?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Contact</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h4>Contact</h4>
    
      </div>
    </div><!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="container">
        <div class="contact-wrapper">
          <div class="contact-info-panel">
            <div class="contact-info-header">
              <h3>Contact Information</h3>
              <p>Dignissimos deleniti accusamus rerum voluptate. Dignissimos rerum sit maiores reiciendis voluptate inventore ut.</p>
            </div>

            <div class="contact-info-cards">
              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-pin-map-fill"></i>
                </div>
                <div class="card-content">
                  <h4>Our Location</h4>
                  <p>4952 Hilltop Dr, Anytown, CA 90210</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-envelope-open"></i>
                </div>
                <div class="card-content">
                  <h4>Email Us</h4>
                  <p>info@example.com</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-telephone-fill"></i>
                </div>
                <div class="card-content">
                  <h4>Call Us</h4>
                  <p>+1 (555) 123-4567</p>
                </div>
              </div>

              <div class="info-card">
                <div class="icon-container">
                  <i class="bi bi-clock-history"></i>
                </div>
                <div class="card-content">
                  <h4>Working Hours</h4>
                  <p>Monday-Saturday: 9AM - 7PM</p>
                </div>
              </div>
            </div>

            <div class="social-links-panel">
              <h5>Follow Us</h5>
              <div class="social-icons">
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
                <a href="#"><i class="bi bi-youtube"></i></a>
              </div>
            </div>
          </div>

          <div class="contact-form-panel">
            <div class="map-container">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.892067015773!2d5.2123582238320365!3d7.253123514257196!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x10478fe8951accd5%3A0x83ca7aca634566ef!2sTisco%20Shopping%20Center%2C%20Alagbaka%2C%20Akure%20340110%2C%20Ondo!5e0!3m2!1sen!2sng!4v1760624147991!5m2!1sen!2sng" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <div class="form-container">
              <h3>Send Us a Message</h3>
              <p>Lorem ipsum dolor sit amet consectetur adipiscing elit mauris hendrerit faucibus imperdiet nec eget felis.</p>

              <?php
            

              if (isset($_SESSION['success_message'])) {
                  echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                  unset($_SESSION['success_message']); // Clear the message after displaying
              }

              if (isset($_SESSION['error_message'])) {
                  echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                  unset($_SESSION['error_message']); // Clear the message after displaying
              }
              ?>
              <form action="send-message.php" method="post" class="php-email-form">
                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="nameInput" name="name" placeholder="Full Name" required="">
                  <label for="nameInput">Full Name</label>
                </div>

                <div class="form-floating mb-3">
                  <input type="email" class="form-control" id="emailInput" name="email" placeholder="Email Address" required="">
                  <label for="emailInput">Email Address</label>
                </div>

                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="subjectInput" name="subject" placeholder="Subject" required="">
                  <label for="subjectInput">Subject</label>
                </div>

                <div class="form-floating mb-3">
                  <textarea class="form-control" id="messageInput" name="message" rows="5" placeholder="Your Message" style="height: 150px" required=""></textarea>
                  <label for="messageInput">Your Message</label>
                </div>

                <div class="my-3">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Your message has been sent. Thank you!</div>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn-submit">Send Message <i class="bi bi-send-fill ms-2"></i></button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Contact Section -->

  </main>

 <?php include('component/footer.php');?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <<?php include('component/script.php');?>

</body>

</html>
