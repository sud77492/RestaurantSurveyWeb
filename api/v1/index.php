<?php
require_once '../include/Security.php';
require_once '../include/DbHandler.php';
require '.././libs/Slim/Slim.php';
require '.././libs/PHPMailer/PHPMailerAutoload.php';
require '.././libs/firebase/firebase.php';
require '.././libs/firebase/push.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$user_id = NULL;

function authenticate(\Slim\Route $route) {
    $response = array();
    $app = \Slim\Slim::getInstance();

    $headers = getHeaders();
  
//    $headers = array();
//    $headers["api-key"] = "e0f3b1aaf601996e6cb52264fe3a7a15";
//    $headers["visitor-login-key"] = "5a4341e71c8127f9550d61c8da4809cc";

    $db = new DbHandler();
    if (array_key_exists("api-key",$headers)) {
        $api_key = $headers['api-key'];
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid API key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            if (array_key_exists("user-login-key", $headers)) {
                $login_key = $headers['user-login-key'];
                if (!$db->isValidUserLoginKey($login_key)) {
                    $response["error"] = true;
                    $response["message"] = "Access Denied. Invalid Login key";
                    echoResponse(401, $response);
                    $app->stop();
                } else {
                    global $user_id;
                    $user_id = $db->getUserId($login_key);
                }
            }
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Access Denied. API key is not present";
        echoResponse(400, $response);
        $app->stop();
    }
}

$app->get('/test', function () {
	        $response = array();
	        $db = new DbHandler();
	        $response["message"] = "For testing purpose";
	        echoResponse(200, $response);
        });

$app->get('/test/keys', 'authenticate', function (){
            global $user_id;
            $response = array();
	        $response["error"] = false;
	        $response["visitor_id"] = $user_id;
	        $response["message"] = "Key validation passed";
	        echoResponse(200, $response);
        });

$app->get('/test/echo/:message', function ($message) {
	        $response = array();
	        $response["message"] = $message;
	        echoResponse(200, $response);
        });

$app->get('/test/server_configuration', function () {
            $hasMySQL = false; 
            $hasMySQLi = false; 
            $withMySQLnd = false; 
            $sentence = '';
            if (function_exists('mysql_connect')) {
                $hasMySQL = true;
                $sentence.= "(Deprecated) MySQL is <b>installed</b> ";
            } else{
                $sentence.= "(Deprecated) MySQL is <b>not installed</b> ";
            }
            if (function_exists('mysqli_connect')) {
                $hasMySQLi = true;
                $sentence.= "and the new (improved) MySQL is <b>installed</b>. "; 
            } else{
                $sentence.= "and the new (improved) MySQL is <b>not installed</b>. ";
            }
            if (function_exists('mysqli_get_client_stats')) {
                $withMySQLnd = true;
                $sentence.= "This server is using <b>MySQLnd</b> as the driver."; 
            } else{
                $sentence.= "This server is using <b>libmysqlclient</b> as the driver.";
            }
            echo $sentence;
        });

$app->get('/test/db_connection/:mobile', function ($mobile) {
            $db = new DbHandler();
            $otp = NULL;
            $result = $db->generateOTP($mobile);
            $otp = $db->getOTP($mobile);

            if ($otp != NULL) {
                $response["error"] = false;
                $response["message"] = "OTP generated succesfully but unable to send";
                $response["otp"] = $otp;
                echoResponse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to generate OTP. Please try again";
                echoResponse(200, $response);
            }
        });

$app->get('/test/encryption/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        $response["message_decrypted"] = Security::decrypt(Security::encrypt($message));
	        echoResponse(200, $response);
        });

$app->get('/test/encrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        echoResponse(200, $response);
        });

$app->get('/test/decrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_decrepted"] = Security::decrypt($message);
	        echoResponse(200, $response);
        });

$app->get('/test/phpinfo', function () {
            phpinfo();
        });


