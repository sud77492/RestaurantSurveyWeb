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
  if(isset($_POST['submit_specific'])){
    $deleteOverall = mysqli_query($con, "DELETE FROM tbl_notifications WHERE notfctn_user_id = '".$_SESSION['user_id']."'");
    if($deleteOverall){
      $queryOverall = mysqli_query($con, "INSERT INTO tbl_notifications SET notfctn_user_id = '".$_SESSION['user_id']."', notfctn_opt_id = '".$_POST['select_question_specific']."', notfctn_rating = '".$_POST['rating_specific']."', notfctn_type = 1, notfctn_modified = '".$date."', notfctn_created = '".$date."'"); 
      if($queryOverall){
          $message = "Notification set successfully";
          echo "<script type='text/javascript'>alert('$message');</script>";
      }else{
          $message = "Notification not set";
          echo "<script type='tempnam(dir, prefix)xt/javascript'>alert('$message');</script>";
      }
    }
  }else if(isset($_POST['submit_overall'])){
    $deleteOverall = mysqli_query($con, "DELETE FROM tbl_notifications WHERE notfctn_user_id = '".$_SESSION['user_id']."'");
    if($deleteOverall){
      $queryOverall = mysqli_query($con, "INSERT INTO tbl_notifications SET notfctn_user_id = '".$_SESSION['user_id']."', notfctn_name = '".$_POST['notification_name']."', notfctn_rating = '".$_POST['rating_overall']."', notfctn_type = 3, notfctn_modified = '".$date."', notfctn_created = '".$date."'"); 
      if($queryOverall){
          $message = "Notification set successfully";
          echo "<script type='text/javascript'>alert('$message');</script>";
      }else{
          $message = "Notification not set";
          echo "<script type='tempnam(dir, prefix)xt/javascript'>alert('$message');</script>";
      }
    }
  }
  
  $notificationOption = mysqli_query($con, "SELECT * FROM tbl_notification_options");

  $query = mysqli_query($con, "SELECT  a.notfctn_id, a.notfctn_name, a.notfctn_user_id, a.notfctn_type, b.ntf_opt_name, a.notfctn_rating, b.ntf_opt_id FROM tbl_notifications a LEFT JOIN tbl_notification_options b
            ON FIND_IN_SET(b.ntf_opt_id, a.notfctn_opt_id) > 0 WHERE a.notfctn_user_id = '".$_SESSION['user_id']."'");
  $row =  mysqli_fetch_array($query);
  switch ($row['notfctn_type']) {
    case 1:
      $notification_type = "SPECIFIC";
      $notification_name = $row['ntf_opt_name'];
      break;

    case 2:
      $notification_type = "INDIVIDUAL";

      break;

    case 3:
      $notification_type = "OVERALL";
      $notification_name = "NOTIFICATION";
      break;
    
    default:
      # code...
      break;
  }
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
        </ul>
      </nav>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">
              <span class="page-title-icon bg-gradient-primary text-white mr-2">
                <i class="mdi mdi-home"></i>                 
              </span>
              Notification
            </h3>
          </div>
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <button type="button" style="float: right;" class="btn btn-outline-primary btn-fw" data-toggle="modal" data-target="#notificationModal" data-whatever="@mdo">Add Notification</button>
                  <h4 class="card-title">Notification</h4>
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Notification Name</th>
                        <th>Rating</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><?php echo $notification_type; ?></td>
                        <td><?php echo $notification_name; ?></td>
                        <td><?php echo $row['notfctn_rating']; ?></td>
                        <td><label class="badge badge-success">Completed</label></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
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

  <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">SELECT NOTIFICATION TYPE</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-group" align="center">
              <button type="button" data-dismiss="modal" class="btn btn-outline-primary btn-fw" data-toggle="modal" data-target="#notificationModalSpecific">SPECIFIC</button>
            </div>
           <div class="form-group" align="center">
              <button type="button" data-dismiss="modal" class="btn btn-outline-primary btn-fw" data-toggle="modal" data-target="#notificationModalOverall" >OVERALL</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="notificationModalSpecific" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">SET SPECIFIC QUESTION NOTIFICATION</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="post" action="#">
        <div class="modal-body">
            <div class="form-group">
              <label for="recipient-name" class="col-form-label">Select Question</label>
              <select class="form-control" name="select_question_specific" id="select_question_specific">
                <?php while($row_options = mysqli_fetch_array($notificationOption)){
                  echo "<option value=".$row_options['ntf_opt_id'].">".$row_options['ntf_opt_name']."</option>";
                }?>          
              </select>
            </div>
           <div class="form-group">
              <label for="recipient-name" class="col-form-label">Rating</label>
              <input type="text" name="rating_specific" class="form-control" id="recipient-name">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <input type="submit" name="submit_specific" class="btn btn-primary"/>
        </div>
        </form>  
      </div>
    </div>
  </div>

  <div class="modal fade" id="notificationModalOverall" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">SET OVERALL NOTIFICATION</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form method="post", action="#">
        <div class="modal-body">
            <div class="form-group">
              <label for="recipient-name" class="col-form-label">Notification Name</label>
              <input type="text" name="notification_name" class="form-control" id="recipient-name">
            </div>
           <div class="form-group">
              <label for="recipient-name" class="col-form-label">Rating</label>
              <input type="text" name="rating_overall" class="form-control" id="recipient-name">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <input type="submit" name="submit_overall" class="btn btn-primary"/>
        </div>
        </form>
      </div>
    </div>
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

  <script type="text/javascript">
    $('#notificationModal').on('show.bs.modal', function (event) {
      
    })
    $('#notificationModalSpecific').on('show.bs.modal', function (event) {
      
    })
    $('#notificationModalOverall').on('show.bs.modal', function (event) {
      
    })
  </script>
  <!-- End custom js for this page-->
</body>

</html>
