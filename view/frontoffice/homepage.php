<?php
/**
 * Public Landing Page
 * Only accessible to visitors who are NOT logged in
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Ensure BASE_URL is set correctly
if (!defined('BASE_URL') || empty(BASE_URL)) {
    // Fallback: calculate from current script location
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Calculate base path correctly
    // If accessed via index.php, REQUEST_URI will be like /wafra/wafra-integration/index.php
    // We need to extract /wafra/wafra-integration
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove index.php and any action parameters
        $uri = preg_replace('#/index\.php.*$#', '', $uri);
        $uri = preg_replace('#\?.*$#', '', $uri);
        $path = rtrim($uri, '/');
    } elseif (isset($_SERVER['SCRIPT_NAME'])) {
        // Fallback to SCRIPT_NAME
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $path = rtrim($scriptPath, '/');
    } else {
        // Default fallback
        $path = '/wafra/wafra-integration';
    }
    
    // Ensure path contains wafra-integration
    if (strpos($path, 'wafra-integration') === false) {
        $path = '/wafra/wafra-integration';
    }
    
    define('BASE_URL', $protocol . '://' . $host . $path);
}

// If user is logged in, redirect them away from homepage
if (isset($_SESSION['userID']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . BASE_URL . '/index.php?action=dashboard');
    } else {
        // Redirect logged-in users to their main page (not homepage)
        header('Location: ' . BASE_URL . '/view/frontoffice/index.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100,200,300,400,500,600,700,800,900" rel="stylesheet" />
    <title>WAFRA - The First Tunisian Donation Site</title>
    <!-- Debug: BASE_URL = <?= BASE_URL ?> -->
    <!-- Bootstrap core CSS -->
    <link href="<?= BASE_URL ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/frontoffice/assets/css/fontawesome.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/frontoffice/assets/css/templatemo-grad-school.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/frontoffice/assets/css/flex-slider.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/frontoffice/assets/css/owl.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/frontoffice/assets/css/lightbox.css" />
  </head>
  <body>
    <!--header-->
    <header class="main-header clearfix" role="header">
      <div class="logo">
        <a href="#">WAFRA</a>
      </div>
      <a href="#menu" class="menu-link"><i class="fa fa-bars"></i></a>
      <nav id="menu" class="main-nav" role="navigation">
        <ul class="main-menu">
          <li><a href="#section1">Home</a></li>
          <li class="has-submenu">
            <a href="#section2">About Us</a>
            <ul class="sub-menu">
              <li><a href="#section2">Who we are?</a></li>
              <li><a href="#section3">sign in/up</a></li>
            </ul>
          </li>
          <li><a href="#section4">Donations</a></li>
          <li><a href="#section6">Contact</a></li>
          <li><a href="<?= BASE_URL ?>/index.php?action=login">Sign In</a></li>
          <li><a href="<?= BASE_URL ?>/index.php?action=signup">Sign Up</a></li>
        </ul>
      </nav>
    </header>

    <!-- ***** Main Banner Area Start ***** -->
    <section class="section main-banner" id="top" data-section="section1">
      <video autoplay muted loop id="bg-video">
        <source src="<?= BASE_URL ?>/view/frontoffice/assets/images/course-video.mp4" type="video/mp4" />
      </video>
      <div class="video-overlay header-text">
        <div class="caption">
          <h6>The first tunisian donation site</h6>
          <h2><em>start</em> donating</h2>
          <div class="main-button">
            <div class="scroll-to-section">
              <a href="#section2">Discover more</a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ***** Main Banner Area End ***** -->

    <section class="features">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 col-12">
            <div class="features-post">
              <div class="features-content">
                <div class="content-show">
                  <h4><i class="fa fa-pencil"></i>All Donation Services</h4>
                </div>
                <div class="content-hide">
                  <p>
                    WAFRA makes giving easy and meaningful. Donate surplus food
                    from restaurants or homes, share legal books and ebooks, or
                    post any other type of donation. People can also request
                    what they need, creating a secure, flexible platform that
                    connects generosity with those who need it most.
                  </p>
                  <div class="scroll-to-section">
                    <a href="#section2">More Info.</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12">
            <div class="features-post second-features">
              <div class="features-content">
                <div class="content-show">
                  <h4><i class="fa fa-graduation-cap"></i>Donation Reward</h4>
                </div>
                <div class="content-hide">
                  <p>
                    At WAFRA, generosity comes with recognition. Donors not only
                    make a meaningful impact but also have the opportunity to
                    receive rewards for their contributions. Whether it's
                    badges, thank-you notes, or special acknowledgments, our
                    platform celebrates every act of giving, encouraging
                    continued support and fostering a community where kindness
                    is recognized and valued.
                  </p>
                  <div class="scroll-to-section">
                    <a href="#section3">Details</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12">
            <div class="features-post third-features">
              <div class="features-content">
                <div class="content-show">
                  <h4><i class="fa fa-book"></i>Real Meeting</h4>
                </div>
                <div class="content-hide">
                  <p>
                    WAFRA facilitates real, safe meetings between donors and
                    recipients when needed. All meetings are organized with
                    verified participants to ensure trust, transparency, and a
                    secure environment for exchanging donations.
                  </p>
                  <div class="scroll-to-section">
                    <a href="#section4">Read More</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section why-us" data-section="section2">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="section-heading">
              <h2>Why choose WAFRA?</h2>
            </div>
          </div>
          <div class="col-md-12">
            <div id="tabs">
              <ul>
                <li><a href="#tabs-1">Best donation site</a></li>
                <li><a href="#tabs-2">Top Management</a></li>
                <li><a href="#tabs-3">Quality Meeting</a></li>
              </ul>
              <section class="tabs-content">
                <article id="tabs-1">
                  <div class="row">
                    <div class="col-md-6">
                      <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/choose-us-image-01.png" alt="" />
                    </div>
                    <div class="col-md-6">
                      <h4>Best Donation site in Tunisia</h4>
                      <p>
                        WAFRA is more than just a donation platform — it's a
                        movement of compassion and impact. Built to connect
                        generous hearts with meaningful causes, WAFRA makes
                        giving simple, transparent, and powerful. Every
                        contribution through WAFRA fuels real change,
                        transforming lives and empowering communities around the
                        world. With its secure system, inspiring stories, and
                        commitment to honesty, WAFRA stands as the best place
                        for those who believe that small acts of kindness can
                        create a big difference.
                      </p>
                    </div>
                  </div>
                </article>
                <article id="tabs-2">
                  <div class="row">
                    <div class="col-md-6">
                      <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/choose-us-image-02.png" alt="" />
                    </div>
                    <div class="col-md-6">
                      <h4>Top Level</h4>
                      <p>
                        WAFRA is designed to be a leading platform in the
                        donation ecosystem, providing a secure, scalable, and
                        transparent solution for connecting donors with
                        impactful causes. Its robust infrastructure ensures
                        seamless transactions, comprehensive reporting, and
                        data-driven insights, enabling efficient management of
                        campaigns and resources.
                      </p>
                    </div>
                  </div>
                </article>
                <article id="tabs-3">
                  <div class="row">
                    <div class="col-md-6">
                      <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/choose-us-image-03.png" alt="" />
                    </div>
                    <div class="col-md-6">
                      <h4>Quality Meeting</h4>
                      <p>
                        At WAFRA, we prioritize trust, transparency, and the
                        highest standards of security in every interaction
                        between donors and those seeking support. Every donation
                        is processed through a secure system, ensuring personal
                        and financial information is fully protected.
                      </p>
                    </div>
                  </div>
                </article>
              </section>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section coming-soon" data-section="section3">
      <div class="container">
        <div class="row">
          <div class="col-md-7 col-xs-12">
            <div class="continer centerIt">
              <div>
                <h4>give <em>any donation</em> and possibly win $326.</h4>
                <div class="counter">
                  <div class="days">
                    <div class="value">00</div>
                    <span>Days</span>
                  </div>
                  <div class="hours">
                    <div class="value">00</div>
                    <span>Hours</span>
                  </div>
                  <div class="minutes">
                    <div class="value">00</div>
                    <span>Minutes</span>
                  </div>
                  <div class="seconds">
                    <div class="value">00</div>
                    <span>Seconds</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="right-content">
              <div class="top-content">
                <h6>
                  Register your free account and <em>get immediate</em> access
                  to online donations
                </h6>
              </div>
              <form id="contact" action="<?= BASE_URL ?>/view/frontoffice/signup.php" method="get">
                <div class="row">
                  <div class="col-md-12">
                    <fieldset>
                      <button type="submit" id="form-submit" class="button">
                        sign up now
                      </button>
                    </fieldset>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section courses" data-section="section4">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="section-heading">
              <h2>Choose Your donation service</h2>
            </div>
          </div>
          <div class="owl-carousel owl-theme">
            <div class="item">
              <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/courses-01.jpg" alt="Food Donation" />
              <div class="down-content">
                <h4>Food Donations</h4>
                <p>
                  Help fight hunger by donating food! Restaurants, cafes, and
                  individuals can share leftover meals, packaged goods, or fresh
                  produce. Every contribution goes directly to people in need.
                </p>
              </div>
            </div>
            <div class="item">
              <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/courses-02.jpg" alt="Book Donation" />
              <div class="down-content">
                <h4>Book Donations</h4>
                <p>
                  Give the gift of knowledge! You can donate physical books,
                  textbooks, or legal eBooks to help children and adults learn,
                  grow, and enjoy reading.
                </p>
              </div>
            </div>
            <div class="item">
              <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/courses-03.jpg" alt="Clothing Donation" />
              <div class="down-content">
                <h4>Clothing donations</h4>
                <p>
                  Keep someone warm and comfortable. Donate clothes, shoes, and
                  jackets for adults or children. Your gently used items can
                  make a big difference in someone's life.
                </p>
              </div>
            </div>
            <div class="item">
              <img src="<?= BASE_URL ?>/view/frontoffice/assets/images/courses-04.jpg" alt="Money Donation" />
              <div class="down-content">
                <h4>Moneytary donations</h4>
                <p>
                  Support causes you care about with financial contributions.
                  Donations can be one-time or recurring and help fund
                  education, healthcare, disaster relief, and more.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section contact" data-section="section6">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="section-heading">
              <h2>Let's Keep In Touch</h2>
            </div>
          </div>
          <div class="col-md-6">
            <form id="contact" action="" method="post">
              <div class="row">
                <div class="col-md-6">
                  <fieldset>
                    <input name="name" type="text" class="form-control" id="name" placeholder="Your Name" required="" />
                  </fieldset>
                </div>
                <div class="col-md-6">
                  <fieldset>
                    <input name="email" type="text" class="form-control" id="email" placeholder="Your Email" required="" />
                  </fieldset>
                </div>
                <div class="col-md-12">
                  <fieldset>
                    <textarea name="message" rows="6" class="form-control" id="message" placeholder="Your message..." required=""></textarea>
                  </fieldset>
                </div>
                <div class="col-md-12">
                  <fieldset>
                    <button type="submit" id="form-submit" class="button">Send Message Now</button>
                  </fieldset>
                </div>
              </div>
            </form>
          </div>
          <div class="col-md-6">
            <div id="map">
              <iframe src="https://maps.google.com/maps?q=Av.+L%C3%BAcio+Costa,+Rio+de+Janeiro+-+RJ,+Brazil&t=&z=13&ie=UTF8&iwloc=&output=embed" width="100%" height="422px" frameborder="0" style="border: 0" allowfullscreen></iframe>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer>
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <p>
              <i class="fa fa-copyright"></i> Copyright 2020 by Grad School |
              Design: <a href="https://templatemo.com" rel="sponsored" target="_parent">TemplateMo</a>
            </p>
          </div>
        </div>
      </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= BASE_URL ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/isotope.min.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/owl-carousel.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/lightbox.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/tabs.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/video.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/slick-slider.js"></script>
    <script src="<?= BASE_URL ?>/view/frontoffice/assets/js/custom.js"></script>
    <script>
      jQuery(document).ready(function($) {
        $(".nav li:first").addClass("active");
        var showSection = function showSection(section, isAnimate) {
          var direction = section.replace(/#/, ""),
            reqSection = $(".section").filter('[data-section="' + direction + '"]'),
            reqSectionPos = reqSection.offset().top - 0;
          if (isAnimate) {
            $("body, html").animate({ scrollTop: reqSectionPos }, 800);
          } else {
            $("body, html").scrollTop(reqSectionPos);
          }
        };
        var checkSection = function checkSection() {
          $(".section").each(function () {
            var $this = $(this),
              topEdge = $this.offset().top - 80,
              bottomEdge = topEdge + $this.height(),
              wScroll = $(window).scrollTop();
            if (topEdge < wScroll && bottomEdge > wScroll) {
              var currentId = $this.data("section"),
                reqLink = $("a").filter("[href*=\\#" + currentId + "]");
              reqLink.closest("li").addClass("active").siblings().removeClass("active");
            }
          });
        };
        $(".main-menu, .scroll-to-section").on("click", "a", function (e) {
          if ($(e.target).hasClass("external")) {
            return;
          }
          var href = $(this).attr("href");
          if (href && href.startsWith("#")) {
            e.preventDefault();
            $("#menu").removeClass("active");
            showSection(href, true);
          }
        });
        $(window).scroll(function () {
          checkSection();
        });
      });
    </script>
  </body>
</html>