$app->post('/login', 'authenticate', function() use ($app) {
            verifyRequiredParams(array('user_name', 'password', 'user_type', 'device_name', 'device_id'));
            $response = array();
            $user_name = $app->request->post('user_name');
            $password = $app->request->post('password');
            $firebase_id = $app->request->post('user_firebase_id');
            $user_type = $app->request->post('user_type');
            $device_name = $app->request->post('device_name');
            $device_id = $app->request->post('device_id');
            $db = new DbHandler();
            switch($user_type){
                case 0:
                    $response_type = $db->adminLogin($user_name, $password);
                    break;
                    
                case 1:
                    $response_type = $db->userLogin($user_name, $password);
                    break;
                    
            }
            switch($response_type){
                case 0:
                    $response["error"] = true;
                    $response["message"] = "User does not exist";
                    break;
                case 1:
                   // $firebase_update =$db->updateFirebase($firebase_id, $user_name);
                    $response["error"] = false;
                    $response["message"] = "User exist and details fetched successfully";
                    $result = $db->getUserDetails($user_name);
                    $user = $result->fetch_assoc();
                    $db->insertLoginInfo($user["usr_id"], $device_id, $device_name);
                    $response['user_id'] = $user["usr_id"];
                    $response['user_name'] = $user["usr_name"];
                    $response['user_restaurant_name'] = $user["usr_restaurant_name"] ? $user["usr_restaurant_name"] : "NA";
                    $response['user_username'] = $user["usr_username"];
                    $response['user_email'] = $user["usr_email"];
                    $response['user_mobile'] = $user["usr_mobile"];
                    $response['user_login_key'] = $user["usr_login_key"];
                    break;
                    
                case 2:
                    $response["error"] = true;
                    $response["message"] = "Invalid login credentials";
                    break;
            }
            echoResponse(200, $response);
        });
        
        
    $app->get('/init', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            $resultQues = $db->getQuestions($user_id);
            if($resultQues){
                $response["error"] = false;
                $response["message"] = "Question fetched successfully";
                $response["questions"] = array();
                while($rowQues = $resultQues->fetch_assoc()){
                    $tmp = array();
                    $tmp["question_id"] = $rowQues["ques_id"];
                    $tmp["question_english"] = $rowQues["ques_name_eng"];
                    $tmp["question_hindi"] = $rowQues["ques_name_hin"];
                    $tmp["options"] = array();
                    $resultOpt = $db->getOptions($user_id, $rowQues["ques_id"]);
                    if($resultOpt){
                        while($rowOpt = $resultOpt->fetch_assoc()){
                            $tmp2 = array();
                            $tmp2["option_id"] = $rowOpt["opt_id"];
                            $tmp2["option_english"] = $rowOpt["opt_name_eng"];
                            $tmp2["option_hindi"] = $rowOpt["opt_name_hin"];
                            array_push($tmp["options"], $tmp2);
                        }
                    }
                    array_push($response['questions'], $tmp);
                }
                
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Question could not be fetched. Try again";
                echoResponse(200, $response);
            }

        });
        
    $app->post('/init_vendor', 'authenticate', function() use ($app) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            $firebase_id = $app->request->post('firebase_id');
            $updateFirebase = $db->updateFirebase($firebase_id, $user_id);
            $recordCount = $db->getRecordCount($user_id);
            $recordCountLastMonth = $db->getRecordCountLastMonth($user_id);
            $noRecordDay = $db->getRecordPerDay($user_id);
            if($noRecordDay){
                $recordRow = $recordCount->fetch_assoc();
                $recordLastMonthRow = $recordCountLastMonth->fetch_assoc();
                $response["error"] = false;
                $response["message"] = "Record fetched successfully";
                $response["response_last_one_month"] = $recordLastMonthRow['last_seven_days'];
                $response["response_last_seven_days"] = $recordLastMonthRow['last_one_month'];
                $response["response_current_one_month"] = $recordRow['current_one_month'];
                $response["response_current_seven_days"] = $recordRow['current_seven_days'];
                $response["reports"] = array();
                $response["survey_per_day"] = array();
                while($rowSurveyPerDay = $noRecordDay->fetch_assoc()){
                    $tmp = array();
                    $tmp["survey_per_day"] = $rowSurveyPerDay["number_of_surveys"];
                    $tmp["survey_date"] = $rowSurveyPerDay["survey_date"];
                    array_push($response['survey_per_day'], $tmp);
                }
                
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Record could not be fetched. Try again";
                echoResponse(200, $response);
            }

        });
        
    $app->get('/response_list', 'authenticate', function (){
        global $user_id;
        $response = array();
        $db = new DbHandler();
        $resultReport = $db->getSurveyReportList($user_id);
        if($resultReport){
            $response["error"] = false;
            $response["message"] = "Survey Report list fetched successfully";
            $response["reports"] = array();
            while($rowReport = $resultReport->fetch_assoc()){
                $tmp = array();
                $tmp["report_customer_survey_id"] = $rowReport["srvy_id"];
                $tmp["report_customer_name"] = $rowReport["srvy_user_name"] ? $rowReport["srvy_user_name"] : "NA";
                $tmp["report_customer_mobile"] = $rowReport["srvy_user_mobile"] ? $rowReport["srvy_user_mobile"] : "NA";
                $tmp["report_customer_star"] = $rowReport["srvy_rating"];
                $tmp["report_customer_comment"] = $rowReport["srvy_comments"] ? $rowReport["srvy_comments"] : "NA";
                $tmp["report_customer_average_rating"] = round($rowReport["average_rating"],2);
                $tmp["report_customer_status"] = $rowReport["rating_status"];
                $tmp["report_customer_date"] = $rowReport["srvy_created_at"];
                array_push($response['reports'], $tmp);
            }
            
            echoResponse(200, $response);
        } else {
            $response["error"] = true;
            $response["message"] = "Survey Report could not be fetched. Try again";
            echoResponse(200, $response);
        }
    });
        
        
        
    $app->get('/graph_list', 'authenticate', function (){
        global $user_id;
        $response = array();
        $db = new DbHandler();
        $resultQues = $db->getQuestions($user_id);
        if($resultQues){
            $response["error"] = false;
            $response["message"] = "Question fetched successfully";
            $response["questions"] = array();
            while($rowQues = $resultQues->fetch_assoc()){
                $tmp = array();
                $tmp["question_id"] = $rowQues["ques_id"];
                $tmp["question_english"] = $rowQues["ques_name_eng"];
                if($rowQues['ques_option_check'] == 0){
                    $resultOpt = $db->getRatingResponseBarChart($user_id, $rowQues["ques_id"]);
                    if($resultOpt){
                        $rowOpt = $resultOpt->fetch_assoc();
                        $tmp["option_check"] = "SAME";
                        $tmp["rating"] = $rowOpt["average_rating"];
                        $tmp["rating_type"] = $rowOpt["rating_type"];
                        
                    }
                }else{
                    $resultOpt = $db->getRatingResponsePieChart($user_id, $rowQues["ques_id"]);
                    if($resultOpt){
                        $rating = "";
                        while($rowOpt = $resultOpt->fetch_assoc()){
                            $rating = $rating.",".$rowOpt["average_rating"];
                            $rating_type = $rowOpt["rating_type"];
                        }
                        $tmp["option_check"] = "DIFFERENT";
                        $tmp["rating"] = trim($rating,",");
                        $tmp["rating_type"] = $rating_type;
                    }
                }
                array_push($response['questions'], $tmp);
            }
            
            echoResponse(200, $response);
        } else {
            $response["error"] = true;
            $response["message"] = "Question could not be fetched. Try again";
            echoResponse(200, $response);
        }
    });
    
        
    $app->get('/notification_list', 'authenticate', function (){
        global $user_id;
        $response = array();
        $db = new DbHandler();
        $notification = $db->getNotification($user_id);
        $notification_options = $db->getNotificationOptions();
        if($notification && $notification_options){
            $response["error"] = false;
            $response["message"] = "Survey Notification fetched successfully";
            $response["survey_notifications"] = array();
            $response["notification_options"] = array();
            if($notification->num_rows > 1){
                $i = 0;
                while($row = $notification->fetch_assoc()){
                    $rating = explode(",",$row['notfctn_rating']);
                    $response["notification_type"] = "INDIVIDUAL"; 
                    $tmp = array();
                    $tmp["notification_id"] = $row["notfctn_id"];
                    $tmp["notification_name"] = $row["ntf_opt_name"];
                    $tmp["notification_rating"] = $rating[$i];
                    array_push($response['survey_notifications'], $tmp);
                    $i++;
                }
            }else{
                $row = $notification->fetch_assoc();
                switch($row['notfctn_type']){
                    case 3 :
                        $response["notification_type"] = "OVERALL"; 
                        $tmp = array();
                        $tmp["notification_id"] = $row["notfctn_id"];
                        $tmp["notification_name"] = "OVERALL";
                        $tmp["notification_rating"] = $row["notfctn_rating"];
                        array_push($response['survey_notifications'], $tmp);
                    break;
                    
                    case 1 :
                        $response["notification_type"] = "SPECIFIC"; 
                        $tmp = array();
                        $tmp["notification_id"] = $row["notfctn_id"];
                        $tmp["notification_name"] = $row["ntf_opt_name"];
                        $tmp["notification_rating"] = $row["notfctn_rating"];
                        array_push($response['survey_notifications'], $tmp);
                    break;
                }
            }
            while($row = $notification_options->fetch_assoc()){
                $tmp = array();
                $tmp["notification_option_id"] = $row["ntf_opt_id"];
                $tmp["notification_option_ques_id"] = $row["ntf_opt_ques_id"];
                $tmp["notification_option_name"] = $row["ntf_opt_name"];
                array_push($response['notification_options'], $tmp);
            }
            echoResponse(200, $response);
            
        }else{
            $response["error"] = true;
            $response["message"] = "Survey Notification could not be fetched. Try again";
            echoResponse(200, $response);
        }
    });
    
    $app->post('/add_notification', 'authenticate', function() use ($app) {
        global $user_id;
        verifyRequiredParams(array('notification_rating'));
        $response = array();
        $notification_name = $app->request->post('notification_name');
        $notification_rating = $app->request->post('notification_rating');
        $notification_type_value = $app->request->post('notification_type_value');
        $notification_id = $app->request->post('notification_id');
        $question_id = $app->request->post('question_id');
        $db = new DbHandler();
        $delete_notification = $db->deleteNotification($user_id);
        if($delete_notification){
            $add_notification = $db->addNotification($user_id, $notification_name, $notification_rating, $notification_type_value, $notification_id, $question_id);
            if($add_notification){
                $response["error"] = false;
                $response["message"] = "Notification added succesfully";
            }else{
                $response["error"] = true;
                $response["message"] = "Notification could not be added. Try again";
            }
        }else{
            $response["error"] = true;
            $response["message"] = "Notification could not be added. Try again";
        }
        echoResponse(200, $response);
    });
    
    
    $app->post('/add_feedback', 'authenticate', function() use ($app) {
        global $user_id;
        verifyRequiredParams(array('feedback_message'));
        $response = array();
        $fbd_message = $app->request->post('feedback_message');
        $db = new DbHandler();
        $add_feedback = $db->addFeedback($user_id, $fbd_message);
        if($add_feedback){
            $response["error"] = false;
            $response["message"] = "Feedback added succesfully";
        }else{
            $response["error"] = true;
            $response["message"] = "Feedback could not be added. Try again";
        }
        echoResponse(200, $response);
    });
    
    $app->post('/update_profile', 'authenticate', function() use ($app) {
        global $user_id;
        verifyRequiredParams(array('user_name', 'user_mobile'));
        $response = array();
        $user_name = $app->request->post('user_name');
        $user_mobile = $app->request->post('user_mobile');
        $db = new DbHandler();
        $update_profile = $db->updateProfile($user_id, $user_name, $user_mobile);
        if($update_profile){
            $response["error"] = false;
            $response["message"] = "Profile Updated succesfully";
        }else{
            $response["error"] = true;
            $response["message"] = "Profile could not be updated. Try again";
        }
        echoResponse(200, $response);
    });
        
    $app->post('/survey_details', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('survey_id'));
            $response = array();
            $survey_id = $app->request->post('survey_id');
            $db = new DbHandler();
            $resultSurveyDetails = $db->getSurveyDetails($user_id, $survey_id);
            if($resultSurveyDetails){
                $response["error"] = false;
                $response["message"] = "Survey Details fetched successfully";
                $response["survey_details"] = array();
                while($rowSurveyDetails = $resultSurveyDetails->fetch_assoc()){
                    $tmp = array();
                    $tmp["survey_detail_ques_id"] = $rowSurveyDetails["ques_id"];
                    $tmp["survey_detail_ques_english"] = $rowSurveyDetails["ques_name_eng"];
                    $tmp["survey_detail_ques_hindi"] = $rowSurveyDetails["ques_name_hin"];
                    $tmp["survey_detail_rating_status"] = $rowSurveyDetails["opt_name_eng"];
                    array_push($response['survey_details'], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Survey Details could not be fetched. Try again";
                echoResponse(200, $response);
            }
        });
        
        
    $app->post('/submit_response', 'authenticate', function() use ($app) {
            verifyRequiredParams(array('answer_ids', 'response_rating', 'device_name', 'device_id'));
            global $user_id;
            $response = array();
            $name = $app->request->post('name');
            $mobile = $app->request->post('mobile');
            $answer_ids = $app->request->post('answer_ids');
            $response_rating = $app->request->post('response_rating');
            $rating = $app->request->post('rating');
            $comment = $app->request->post('comment');
            $firebase_id = $app->request->post('firebase_id');
            $device_name = $app->request->post('device_name');
            $device_id = $app->request->post('device_id');
            $data_answer_ids = explode( ",",rtrim($answer_ids, ','));
            $data_response_rating = explode( ",",rtrim($response_rating, ','));

            $db = new DbHandler();
            
            $insertSurvey = $db->insertSurvey($user_id, $name, $mobile, $rating, $comment, $device_name, $device_id);
            if($insertSurvey){
                $notification_data = $db->getNotificationData($user_id);
                foreach ($data_answer_ids as $key => $value){
                	$question_id = $key + 1;
                    $result = $db->responseSubmit($user_id, $insertSurvey, $key + 1, $value, $data_response_rating[$key]);
                }
                if($result){
                	$firebase_id = $db->getFirebaseId($user_id);
                	$firebase_data = $firebase_id->fetch_assoc();
					if(($notification_data->num_rows) > 0){
                    	$getFirbaseInfo = $db->getFirebaseId($user_id);
	                    $row = $notification_data->fetch_assoc();
	                    $question_id = $row["notfctn_opt_question_id"];
	                    $rating = $row["notfctn_rating"];
	                    switch($row["notfctn_type"]){
	                        case 1:
	                        	$rating_response = $db->getRating($insertSurvey, $question_id);
	                        	$row_rating = $rating_response->fetch_assoc();
	                        	if(($row_rating['rspn_answer_rating']) < $rating){
                        			pushFirebaseNotification($rating, $firebase_data['usr_firebase_id']);
                        		}
	                        	
	                        break;

	                        case 2:
	                        	pushFirebaseNotification($result, $firebase_data['usr_firebase_id']);
		                        
	                        break;

	                        case 3:
	                        	pushFirebaseNotification($result, $firebase_data['usr_firebase_id']);
		                        if($result < $rating){
		                        	pushFirebaseNotification($result, $firebase_data['usr_firebase_id']);
			                    }
	                        break;
	                    }
                
                	}
                    
                    $response["error"] = false;
                    $response["message"] = "Your Response Successfully submitted";
                }else{
                    $response["error"] = true;
                    $response["message"] = "Your Response is not s ubmitted1";
                }
            }else{
                $response["error"] = true;
                $response["message"] = "Your Response is not submitted2";
            }
            echoResponse(200, $response);
            
    });
    
