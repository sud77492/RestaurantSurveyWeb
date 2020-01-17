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
  if (isset($_GET['pageno'])) {
      $pageno = $_GET['pageno'];
  } else {
      $pageno = 1;
  }
  $no_of_records_per_page = 10;
  $offset = ($pageno-1) * $no_of_records_per_page;

  $total_pages_sql = mysqli_query($con, "SELECT count(*) FROM `tbl_surveys` WHERE srvy_user_id = '".$_SESSION['user_id']."'");
  $total_rows = mysqli_fetch_array($total_pages_sql)[0];
  $total_pages = ceil($total_rows / $no_of_records_per_page);

  $query = mysqli_query($con, "SELECT *, SUM(rspn_answer_rating), count(rspn_answer_rating), SUM(rspn_answer_rating)/count(rspn_answer_rating) as average_rating, CASE
		WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>4
		THEN  'Excellent'
		WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>3
		THEN  'Good'
		WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>2
		THEN  'Average'
		WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)<=1
		THEN  'Poor'
		WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)<=0
		THEN  'Very Poor'
		ELSE NULL END AS  'rating_status' FROM `tbl_surveys` LEFT JOIN `tbl_responses` ON srvy_id = rspn_survey_id WHERE rspn_user_id = '".$_SESSION['user_id']."' GROUP BY rspn_survey_id ORDER BY srvy_id DESC LIMIT $offset, $no_of_records_per_page");



    
    
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
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
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
              Response 
            </h3>
            
          </div>
         
          <div class="row">
            <div class="col-12 grid-margin">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">All Feedbacks</h4>
                  <div class="table-responsive">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>
                            Customer Name
                          </th>
                          <th>
                            Mobile
                          </th>
                          <th>
                            Rating Status
                          </th>
                          <th>
                            Status
                          </th>
                          <th>
                            Star Rating
                          </th>
                          <th>
                            Comment
                          </th>
                          <th>
                            View
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php while($row = mysqli_fetch_array($query)){ ?>
                        <tr>
                          <td>
                            <!--<img src="images/faces/face1.jpg" class="mr-2" alt="image">-->
                            <?php echo $row['srvy_user_name']; ?>
                          </td>
                          <td>
                            <?php echo $row['srvy_user_mobile']; ?>
                          </td>
                          <?php if($row['average_rating'] >= 4){ ?>
                            <td>
                              <label class="badge badge-gradient-success"><?php echo round($row['average_rating'],2); ?></label>
                            </td>
                            <td>
                              <label class="badge badge-gradient-success"><?php echo $row['rating_status']; ?></label>
                            </td>
                              <?php }else if($row['average_rating'] >= 3){ ?>
                            <td>
                              <label class="badge badge-gradient-info"><?php echo round($row['average_rating'],2); ?></label>
                            </td>
                            <td>
                              <label class="badge badge-gradient-info"><?php echo $row['rating_status']; ?></label>
                            </td>
                              <?php }else{ ?>
                            <td>
                              <label class="badge badge-gradient-danger"><?php echo round($row['average_rating'],2); ?></label>
                            </td>
                            <td>
                              <label class="badge badge-gradient-danger"><?php echo $row['rating_status']; ?></label>
                            </td>
                          <?php } ?>
                          <td>
                            <?php echo $row['srvy_rating']; ?>
                          </td>
                          <td>
                            <?php echo $row['srvy_comments']; ?>
                          </td>
                          <td>
                            <a href="detail.php?survey_id=<?php echo $row['srvy_id'] ?>&overall_rating=<?php echo round($row['average_rating'],2) ?>&status=<?php echo $row['rating_status'] ?>" class="badge badge-gradient-success">Details</a>
                          </td>
                        </tr>
                      <?php } ?>  
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <ul class="pagination">
          <li><a href="?pageno=1">First</a></li>
          <li class="<?php if($pageno <= 1){ echo 'disabled'; } ?>">
              <a href="<?php if($pageno <= 1){ echo '#'; } else { echo "?pageno=".($pageno - 1); } ?>">Prev</a>
          </li>
          <li class="<?php if($pageno >= $total_pages){ echo 'disabled'; } ?>">
              <a href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo "?pageno=".($pageno + 1); } ?>">Next</a>
          </li>
          <li><a href="?pageno=<?php echo $total_pages; ?>">Last</a></li>
        </ul>
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
