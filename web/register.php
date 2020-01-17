<?php
 // error_reporting(E_ALL);
 // ini_set('display_errors', 1);
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\SMTP;
  use PHPMailer\PHPMailer\Exception;

    // Load Composer's autoloader
  include("common/config.php");
  session_start();
  if(isset($_POST['submit'])){
    date_default_timezone_set('Asia/Kolkata');
    $currentTime = date("Y-m-d H:i:s");
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $mobile = $_POST['mobile'];
    $user_key = md5($name.$currentTime);
    //$token = bin2hex(random_bytes(50));

    $token = bin2hex(openssl_random_pseudo_bytes(50));

      if(!empty($name) && !empty($email) && !empty($password) && !empty($mobile)){
          $user_check = mysqli_query($con, "SELECT * FROM tbl_users WHERE usr_email = '".$email."'");
          if(mysqli_num_rows($user_check) > 0){
              echo '<script language="javascript">';
              echo 'alert("Your Email already registered")';
              echo '</script>';
          }else{
              
              $query = mysqli_query($con, "INSERT INTO tbl_users SET usr_name = '".$name."', usr_email = '".$email."', usr_password = '".$password."', usr_mobile = '".$mobile."', usr_login_key = '".$user_key."', usr_token = '".$token."', created_at = '".$currentTime."', usr_modified_at = '".$currentTime."'");
                //  echo "SELECT * FROM tbl_users WHERE usr_username = '".$username."' && usr_password = '".$password."' AND usr_status = 1 AND usr_parent_id = 0";exit;
              if($query){
                
                sendEmailToServer($token);
                echo '<script language="javascript">';
                echo 'alert("Successfully Registered. Please verify your email")';
                echo '</script>';
              }else{
                echo '<script language="javascript">';
                echo 'alert("Something went wrong, Please try again")';
                echo '</script>';
              }
          }
      }
    
    
  }

  function sendEmailToServer($token){
    require '../vendor/autoload.php';

    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'sameersharma078626@gmail.com';                     // SMTP username
        $mail->Password   = '7860764762078626';                               // SMTP password
        //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPDebug = 0;
        $mail->SMTPAutoTLS = false;
        $mail->SMTPSecure = 'ssl';      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 465;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('help.nhsurveys@gmail.com', 'NHSurveys');
        $mail->addAddress('sudhanshusharma435@gmail.com', 'Joe User');
        $body = '<!DOCTYPE html>
                  <html lang="en">
                  <head>
                      <meta charset="UTF-8">
                      <title>NHSurveys</title>
                      <style>
                          h1, h4 {
                              color: #ff4500;
                          }

                          .header {
                              border-bottom: 2px solid #ff4500;
                              background-color: #fff;
                              text-align: center;
                          }

                          .footer {
                              border-top: 2px solid #1b6d85;
                          }

                          .footer > a {
                              color: #ff4500;
                          }

                      </style>
                  </head>
                  <body>
                  <table width="100%">
                      <tr>
                          <td align="center">
                              <table width="600">
                                  <tr>
                                      <td class="header">
                                          <h1>NHSurveys</h1>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <h2>Verify Your Email Address</h2>
                                          <p> Thanks for creating an account with the NHSurveys.
                                              Please follow the link below to verify your email address.</p>
                                          <a href="localhost/nh_surveys/web/email_verified.php?token=' . $token . '">Verify Email</a>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <br/>
                                          Regards,<br/>
                                          NHSurveys
                                      </td>
                                  </tr>
                                  <tr>
                                      <td class="footer">
                                          © 2019 NHSurveys. All rights reserved.
                                      </td>
                                  </tr>
                              </table>
                          </td>
                      </tr>
                  </table>
                  </body>
                  </html>';
        
        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Email verification (nhsurveys.com)';
        $mail->Body    = $body;
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>NHSurveys Register</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/iconfonts/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- plugin css for this page -->
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth">
        <div class="row w-100">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left p-5">
              <div class="brand-logo">
                <!--<img src="images/logo.svg">--><h3 style="color:#5E50F9;">NHsurveys</h3>
              </div>
              <h4>New here?</h4>
              <h6 class="font-weight-light">Signing up is easy. It only takes a few steps</h6>
              <form class="pt-3" method="post" action="register.php">
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" id="exampleInputUsername1" placeholder="Name" name="name" required>
                </div>
                <div class="form-group">
                  <input type="email" class="form-control form-control-lg" id="exampleInputEmail1" placeholder="Email" name="email" required>
                </div>
                <div class="form-group">
                  <input type="mobile" class="form-control form-control-lg" id="exampleInputEmail1" placeholder="Mobile" name="mobile" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-lg" id="exampleInputPassword1" placeholder="Password" name="password" required>
                </div>
                <div class="mb-4">
                  <div class="form-check">
                    <label class="form-check-label text-muted">
                      <input type="checkbox" class="form-check-input">
                      I agree to all Terms & Conditions
                    </label>
                  </div>
                </div>
                <div class="mt-3">
                  <Button class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn" name="submit">SIGN UP</Button>
                </div>
                <div class="text-center mt-4 font-weight-light">
                  Already have an account? <a href="index_login.php" class="text-primary">Login</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="../../vendors/js/vendor.bundle.base.js"></script>
  <script src="../../vendors/js/vendor.bundle.addons.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="../../js/off-canvas.js"></script>
  <script src="../../js/misc.js"></script>
  <!-- endinject -->
</body>

</html>