$app->post('/submit/profile', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('name', 'email', 'mobile', 'job_id'));
            $response = array();
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $mobile = $app->request->post('mobile');
            $job_id = $app->request->post('job_id');
            $resume = $app->request->post('resume');
        
        
        

            $db = new DbHandler();
            $candidate_id = $db->submitProfile($user_id, $job_id, $name, $email, $mobile, $resume);
            if($candidate_id) {
                $db->insertRefererCandidate($user_id, $job_id, $candidate_id);
                $response["error"] = false;
                $response["message"] = "Profile submitted successfully";
                $referee_name = $db->getRefereeName($user_id);
                sendJobEmail($email, $name, $referee_name);
                echoResponse(200, $response);
            } else{
                $response["error"] = true;
                $response["message"] = "Profile not submitted. Try again later";
                echoResponse(200, $response);
            }
    });


$app->post('/upload/file', function() use ($app) {
            global $user_id;
            $response = array();
            
            $db = new DbHandler();


            $file_path = ".././uploads/";
     
            $target_file = $file_path . basename($_FILES["uploaded_file"]["name"]);
            $extension = pathinfo($target_file,PATHINFO_EXTENSION);
     
     
         	$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    	    $randomString = '';
            for ($i = 0; $i < 2; $i++) {
            	$randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
	    
	        $characters2 = '0123456789';
            $randomString2 = '';
            for ($i = 0; $i < 6; $i++) {
            	$randomString2 .= $characters2[rand(0, strlen($characters2) - 1)];
    	    }
        
            $file_name = $randomString.$randomString2.'.'.$extension;
    	    $file_path = $file_path.$file_name;
            
            if($extension == "doc" || $extension == "docx" || $extension == "pdf"){
                if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $file_path) ){
                    $response["error"] = false;
                    $response["message"] = "File Uploaded Successfully";
                    $response["file_name"] = $file_name;
                    $response["file_type"] = $extension;
                    echoResponse(200, $response);
                } else{
                    $response["error"] = true;
                    $response["message"] = "File not Uploaded. Try again later";
                    echoResponse(200, $response);
                }    
            } else{
                $response["error"] = true;
                $response["message"] = "Invalid format. Kindly upload the resume in pdf, doc and docx extension only";
                $response["file_type"] = $extension;
                echoResponse(200, $response);
            }
    });





