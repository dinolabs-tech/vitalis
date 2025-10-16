<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php');?>

<body class="page-404">

  <?php include('component/header.php');?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">404</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h1>404</h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>
      </div>
    </div><!-- End Page Title -->

    <!-- Error 404 Section -->
    <section id="error-404" class="error-404 section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="text-center">
          <div class="error-icon mb-4" data-aos="zoom-in" data-aos-delay="200">
            <i class="bi bi-exclamation-circle"></i>
          </div>

          <h4 class="error-code mb-4" data-aos="fade-up" data-aos-delay="300">404</h4>

          <h2 class="error-title mb-3" data-aos="fade-up" data-aos-delay="400">Oops! Page Not Found</h2>

          <p class="error-text mb-4" data-aos="fade-up" data-aos-delay="500">
   
          </p>

          <div class="search-box mb-4" data-aos="fade-up" data-aos-delay="600">
            <form action="#" class="search-form">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Search for pages..." aria-label="Search">
                <button class="btn search-btn" type="submit">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </form>
          </div>

          <div class="error-action" data-aos="fade-up" data-aos-delay="700">
            <a href="/" class="btn btn-primary">Back to Home</a>
          </div>
        </div>

      </div>

    </section><!-- /Error 404 Section -->

  </main>

  <?php include('component/footer.php');?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <?php include('component/script.php');?>
</body>

</html>