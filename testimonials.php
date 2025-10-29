<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<?php include('component/head.php');?>

<body class="testimonials-page">

  <?php include('component/header.php');?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current">Testimonials</li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h4>Testimonials</h4>
     
      </div>
    </div><!-- End Page Title -->

    <!-- Featured Testimonials Section -->
    <section id="featured-testimonials" class="featured-testimonials section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="testimonials-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "slidesPerView": 1,
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              }
            }
          </script>

          <div class="swiper-wrapper">

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Sed ut perspiciatis unde omnis</h2>
                    <p>
                      Proin iaculis purus consequat sem cure digni ssim donec porttitora entum suscipit rhoncus. Accusantium quam, ultricies eget id, aliquam eget nibh et. Maecen aliquam, risus at semper.
                    </p>
                    <p>
                      Beatae magnam dolore quia ipsum. Voluptatem totam et qui dolore dignissimos. Amet quia sapiente laudantium nihil illo et assumenda sit cupiditate. Nam perspiciatis perferendis minus consequatur. Enim ut eos quo.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="assets/img/person/person-m-7.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Saul Goodman</h3>
                        <span>Client</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="assets/img/person/person-m-7.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Nemo enim ipsam voluptatem</h2>
                    <p>
                      Export tempor illum tamen malis malis eram quae irure esse labore quem cillum quid cillum eram malis quorum velit fore eram velit sunt aliqua noster fugiat irure amet legam anim culpa.
                    </p>
                    <p>
                      Dolorem excepturi esse qui amet maxime quibusdam aut repellendus voluptatum. Corrupti enim a repellat cumque est laborum fuga consequuntur. Dolorem nostrum deleniti quas voluptatem iure dolorum rerum. Repudiandae doloribus ut repellat harum vero aut. Modi aut velit aperiam aspernatur odit ut vitae.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="assets/img/person/person-f-8.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Sara Wilsson</h3>
                        <span>Designer</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="assets/img/person/person-f-8.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>
                      Labore nostrum eos impedit
                    </h2>
                    <p>
                      Fugiat enim eram quae cillum dolore dolor amet nulla culpa multos export minim fugiat minim velit minim dolor enim duis veniam ipsum anim magna sunt elit fore quem dolore labore illum veniam.
                    </p>
                    <p>
                      Itaque ut explicabo vero occaecati est quam rerum sed. Numquam tempora aut aut quaerat quia illum. Nobis quia autem odit ipsam numquam. Doloribus sit sint corporis eius totam fuga. Hic nostrum suscipit corrupti nam expedita adipisci aut optio.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="assets/img/person/person-m-9.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Matt Brandon</h3>
                        <span>Freelancer</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="assets/img/person/person-m-9.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Impedit dolor facilis nulla</h2>
                    <p>
                      Enim nisi quem export duis labore cillum quae magna enim sint quorum nulla quem veniam duis minim tempor labore quem eram duis noster aute amet eram fore quis sint minim.
                    </p>
                    <p>
                      Omnis aspernatur accusantium qui delectus praesentium repellendus. Facilis sint odio aspernatur voluptas commodi qui qui qui pariatur. Corrupti deleniti itaque quaerat ipsum deleniti culpa tempora tempore. Et consequatur exercitationem hic aspernatur nobis est voluptatibus architecto laborum.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="assets/img/person/person-f-10.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Jena Karlis</h3>
                        <span>Store Owner</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="assets/img/person/person-f-10.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

          </div>

          <div class="swiper-navigation w-100 d-flex align-items-center justify-content-center">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>

        </div>

      </div>

    </section><!-- /Featured Testimonials Section -->

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-4">
          <!-- Testimonial Item 1 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce posuere metus vitae arcu imperdiet, id aliquet ante scelerisque. Sed sit amet sem vitae urna fringilla tempus.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-m-3.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>Michael Anderson</h5>
                    <span>Software Developer</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <!-- Testimonial Item 2 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Cras fermentum odio eu feugiat lide par naso tierra. Justo eget nada terra videa magna derita valies darta donna mare fermentum iaculis eu non diam phasellus.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-f-5.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>Sophia Martinez</h5>
                    <span>Marketing Specialist</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <!-- Testimonial Item 3 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-m-7.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>David Wilson</h5>
                    <span>Graphic Designer</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <!-- Testimonial Item 4 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-f-9.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>Emily Johnson</h5>
                    <span>UX Designer</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <!-- Testimonial Item 5 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="500">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Praesent nonummy mi in odio. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-f-11.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>Olivia Thompson</h5>
                    <span>Entrepreneur</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <!-- Testimonial Item 6 -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="600">
            <div class="testimonial-item">
              <div class="stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <p>Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.</p>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <img src="assets/img/person/person-m-12.webp" alt="Author" class="img-fluid rounded-circle" loading="lazy">
                  <div>
                    <h5>James Taylor</h5>
                    <span>Financial Analyst</span>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

        </div>

      </div>

    </section><!-- /Testimonials Section -->

  </main>

 <?php include('component/footer.php');?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

 <?php include('component/script.php');?>

</body>

</html>