$app->get('/campaign/sms/:event_id', function ($event_id) {
            $db = new DbHandler();
            $response = array();
            try {
                putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/indiasupply55.json');
                $client = new Google_Client;
                $client->useApplicationDefaultCredentials();
     
                $client->setApplicationName("Something to do with my representatives");
                $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
     
                if ($client->isAccessTokenExpired()) {
                    $client->refreshTokenWithAssertion();
                }
    
                $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
                ServiceRequestFactory::setInstance(new DefaultServiceRequest($accessToken));
    
                // Get our spreadsheet
                $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
                ->getSpreadsheetFeed()
                ->getByTitle('Expodent Chandigarh');
     
                // Get the first worksheet (tab)
                $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
                $worksheet = $worksheets[0];
                $listFeed = $worksheet->getListFeed();

                $event_name = $db->getEventNameByID($event_id);
            
                $user_details = array();
                
                foreach ($listFeed->getEntries() as $entry) {
                    $tmp = array();
                    if (strtoupper($entry->getValues()['ihavealreadyregisteredonisdentalapp']) != 'YES') {
                        $entry->update(array_merge($entry->getValues(), ['ihavealreadyregisteredonisdentalapp' => 'YES']));
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        sendEventRegisterationSMS($tmp["name"], $tmp["mobile"], $event_name);
                        array_push($user_details, $tmp);
                    }
                }
            
                $no_of_sms = sizeof($user_details);
      
                if($no_of_sms>0){
                    $response["error"] = false;
                    $response["message"] = "SMS sent successfully to ".$no_of_sms." Users";
                    $response["users"] = $user_details;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = false;
                    $response["message"] = "No new user registered";
                    unset($response["users"]);
                    echoResponse(200, $response);
                }
            } catch(Exception $e) {
                $response["error"] = true;
                $response["message"] = $e->getMessage();
                echoResponse(200, $response);
            }
        });



