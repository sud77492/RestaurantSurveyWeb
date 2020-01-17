  <?php
  session_start();
  $sud = "sudhanshu";
  if (empty($_SESSION['user_id'])) {
      ?>
      <script type="text/javascript">
          window.location = "index_login.php";
  
      </script>
  
  <?php
  }
  include("common/config.php");
  date_default_timezone_set('Asia/Kolkata');
  $date = date('Y-m-d H:i:s');
  $query = mysqli_query($con, "SELECT * FROM `tbl_responses` INNER JOIN `tbl_options` ON rspn_answer = opt_id INNER JOIN `tbl_questions` ON rspn_ques_id = ques_id WHERE rspn_survey_id = '".$_GET['survey_id']."' ORDER BY ques_id ASC");

  $queryUserInfo = mysqli_query($con, "SELECT * FROM tbl_surveys WHERE srvy_id = '".$_GET['survey_id']."'");
  $rowInfo = mysqli_fetch_array($queryUserInfo);
  
    
    
?>
  
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Survey Admin</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/iconfonts/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>\
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <!-- <a class="navbar-brand brand-logo" href="index.php"><img src="images/logo.svg" alt="logo"/></a> -->
        <a class="navbar-brand brand-logo" href="index.php"><h3 href="index.php" style="color:#5E50F9;">NHsurveys</h3></a>
        <a class="navbar-brand brand-logo-mini" href="index.php"><img src="images/logo-mini.svg" alt="logo"/></a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <div class="search-field d-none d-md-block">
          <form class="d-flex align-items-center h-100" action="#">
            <!--<div class="input-group">
              <div class="input-group-prepend bg-transparent">
                  <i class="input-group-text border-0 mdi mdi-magnify"></i>                
              </div>
              <input type="text" class="form-control bg-transparent border-0" placeholder="Search projects">
            </div>-->
          </form>
        </div>
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-profile dropdown">
            <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
              <div class="nav-profile-img">
                <img src="images/faces/face1.jpg" alt="image">
                <span class="availability-status online"></span>             
              </div>
              <div class="nav-profile-text">
                <p class="mb-1 text-black"><?php echo $_SESSION['user_restaurant_name'];?></p>
              </div>
            </a>
            <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" onclick="return confirm('Are you sure to logout?');" href="logout.php">
                <i class="mdi mdi-logout mr-2 text-primary"></i>
                Signout
              </a>
            </div>
          </li>
          <li class="nav-item d-none d-lg-block full-screen-link">
            <a class="nav-link">  
              <i class="mdi mdi-fullscreen" id="fullscreen-button"></i>
            </a>
          </li>
          
          <li class="nav-item nav-logout d-none d-lg-block">
            <a class="nav-link" onclick="return confirm('Are you sure to logout?');" href="logout.php">
              <i class="mdi mdi-power"></i>
            </a>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item nav-profile">
            <a href="#" class="nav-link">
              
              <div class="nav-profile-text d-flex flex-column">
                <span class="font-weight-bold mb-2"><?php echo $_SESSION['user_restaurant_name'];?></span>
                <span class="text-secondary text-small">Owner</span>
              </div>
              <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php">
              <span class="menu-title">Dashboard</span>
              <i class="mdi mdi-home menu-icon"></i>
            </a>
            <a class="nav-link" href="chart.php">
              <span class="menu-title">Chart</span>
              <i class="mdi mdi-chart-line menu-icon"></i>
            </a>
            <a class="nav-link" href="response.php">
              <span class="menu-title">Response</span>
              <i class="mdi mdi-chart-line menu-icon"></i>
            </a>
            <a class="nav-link" href="notification.php">
              <span class="menu-title">Notification</span>
              <i class="mdi mdi-menu menu-icon"></i>
            </a>
            <a class="nav-link" href="accounts.php">
              <span class="menu-title">Accounts</span>
              <i class="mdi mdi-settings menu-icon"></i>
            </a>
            <a class="nav-link" href="support.php">
              <span class="menu-title">Help & Support</span>
              <i class="mdi mdi-help-circle menu-icon"></i>
            </a>
          </li>
        </ul>   </nav>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">
              <span class="page-title-icon bg-gradient-primary text-white mr-2">
                <i class="mdi mdi-home"></i>                 
              </span>
              Response Detail
            </h3>
            
          </div>
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Feedback and UserInfo</h4>
                  
                  <table class="table table-striped">
                    <tbody>
                      <tr>
                        <td>
                          <b>Name:</b>
                        </td>
                        <td>
                          <?php echo $rowInfo['srvy_user_name']; ?>
                        </td>
                        
                      </tr>
                      <tr>
                        <td>
                          <b>Overall Rating:</b>
                        </td>
                        <td>
                          <?php echo $_GET['overall_rating']; ?>
                        </td>
                        
                      </tr>
                      <tr>
                        <td>
                          <b>Mobile:</b>
                        </td>
                        <td>
                          <?php echo $rowInfo['srvy_user_mobile']; ?>
                        </td>
                        
                      </tr>
                      <tr>
                        <td>
                          <b>Star Rating:</b>
                        </td>
                        <td>
                        <?php for ($i=0; $i < 5; $i++){
                        		if ($i < $rowInfo['srvy_rating']){
                        			echo '<span class="fa fa-star checked" style="color:orange;"></span>';
                        		}else{
                        			echo '<span class="fa fa-star"></span>';
                        		}

                        	} ?>
                        </td>
                        
                      </tr>
                      <tr>
                        <td>
                          <b>Status:</b>
                        </td>
                        <td>
                          <label class="badge badge-success"><?php echo $_GET['status'];?></label>
                        </td>
                        
                      </tr>
                      <tr>
                        <td>
                          <b>Comment:</b>
                        </td>
                        <td>
                         <?php echo $rowInfo['srvy_comments']; ?>
                        </td>
                        
                      </tr>
                      
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Question and Responses</h4>
                  
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        
                        <th>
                          Question
                        </th>
                        <th>
                          Responses
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while($row = mysqli_fetch_array($query)){ ?>
                      <tr>
                        
                        <td>
                          <?php echo $row['ques_name_eng']; ?>
                        </td>
                        <td>
                          <?php echo $row['opt_name_eng']; ?>
                        </td>
                        
                      </tr>
                     <?php  } ?>
                      
                     </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2017 <a href="https://www.bootstrapdash.com/" target="_blank">Bootstrap Dash</a>. All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i class="mdi mdi-heart text-danger"></i></span>
          </div>
        </footer>
        <!-- partial -->
      </div> 
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- plugins:js -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <script src="vendors/js/vendor.bundle.addons.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page-->
  <!-- End plugin js for this page-->
  <!-- inject:js -->
  <script src="js/off-canvas.js"></script>
  <script src="js/misc.js"></script>

  <!-- End custom js for this page-->
</body>

</html>
.checked {
  color: orange;
}
