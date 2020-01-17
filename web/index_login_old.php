<?php
  include("common/config.php");
  session_start();
  if(isset($_POST['submit'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    if(!empty($username) && !empty($password)){
      $query = mysqli_query($con, "SELECT * FROM tbl_users WHERE usr_username = '".$username."' && usr_password = '".$password."' AND usr_status = 1 AND usr_parent_id = 0");
    //  echo "SELECT * FROM tbl_users WHERE usr_username = '".$username."' && usr_password = '".$password."' AND usr_status = 1 AND usr_parent_id = 0";exit;
      if(mysqli_num_rows($query) > 0){
        $row = mysqli_fetch_array($query);
        $_SESSION['user_id'] = $row['usr_id'];
        $_SESSION['user_name'] = $row['usr_name'];
        $_SESSION['user_restaurant_name'] = $row['usr_restaurant_name'];
        $_SESSION['user_email'] = $row['usr_email'];
        $_SESSION['user_mobile'] = $row['usr_mobile'];
        header("Location: index.php");
      }else{
        echo '<script language="javascript">';
        echo 'alert("Invalid Credentials")';
        echo '</script>';
      }
    }
    
    
  }
?>
<link href='https://fonts.googleapis.com/css?family=Open+Sans:700,600' rel='stylesheet' type='text/css'>

<form method="post" action="index_login.php">
  <div class="box">
    <h1>Dashboard</h1>
    
    <input type="username" name="username" placeholder="username" onFocus="field_focus(this, 'username');" onblur="field_blur(this, 'username');" class="username" required/>
      
    <input type="password" name="password" placeholder="password" onFocus="field_focus(this, 'password');" onblur="field_blur(this, 'password');" class="password" required/>
      
    <button class="btn default" name="submit">Sign In</button>
    <button class="btn default" onclick="window.location.href='register.php'" name="">Register</button>

    <!--<a href="#"><div id="btn2">Sign Up</div></a>--> <!-- End Btn2 -->
  
  </div> <!-- End Box -->
</form>

<!--<p>Forgot your password? <u style="color:#f1c40f;">Click Here!</u></p>-->
  
<script
  src="https://code.jquery.com/jquery-3.3.1.js"
  integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="
  crossorigin="anonymous"></script>
\
<script>
    function field_focus(field, username)
  {
    if(field.value == username)
    {
      field.value = '';
    }
  }

  function field_blur(field, password)
  {
    if(field.value == '')
    {
      field.value = password;
    }
  }

//Fade in dashboard box
$(document).ready(function(){
    $('.box').hide().fadeIn(1000);
    });

//Stop click event
$('a').click(function(event){
    event.preventDefault(); 
	});
</script>

<style>
    
    body{
  font-family: 'Open Sans', sans-serif;
  background:#3498db;
  margin: 0 auto 0 auto;  
  width:100%; 
  text-align:center;
  margin: 20px 0px 20px 0px;   
}

p{
  font-size:12px;
  text-decoration: none;
  color:#ffffff;
}

h1{
  font-size:1.5em;
  color:#525252;
}

.box{
  background:white;
  width:375px;
  border-radius:6px;
  margin: 0 auto 0 auto;
  padding:0px 0px 70px 0px;
  border: #2980b9 4px solid; 
}

.username{
  background:#ecf0f1;
  border: #ccc 1px solid;
  border-bottom: #ccc 2px solid;
  padding: 8px;
  width:250px;
  color:#AAAAAA;
  margin-top:10px;
  font-size:1em;
  border-radius:4px;
}

.password{
  border-radius:4px;
  background:#ecf0f1;
  border: #ccc 1px solid;
  padding: 8px;
      margin-top: 10px;
  width:250px;
  font-size:1em;
}

.btn{
  background:#2ecc71;
  border: #ccc 1px solid;
  border-bottom: #ccc 2px solid;
  padding: 8px;
  width:250px;
  color:white;
  margin-top:10px;
  font-size:1em;
  border-radius:4px;
  
}

.btn:hover{
  background:#2CC06B; 
}

#btn2{
  float:left;
  background:#3498db;
  width:125px;  padding-top:5px;
  padding-bottom:5px;
  color:white;
  border-radius:4px;
  border: #2980b9 1px solid;
  
  margin-top:20px;
  margin-bottom:20px;
  margin-left:10px;
  font-weight:800;
  font-size:0.8em;
}

#btn2:hover{ 
background:#3594D2; 
}
</style>