$app->get('/campaign/sms/test', function () {
    
            $response = array();
       
            putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/test.json');
            $client = new Google_Client;
            $client->useApplicationDefaultCredentials();
     
            $client->setApplicationName("Something to do with my representatives");
            $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
     
            if ($client->isAccessTokenExpired()) {
                $client->refreshTokenWithAssertion();
            }
    
            $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
            ServiceRequestFactory::setInstance(new DefaultServiceRequest($accessToken));
    
            // Get our spreadsheet
            $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
            ->getSpreadsheetFeed()
            ->getByTitle('Test Google Script SMS');
 
            // Get the first worksheet (tab)
            $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[0];
            $listFeed = $worksheet->getListFeed();
 

            foreach ($listFeed->getEntries() as $entry) {
                if (strtoupper($entry->getValues()['smssent']) === 'NO') {
                    $entry->update(array_merge($entry->getValues(), ['smssent' => 'YES']));
                    array_push($response, $entry->getValues());
                }
            }
  
            echo json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);   
            
        });
    
function siteURL(){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName."/";
}


/**
 * Verifying required params posted or not
 */
 
 
function pushFirebaseNotification($rating, $firebase_id){
	$notification_type = 1;
    $firebase = new Firebase();
    $push = new Push();
    $payload = array();
    $payload['team'] = 'India';
    $payload['rating'] = $rating;
    $push->setTitle("Test Restaurant");
    $push->setMessage("This customer has given rating less than ".$rating);
    $push->setImage('');
    $push->setIsBackground(FALSE);
    $push->setPayload($payload);
    $json = '';
    $response = '';
    $json = $push->getPush();
    $response = $firebase->send(trim($firebase_id), $json);
    print_r($response);
}
 
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    
    //header('Content-Type: application/json; charset=UTF-8');
    $app->contentType('application/json;');
    echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
