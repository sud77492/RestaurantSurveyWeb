   <?php
       include("config.php");
       date_default_timezone_set('Asia/Kolkata');
       $date = date('d-m-Y H:i');
      if(isset($_POST['submit'])){
          $sql = mysqli_query($con, "INSERT INTO tbl_contact SET cnt_name = '".$_POST['name']."', cnt_email = '".$_POST['email']."',cnt_subject = '".$_POST['subject1']."',cnt_message = '".$_POST['message']."'");
          if($sql){
              $to = "help.nhsurveys@gmail.com";
              $msg= "Thank you for contacting us. We will get back to you soon.";
              $subject="Contact Us (nhsurveys.com)";
              $headers .= "MIME-Version: 1.0"."\r\n";
              $headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
              $headers .= 'From:NHSurveys <help.nhsurveys@gmail.com>'."\r\n";
              $ms.="<html></body><div><div>Dear NHSurveys,</div></br></br>";
              $ms.="<div style='padding-top:8px;'>".$_POST['name']." is contact us</div><br>
              <p>Name : ".$_POST['name']."</p>
              <p>Email : ".$_POST['email']."</p>
              <p>Subject : ".$_POST['subject1']."</p>
              <p>Message : ".$_POST['message']."</p><br>

              <p>Thanks</p>
              </body></html>";

              $headers = "MIME-Version: 1.0" . "\r\n";
              $headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";
              $headers .= "From: ".$_POST['email']."" . "\r\n" .
              "Reply-To: help.nhsurveys@gmail.com" . "\r\n" .
              "X-Mailer: PHP/" . phpversion();

              if(mail($to,$subject,$ms,$headers)){
                echo "Mail Successfully Sent";
              }else{
                echo "Mail not sent";
              }
              $message = "Submitted Successfully";
              echo "<script type='text/javascript'>alert('$message');</script>";
          }else{
              $message = "Not Submitted";
              echo "<script type='text/javascript'>alert('$message');</script>";
          }
      }

   ?>
   <!DOCTYPE html>
  <html lang="en">
    <head>
      <!-- Required meta tags -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

      <title>NHSurveys</title>

      <!-- Bootstrap CSS -->
      <link rel="stylesheet" href="assets/css/bootstrap.min.css" >
      <!-- Icon -->
      <link rel="stylesheet" href="assets/fonts/line-icons.css">
      <!-- Owl carousel -->
      <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
      <link rel="stylesheet" href="assets/css/owl.theme.css">
      
      <!-- Animate -->
      <link rel="stylesheet" href="assets/css/animate.css">
      <!-- Main Style -->
      <link rel="stylesheet" href="assets/css/main.css">
      <!-- Responsive Style -->
      <link rel="stylesheet" href="assets/css/responsive.css">

    </head>
    <body>

      <!-- Header Area wrapper Starts -->
      <header id="header-wrap">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-md bg-inverse fixed-top scrolling-navbar">
          <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <!--<a href="index.php" class="navbar-brand"><img src="assets/img/logo.png" alt=""></a>-->

              <h3 href="index.php" style="color:#F63854;">NHsurveys</h3>
              <!--<a href="index.php" class="navbar-brand">NHSurveys</a>-->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
              <i class="lni-menu"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
              <ul class="navbar-nav mr-auto w-100 justify-content-end clearfix">
                <li class="nav-item active">
                  <a class="nav-link" href="#hero-area">
                    Home
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#services">
                    Services
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#team">
                    Team
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#pricing">
                    Pricing
                  </a>
                </li>
                <!-- <li class="nav-item">
                  <a class="nav-link" href="#testimonial">
                    Testimonial
                  </a>
                </li> -->
                <li class="nav-item">
                  <a class="nav-link" href="#contact">
                    Contact
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="web/index_login.php">
                     SignIn
                </a>
              </li>
              </ul>
            </div>
          </div>
        </nav>
        <!-- Navbar End -->

        <!-- Hero Area Start -->
        <div id="hero-area" class="hero-area-bg">
          <div class="container">
            <div class="row">
              <div class="col-lg-7 col-md-12 col-sm-12 col-xs-12">
                <div class="contents">
                  <h2 class="head-title">App, Business<br>NHSurveys</h2>
                    <p>NHSurveys help you to optimize your business and gives you chance to take business decisions based on real feedback from the customer .It help you keep track on regular customer comfort, changes they recommend and resolve the issues by using proper feedback. It helps the business to keep track on positive and negative feedback with our dashboard real-time data collection and also saves time in unnecessary meetings regarding  small issues, So get your real-time feedback in just few clicks.</p>
                  <div class="header-button">
                    <a href="#" class="btn btn-common" data-toggle="modal" data-target="#notificationModal">Download Now</i></a>
                  </div>
                </div>
              </div>
              <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                <div class="intro-img">
                  <img class="img-fluid" src="assets/img/intro-mobile.png" alt="">
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Hero Area End -->

      </header>
      <!-- Header Area wrapper End -->

      <!-- Services Section Start -->
      <section id="services" class="section-padding">
        <div class="container">
          <div class="section-header text-center">
            <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Our Services</h2>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
          </div>
          <div class="row">
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="0.3s">
                <div class="icon">
                  <i class="lni-cog"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">Easy To Used</a></h3>
                  <p>This application is very easy to use. Anyone can understand the flow of the application, how application works. </p>
                </div>
              </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="0.6s">
                <div class="icon">
                  <i class="lni-stats-up"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">Awesome Design</a></h3>
                  <p>Design of the application is so good that anyone can like the application.</p>
                </div>
              </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="0.9s">
                <div class="icon">
                  <i class="lni-users"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">Easy To Customize</a></h3>
                  <p>User can customize the application. They can set notification for the specific result. They can see the report, can get the chart, graph reports etc.</p>
                </div>
              </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="1.2s">
                <div class="icon">
                  <i class="lni-layers"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">Business Model</a></h3>
                  <p>We create business architecture according to there expectations.</p>
                </div>
              </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="1.5s">
                <div class="icon">
                  <i class="lni-mobile"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">App Development</a></h3>
                  <p>We develop custom android iOS application according to there needs with server setup.</p>
                </div>
              </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
              <div class="services-item wow fadeInRight" data-wow-delay="1.8s">
                <div class="icon">
                  <i class="lni-rocket"></i>
                </div>
                <div class="services-content">
                  <h3><a href="#">User Friendly interface</a></h3>
                  <p>we have developed user friendly interface that is very easy to access and understand.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Services Section End -->

      <!-- About Section start -->
      <div class="about-area section-padding bg-gray">
        <div class="container">
          <div class="row">
            <div class="col-lg-6 col-md-12 col-xs-12 info">
              <div class="about-wrapper wow fadeInLeft" data-wow-delay="0.3s">
                <!--<div>
                  <div class="site-heading">
                    <p class="mb-3">Manage Statistics</p>
                    <h2 class="section-title">Detailed Statistics of NHSurveys</h2>
                  </div>
                  <div class="content">
                    <p>
                      NHsurveys software helps user to Identify that which is the point where they are lacking and where they to improve, help them to identify there positive and negatives, help them to know that which customers is happy which customer is not etc.
                    </p>
                    <a href="#" class="btn btn-common mt-3">Read More</a>
                  </div>
                </div>-->
              </div>
            </div>
            <div class="col-lg-6 col-md-12 col-xs-12 wow fadeInRight" data-wow-delay="0.3s">
              <img class="img-fluid" src="assets/img/about/img-1.png" alt="" >
            </div>
          </div>
        </div>
      </div>
      <!-- About Section End -->

      <!-- Features Section Start -->
      <section id="features" class="section-padding">
        <div class="container">
          <div class="section-header text-center">
            <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Awesome Features</h2>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
          </div>
          <div class="row">
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <div class="content-left">
                <div class="box-item wow fadeInLeft" data-wow-delay="0.3s">
                  <span class="icon">
                    <i class="lni-rocket"></i>
                  </span>
                  <div class="text">
                    <h4>Customize Notification</h4>
                    <p>Vendor can set notification according to his need either specific or overall.</p>
                  </div>
                </div>
                <div class="box-item wow fadeInLeft" data-wow-delay="0.6s">
                  <span class="icon">
                    <i class="lni-laptop-phone"></i>
                  </span>
                  <div class="text">
                    <h4>Get the daily records</h4>
                    <p>You will get the daily records of feedback with report</p>
                  </div>
                </div>
                <div class="box-item wow fadeInLeft" data-wow-delay="0.9s">
                  <span class="icon">
                    <i class="lni-cog"></i>
                  </span>
                  <div class="text">
                    <h4>Comparison</h4>
                    <p>You can compare with current 7 days & current 30 days with last 7 days and last 30 days</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <div class="show-box wow fadeInUp" data-wow-delay="0.3s">
                <img src="assets/img/feature/intro-mobile.png" alt="">
              </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              <div class="content-right">
                <div class="box-item wow fadeInRight" data-wow-delay="0.3s">
                  <span class="icon">
                    <i class="lni-leaf"></i>
                  </span>
                  <div class="text">
                    <h4>Graph Reports</h4>
                    <p>You will get graph report with rating of 5 with all the question</p>
                  </div>
                </div>
                <div class="box-item wow fadeInRight" data-wow-delay="0.6s">
                  <span class="icon">
                    <i class="lni-layers"></i>
                  </span>
                  <div class="text">
                    <h4>Support 24*7</h4>
                    <p>Main and the most important point is this, you will get 24*7 assistance, anytime, anywhere you can ask anything</p>
                  </div>
                </div>
                <div class="box-item wow fadeInRight" data-wow-delay="0.9s">
                  <span class="icon">
                    <i class="lni-leaf"></i>
                  </span>
                  <div class="text">
                    <h4>Feedback</h4>
                    <p>Our team will give you feedback that what are the reasons that you are not growing and what you should do, what you should not.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Features Section End -->

      <!-- Team Section Start -->
      <section id="team" class="section-padding bg-gray">
        <div class="container">
          <div class="section-header text-center">
            <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Meet our team</h2>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
          </div>
          <div class="row">
            <div class="col-lg-6 col-md-12 col-xs-12">
              <!-- Team Item Starts -->
              <div class="team-item wow fadeInRight" data-wow-delay="0.2s">
                <div class="team-img">
                  <img class="img-fluid" width="300" height="100" src="assets/img/team/Sudhanshu.jpg" alt="">
                </div>
                <div class="contetn">
                  <div class="info-text">
                    <h3><a href="#">Sudhanshu Sharma</a></h3>
                    <p>Mobile Application lead</p>
                  </div>
                  <p></p>
                  <ul class="social-icons">
                    <li><a href="https://www.facebook.com/sid.sharma.3914207" target="_blank"><i class="lni-facebook-filled" aria-hidden="true"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/sudhanshu-sharma-a93038143/" target="_blank"><i class="lni-linkedin-filled" aria-hidden="true"></i></a></li>
                  </ul>
                </div>
              </div>
              <!-- Team Item Ends -->
            </div>
            <div class="col-lg-6 col-md-12 col-xs-12">
              <!-- Team Item Starts -->
              <div class="team-item wow fadeInRight" data-wow-delay="0.4s">
                <div class="team-img">
                  <img class="img-fluid" src="assets/img/team/rajat.jpeg" alt="">
                </div>
                <div class="contetn">
                  <div class="info-text">
                    <h3><a href="#">Rajat Singh</a></h3>
                    <p>Salesforce Lead</p>
                  </div>
                  <p>He is tech enthusiast working as certified Salesforce developer</p>
                  <ul class="social-icons">
                    <li><a href="https://www.facebook.com/rajat.singh.77" target="_blank"><i class="lni-facebook-filled" aria-hidden="true"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/rajat-kumar-singh-418501b6/" target="_blank"><i class="lni-linkedin-filled" aria-hidden="true"></i></a></li>
                  </ul>
                </div>
              </div>
              <!-- Team Item Ends -->
            </div>
            <div class="col-lg-6 col-md-12 col-xs-12">
              <!-- Team Item Starts -->
              <div class="team-item wow fadeInRight" data-wow-delay="0.6s">
                <div class="team-img">
                  <img class="img-fluid" width="300" height="100" src="assets/img/team/gaurav.jpeg" alt="">
                </div>
                <div class="contetn">
                  <div class="info-text">
                    <h3><a href="#"></a>Gaurav</h3>
                    <p>Database Architect</p>
                  </div>
                  <p>He is expertise in creating database schema and managing database.</p>
                  <ul class="social-icons">
                    <li><a href="https://www.facebook.com/gauravmandhyan30" target="_blank"><i class="lni-facebook-filled" aria-hidden="true"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/gauravmandhyan/" target="_blank"><i class="lni-linkedin-filled" aria-hidden="true"></i></a></li>
                  </ul>
                </div>
              </div>
              <!-- Team Item Ends -->
            </div>
            <!--<div class="col-lg-6 col-md-12 col-xs-12">
              <div class="team-item wow fadeInRight" data-wow-delay="0.8s">
                <div class="team-img">
                  <img class="img-fluid" src="assets/img/team/team-04.png" alt="">
                </div>
                <div class="contetn">
                  <div class="info-text">
                    <h3><a href="#">MARIJN OTTE</a></h3>
                    <p>Lead Designer</p>
                  </div>
                  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quod eos id officiis hic tenetur.</p>
                  <ul class="social-icons">
                    <li><a href="#"><i class="lni-facebook-filled" aria-hidden="true"></i></a></li>
                    <li><a href="#"><i class="lni-twitter-filled" aria-hidden="true"></i></a></li>
                    <li><a href="#"><i class="lni-instagram-filled" aria-hidden="true"></i></a></li>
                  </ul>
                </div>
              </div>
            </div>-->
          </div>
        </div>
      </section>
      <!-- Team Section End -->

      <!-- Pricing section Start -->
      <section id="pricing" class="section-padding">
        <div class="container">
          <div class="section-header text-center">
            <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Pricing</h2>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
          </div>
          <div class="row">
            <div class="col-lg-4 col-md-6 col-xs-12">
              <div class="table wow fadeInLeft" data-wow-delay="1.2s">
                <div class="icon-box">
                  <i class="lni-package"></i>
                </div>
                <div class="pricing-header">
                  <p class="price-value">Free</p>
                </div>
                <div class="title">
                  <h3>Basic</h3>
                </div>
                <ul class="description">
                  <li>1 user</li>
                  <li>1 GB storage</li>
                  <li>Email support</li>
                  <li></li>
                  <li></li>
                    <li></li>
                </ul>
                <a href="web/index.php"<button class="btn btn-common">Access Now</button></a>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 col-xs-12 active">
              <div class="table wow fadeInUp" id="active-tb" data-wow-delay="1.2s">
                <div class="icon-box">
                  <i class="lni-drop"></i>
                </div>
                <div class="pricing-header">
                  <p class="price-value">$29<span> /mo</span></p>
                </div>
                <div class="title">
                  <h3>Pro</h3>
                </div>
                <ul class="description">
                  <li>10 user</li>
                  <li>10 GB storage</li>
                  <li>No Ads</li>
                  <li>Priority email support</li>
                  <li>Lifetime updates</li>
                </ul>
                <button class="btn btn-common">Buy Now</button>
             </div>
            </div>
            <div class="col-lg-4 col-md-6 col-xs-12">
              <div class="table wow fadeInRight" data-wow-delay="1.2s">
                <div class="icon-box">
                  <i class="lni-star"></i>
                </div>
                <div class="pricing-header">
                  <p class="price-value">$49<span> /mo</span></p>
                </div>
                <div class="title">
                  <h3>Premium</h3>
                </div>
                <ul class="description">
                  <li>Unlimited users</li>
                  <li>Unlimited storage</li>
                  <li>No Ads</li>
                  <li>24/7 support</li>
                  <li>Lifetime updates</li>
                </ul>
                <button class="btn btn-common">Buy Now</button>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Pricing Table Section End -->
    
      <!-- Testimonial Section Start -->
      <!--<section id="testimonial" class="testimonial section-padding">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
              <div id="testimonials" class="owl-carousel wow fadeInUp" data-wow-delay="1.2s">
                <div class="item">
                  <div class="testimonial-item">
                    <div class="img-thumb">
                      <img src="assets/img/testimonial/img1.jpg" alt="">
                    </div>
                    <div class="info">
                      <h2><a href="#">David Smith</a></h2>
                      <h3><a href="#">Creative Head</a></h3>
                    </div>
                    <div class="content">
                      <p class="description">Praesent cursus nulla non arcu tempor, ut egestas elit tempus. In ac ex fermentum, gravida felis nec, tincidunt ligula.</p>
                      <div class="star-icon mt-3">
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-half"></i></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="item">
                  <div class="testimonial-item">
                    <div class="img-thumb">
                      <img src="assets/img/testimonial/img2.jpg" alt="">
                    </div>
                    <div class="info">
                      <h2><a href="#">Domeni GEsson</a></h2>
                      <h3><a href="#">Awesome Technology co.</a></h3>
                    </div>
                    <div class="content">
                      <p class="description">Praesent cursus nulla non arcu tempor, ut egestas elit tempus. In ac ex fermentum, gravida felis nec, tincidunt ligula.</p>
                      <div class="star-icon mt-3">
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-half"></i></span>
                        <span><i class="lni-star-half"></i></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="item">
                  <div class="testimonial-item">
                    <div class="img-thumb">
                      <img src="assets/img/testimonial/img3.jpg" alt="">
                    </div>
                    <div class="info">
                      <h2><a href="#">Dommini Albert</a></h2>
                      <h3><a href="#">Nesnal Design co.</a></h3>
                    </div>
                    <div class="content">
                      <p class="description">Praesent cursus nulla non arcu tempor, ut egestas elit tempus. In ac ex fermentum, gravida felis nec, tincidunt ligula.</p>
                      <div class="star-icon mt-3">
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-half"></i></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="item">
                  <div class="testimonial-item">
                    <div class="img-thumb">
                      <img src="assets/img/testimonial/img4.jpg" alt="">
                    </div>
                    <div class="info">
                      <h2><a href="#">Fernanda Anaya</a></h2>
                      <h3><a href="#">Developer</a></h3>
                    </div>
                    <div class="content">
                      <p class="description">Praesent cursus nulla non arcu tempor, ut egestas elit tempus. In ac ex fermentum, gravida felis nec, tincidunt ligula.</p>
                      <div class="star-icon mt-3">
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-filled"></i></span>
                        <span><i class="lni-star-half"></i></span>
                        <span><i class="lni-star-half"></i></span>
                        <span><i class="lni-star-half"></i></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>-->

      <!-- Testimonial Section End -->

      <!-- Call To Action Section Start -->
      <section id="cta" class="section-padding">
        <div class="container">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-xs-12 wow fadeInLeft" data-wow-delay="0.3s">
              <div class="cta-text">
                <!--<h4>Get free Software application</h4>-->
                <!--<p>We will give you 6 months trial where you can use the application and get all the information.</p>-->
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-xs-12 text-right wow fadeInRight" data-wow-delay="0.3s">
              </br><a href="web/register.php" class="btn btn-common">Register Now</a>
            </div>
          </div>
        </div>
      </section>
      <!-- Call To Action Section Start -->

      <!-- Contact Section Start -->
      <section id="contact" class="section-padding bg-gray">
        <div class="container">
          <div class="section-header text-center">
            <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Contact Us</h2>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
          </div>
          <div class="row contact-form-area wow fadeInUp" data-wow-delay="0.3s">
            <div class="col-lg-7 col-md-12 col-sm-12">
              <div class="contact-block">
                <form method="post" action="#">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Name" required data-error="Please enter your name">
                        <div class="help-block with-errors"></div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="text" placeholder="Email" id="email" class="form-control" name="email" required data-error="Please enter your email">
                        <div class="help-block with-errors"></div>
                      </div>
                    </div>
                     <div class="col-md-12">
                      <div class="form-group">
                        <input type="text" placeholder="Subject" id="msg_subject" name="subject1" class="form-control" required data-error="Please enter your subject">
                        <div class="help-block with-errors"></div>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" name="message" id="message" placeholder="Your Message" rows="7" data-error="Write your message" required></textarea>
                        <div class="help-block with-errors"></div>
                      </div>
                      <div class="submit-button text-left">
                        <button class="btn btn-common" id="form-submit" name="submit" type="submit">Send Message</button>
                        <div id="msgSubmit" class="h3 text-center hidden"></div>
                        <div class="clearfix"></div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <div class="col-lg-5 col-md-12 col-xs-12">
              <div class="map">
                  <iframe style="border:0; height: 280px; width: 100%;" id="gmap_canvas" src="https://maps.google.com/maps?q=26.8684583,80.8803922&hl=es;z=14&amp;output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0">
                  </iframe>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Contact Section End -->

      <!-- Footer Section Start -->
      <footer id="footer" class="footer-area section-padding">
        <div class="container">
          <div class="container">
            <div class="row">
              <div class="col-lg-3 col-md-6 col-sm-6 col-xs-6 col-mb-12">
                <div class="widget">
                  <h3 class="footer-logo" style="color:#F63854;">NHsurveys</h3>
                  <!--<h3 class="footer-logo"><img src="assets/img/logo.png" alt=""></h3>-->
                  <div class="textwidget">
                    <p>We will become a part of restaurant owners to grow there business. Our team will guide them about the business model and help them that where they need to improve.</p>
                  </div>
                  <div class="social-icon">
                    <a class="facebook" href="https://www.facebook.com/feedbackSurveys/"><i class="lni-facebook-filled"></i></a>
                    <!-- <a class="twitter" href="#"><i class="lni-twitter-filled"></i></a>
                    <a class="instagram" href="#"><i class="lni-instagram-filled"></i></a>-->
                    <a class="linkedin" href="https://www.linkedin.com/in/nhsurveys-feedback-422329197/"><i class="lni-linkedin-filled"></i></a> 
                  </div>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
                <h3 class="footer-titel">Products</h3>
                <ul class="footer-link">
                  <li><a href="#">Application</a></li>
                  <li><a href="#">Resource Planning</a></li>
                  <li><a href="#">Enterprise</a></li>
                  <li><a href="#">Employee Management</a></li>
                </ul>
              </div>
              <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
                <h3 class="footer-titel">Resources</h3>
                <ul class="footer-link">
                  <li><a href="#pricing">Payment Options</a></li>
                  <li><a href="#">Fee Schedule</a></li>
                  <li><a href="#">Getting Started</a></li>
              </div>
              <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
                <h3 class="footer-titel">Contact</h3>
                <ul class="address">
                  <li>
                    <a href="#"><i class="lni-map-marker"></i>HN - 44 N Block, Uttam Nagar, New Delhi - 110059</a>
                  </li>
                  <li>
                    <a href="#"><i class="lni-phone-handset"></i> +919911039216</a>
                  </li>
                  <li>
                    <a href="#"><i class="lni-envelope"></i> help.nhsurveys@gmail.com</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div id="copyright">
          <div class="container">
            <div class="row">
              <div class="col-md-12">
                <div class="copyright-content">
                  <p>Copyright © 2020 <a rel="nofollow" href="">NHSurveys</a> All Right Reserved</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>
      <!-- Footer Section End -->

      <!-- Go to Top Link -->
      <a href="#" class="back-to-top">
          <i class="lni-arrow-up"></i>
      </a>
      
      <!-- Preloader -->
      <div id="preloader">
        <div class="loader" id="loader-1"></div>
      </div>

      <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel" style="color:black;" align="center">First Register, then you will be able to access the application. If already registered then Click on Download App button.</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form>
                <div class="form-group" align="center">
                  <a href="web/register.php" class="header-button">Register</a>
                </div>
                <div class="header-button" align="center">
                  <a href="https://play.google.com/store/apps/details?id=com.nhsurveys.restaurantsurvey"  class="btn btn-common">Download App</i></a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- End Preloader -->
      
      <!-- jQuery first, then Popper.js, then Bootstrap JS -->
      <script src="assets/js/jquery-min.js"></script>
      <script src="assets/js/popper.min.js"></script>
      <script src="assets/js/bootstrap.min.js"></script>
      <script src="assets/js/owl.carousel.min.js"></script>
      <script src="assets/js/wow.js"></script>
      <script src="assets/js/jquery.nav.js"></script>
      <script src="assets/js/scrolling-nav.js"></script>
      <script src="assets/js/jquery.easing.min.js"></script>
      <script src="assets/js/main.js"></script>
      <script src="assets/js/form-validator.min.js"></script>
      <script src="assets/js/contact-form-script.min.js"></script>
      <script type="text/javascript">
        $('#notificationModal').on('show.bs.modal', function (event) {
      
        })


      </script>
        
    </body>
  </html>