//    $app->contentType('text/xml');
//    echo xml_encode($response);
}

function getHeaders(){
    $headers = array();
    foreach ($_SERVER as $k => $v) {
        if (substr($k, 0, 5) == "HTTP_") {
            $k = strtolower(str_replace('_', '-', substr($k, 5)));
//            $k = str_replace(' ', '_', ucwords(strtolower($k)));
            $headers[$k] = $v;
        }
    }
    return $headers;
}

function sendOTPSMS($mobile, $otp){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    $username="actiknow";
    $password="actiknow@2017";
//    $username="shout";
//    $password="shout@share";
	$message= $otp." is your login OTP for ISDental application.";
	$sender="INSPLY"; //ex:INVITE
	$mobile_number = $mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
    return true;
}

function sendEventRegisterationSMS($user_name, $user_mobile, $event_name){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    $username="actiknow";
    $password="actiknow@2017";
//    $username="shout";
//    $password="shout@share";
	$message= "Dear ".$user_name.",\nCongratulations! You have been registered for ".$event_name.". Please show this message at registration desk and get your entry badge. Thanks.";
	$sender="INSPLY"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
    return true;
}

function sendWelcomeSMS($user_mobile){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    $username="actiknow";
    $password="actiknow@2017";
//    $username="shout";
//    $password="shout@share";
	$message= "Hi, You have joined 20,000+ Dentists on ISDental App. Now get contact details of ALL Dental Brands and Dealers. Get latest update about upcoming events. Thanks";
	$sender="INSPLY"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
    return true;
}

function sendWelcomeEmail($user_email, $user_name){
    try{
        //PHPMailer Object
        $mail = new PHPMailer;
        //Enable SMTP debugging. 
        //$mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
        
        //    $mail->Username = "actipatient@gmail.com";                 
        //    $mail->Password = "actipatient1234";                           
            
            
        $mail->Username = "indiasupply55@gmail.com";                 
        $mail->Password = "indiasupply#2016";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
            
        $mail->From = "noreply@indiasupply.com";
        $mail->FromName = "IndiaSupply";
            
        $mail->addAddress($user_email);
            
        //$mail->isHTML(true);
            
        $mail->Subject = "Congratulations! You have joined 20,000+ Dentists";
        $mail->Body = "
Hi ".$user_name.",

Welcome to ISDental App. 

Now you need not keep multiple visiting cards. Find contact details of ALL Dental Brands and Dealers on this app.

Also get latest updates about upcoming expos, conferences and workshops and never miss anything important around you.

We're glad to have you here, kindly contact us at isdental@indiasupply.com for any assistance.

Best Regards,

Team IndiaSupply";
        //$mail->AltBody = "This is the plain text version of the email content";
            
        if(!$mail->send()) {
                //    echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    }
    return false;
}

function sendJobEmail($user_email, $user_name, $referer_name){
    try{
        //PHPMailer Object
        $mail = new PHPMailer;
        //Enable SMTP debugging. 
        //$mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
        
        //    $mail->Username = "actipatient@gmail.com";                 
        //    $mail->Password = "actipatient1234";                           
            
            
        $mail->Username = "support@actiknow.com";                 
        $mail->Password = "actiknow@123";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
            
        $mail->From = "noreply@referex.com";
        $mail->FromName = "REFEREX";
            
        $mail->addAddress($user_email);
            
        //$mail->isHTML(true);
            
        $mail->Subject = "Congratulations! You have been referred for a job";
        $mail->Body = "
Hi ".$user_name.",

You have been referred for a job


Best Regards,
Team Referex";
        //$mail->AltBody = "This is the plain text version of the email content";
            
        if(!$mail->send()) {
                //    echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    }
    return false;
}


$app->run();
?>
