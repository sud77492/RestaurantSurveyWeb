 <?php
	date_default_timezone_set("Asia/Kolkata");
	$dt = new DateTime();
	$dt->format('Y-m-d H:i:s');
	$newdt = $dt->format('Y-m-d H:i:s');
class DbHandler {
	private $conn;
	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	public function isValidApiKey($api_key) {
		$stmt = $this->conn->prepare("SELECT `api_key_id` FROM `tbl_api_key` WHERE `key` = ?");
		$stmt->bind_param("s", $api_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
		
	}

	public function isValidUserLoginKey($login_key) {
		$stmt = $this->conn->prepare("SELECT `usr_id` FROM `tbl_users` WHERE `usr_login_key` = ?");
		$stmt->bind_param("s", $login_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function getUserId($login_key) {
		$stmt = $this->conn->prepare("SELECT `usr_id` FROM `tbl_users` WHERE `usr_login_key` = ?");
		$stmt->bind_param("s", $login_key);
		if ($stmt->execute()) {
			$stmt->bind_result($user_id);
			$stmt->fetch();
			$stmt->close();
			return $user_id;
		} else {
			return NULL;
		}
	}

	public function isUserExist($user_name){
		$stmt = $this->conn->prepare("SELECT `id` FROM `users` WHERE `user_name` = ? AND `user_group_id` = 3");
		$stmt->bind_param("s", $user_name);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	public function updateFirebase($firebase_id, $user_id){
		$stmt = $this->conn->prepare("UPDATE tbl_users SET usr_firebase_id = ? WHERE usr_id = ?");
		$stmt->bind_param("si", $firebase_id, $user_id);
		$stmt->execute();
		$stmt->close();
		return 1;
	}
	
	public function userLogin($user_name, $password){
		//echo "SELECT `usr_password` FROM `tbl_users` WHERE `usr_email` = '".$user_name."'";exit;
		$stmt = $this->conn->prepare("SELECT `usr_password` FROM `tbl_users` WHERE `usr_email` = ?");
		$stmt->bind_param("s", $user_name);
		if ($stmt->execute()) {
			$stmt->store_result();
			$stmt->bind_result($password_db);
			$num_rows = $stmt->num_rows;
			$stmt->fetch();
			$stmt->close();
			if($num_rows){
				if($password == $password_db){
					return 1;
				} else {
					return 2;
				}
			} else {
				return 0;
			}
		}
	}
	
	public function insertLoginInfo($user_id, $device_id, $device_name){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		$stmt = $this->conn->prepare("INSERT INTO `tbl_last_login` SET lst_lgn_user_id = ?, lst_lgn_device_id = ?, lst_lgn_device_name = ?, lst_lgn_modified = ?, lst_lgn_created = ?");
		$stmt->bind_param("iisss", $user_id, $device_id, $device_name, $newdt, $newdt);
		$stmt->execute();
		$stmt->close();
		return 1;
	}
	
	public function adminLogin($user_name, $password){
		$stmt = $this->conn->prepare("SELECT `usr_password` FROM `tbl_users` WHERE `usr_email` = ?");
		$stmt->bind_param("s", $user_name);
		if ($stmt->execute()) {
			$stmt->store_result();
			$stmt->bind_result($password_db);
			$num_rows = $stmt->num_rows;
			$stmt->fetch();
			$stmt->close();
			if($num_rows){
				if($password == $password_db){
					return 1;
				} else {
					return 2;
				}
			} else {
				return 0;
			}
		}
	}
	
	public function isUserExistByMobileAndEmail($mobile, $email){
		$stmt = $this->conn->prepare("SELECT `id` FROM `users` WHERE `mobile` = ? OR `email` = ? AND `user_group_id` = 3");
		$stmt->bind_param("ss", $mobile, $email);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	public function getUserDetails($user_name) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_users` where `usr_email` = ?");
		$stmt->bind_param("s", $user_name);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getNotificationOptions(){
		$stmt = $this->conn->prepare("SELECT * FROM tbl_notification_options");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	
	
	public function deleteNotification($user_id){
		$stmt = $this->conn->prepare("DELETE FROM tbl_notifications WHERE notfctn_user_id = ?");
		$stmt->bind_param("s", $user_id);
		if($stmt->execute()){
			$stmt->close();
			return true;
		}else{
			return false;
		}
	}
	
	public function getFirebaseId($user_id) {
		$stmt = $this->conn->prepare("SELECT usr_two.usr_firebase_id, usr_one.usr_restaurant_name FROM tbl_users AS usr_one
										INNER JOIN tbl_users AS usr_two ON usr_one.usr_parent_id = usr_two.usr_id
										WHERE usr_one.usr_id = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->execute()) {
			//$value = $stmt->bind_result($firebase_id, $restaurant_name);

			$result = getResult($stmt);
			$stmt->fetch();
			$stmt->close();
			return $result;
		} else {
			return NULL;
		}
	}
	
	public function getNotification($user_id){
		$stmt = $this->conn->prepare("SELECT  a.notfctn_id, a.notfctn_user_id, a.notfctn_type, b.ntf_opt_name, a.notfctn_rating, b.ntf_opt_id FROM tbl_notifications a LEFT JOIN tbl_notification_options b
            ON FIND_IN_SET(b.ntf_opt_id, a.notfctn_opt_id) > 0 WHERE a.notfctn_user_id = ?");
		$stmt->bind_param("s", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
		
	}
	
	public function addNotification($user_id, $notification_name, $notification_rating, $notification_type_value, $notification_id, $question_id){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		$stmt = $this->conn->prepare("INSERT INTO tbl_notifications SET notfctn_user_id = ?, notfctn_name = ?, notfctn_rating = ?, notfctn_type = ?, notfctn_opt_id = ?, notfctn_opt_question_id = ?, notfctn_modified = ?, notfctn_created = ?");
		$stmt->bind_param("isiiisss", $user_id, $notification_name, $notification_rating, $notification_type_value, $notification_id, $question_id, $newdt, $newdt);
		$stmt->execute();
		$stmt->close();
		return 1;
	}
	
	public function addFeedback($user_id, $fbd_message){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		$stmt = $this->conn->prepare("INSERT INTO tbl_feedbacks SET fdb_user_id = ?, fdb_message = ?, fdb_created = ?, fdb_modified = ?"); 
		$stmt->bind_param("isss", $user_id, $fbd_message, $newdt, $newdt);	
		$stmt->execute();
		$stmt->close();
		return 1;
	}
	
	public function updateProfile($user_id, $user_name, $user_mobile){
		$stmt = $this->conn->prepare("UPDATE `tbl_users` SET `usr_name` = ?,`usr_mobile` = ? WHERE `usr_id` = ?");
		$stmt->bind_param("ssi", $user_name, $user_mobile, $user_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return 1;
	}

	public function getQuestions($user_id) {
		//$stmt = $this->conn->prepare("SELECT * FROM tbl_notifications WHERE notfctn_id = ?");
        $stmt = $this->conn->prepare("SELECT * FROM tbl_questions");
		//$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getOptions($user_id, $question_id) {
		$stmt = $this->conn->prepare("SELECT * FROM tbl_options WHERE opt_ques_id = ?");
		$stmt->bind_param("i", $question_id);
		$stmt->execute();
		$result = getResult($stmt);
		if($result->num_rows > 0){
			$stmt->close();
			return $result;
		}else{
			$stmt2 = $this->conn->prepare("SELECT * FROM tbl_options WHERE opt_ques_id IS NULL");
			$stmt2->execute();
			$result = getResult($stmt2);
			$stmt2->close();
			return $result;
		}
		
		
	}
	
	public function getRatingResponseBarChart($user_id, $question_id) {
		/*echo "SELECT SUM(rspn_answer_rating), count(rspn_answer_rating), ROUND(SUM(rspn_answer_rating)/count(rspn_answer_rating), 2) as average_rating, (SELECT rating_type FROM tbl_questions WHERE ques_id = '".$question_id."') FROM `tbl_responses` WHERE rspn_user_id = (SELECT usr_id FROM tbl_users WHERE usr_parent_id = '".$user_id."') AND rspn_ques_id = '".$question_id."'";exit;*/
		$stmt = $this->conn->prepare("SELECT SUM(rspn_answer_rating), count(rspn_answer_rating), ROUND(SUM(rspn_answer_rating)/count(rspn_answer_rating), 2) as average_rating, (SELECT rating_type FROM tbl_questions WHERE ques_id = ?) as rating_type FROM `tbl_responses` WHERE rspn_user_id = ? AND rspn_ques_id = ?");
		$stmt->bind_param("iii", $question_id, $user_id, $question_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getRatingResponsePieChart($user_id, $question_id) {
		/*echo "SELECT SUM(rspn_answer_rating), count(rspn_answer_rating), ROUND(SUM(rspn_answer_rating)/count(rspn_answer_rating), 2) as average_rating, (SELECT rating_type FROM tbl_questions WHERE ques_id = '".$question_id."') as rating_type FROM `tbl_responses` WHERE rspn_user_id = (SELECT usr_id FROM tbl_users WHERE usr_parent_id = '".$user_id."') AND rspn_ques_id = '".$question_id."'";exit;*/
		$stmt = $this->conn->prepare("SELECT SUM(rspn_answer_rating), count(rspn_answer_rating), ROUND(SUM(rspn_answer_rating)/count(rspn_answer_rating), 2) as average_rating, (SELECT rating_type FROM tbl_questions WHERE ques_id = ?) as rating_type FROM `tbl_responses` WHERE rspn_user_id = ? AND rspn_ques_id = ?");
		$stmt->bind_param("iii", $question_id, $user_id, $question_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function insertSurvey($user_id, $name, $mobile, $rating, $comment, $device_name, $device_id){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$login_key = md5($newdt.$name);
		//echo "INSERT INTO `tbl_surveys`(`srvy_user_id`, `srvy_user_name`, `srvy_user_mobile`, `srvy_rating`, `srvy_comments`, `srvy_device_name`, `srvy_device_id`, `srvy_created_at`) VALUES ($user_id, $name, $mobile, $rating, $comment, $device_name, $device_id, $newdt)";
		$stmt = $this->conn->prepare("INSERT INTO `tbl_surveys`(`srvy_user_id`, `srvy_user_name`, `srvy_user_mobile`, `srvy_rating`, `srvy_comments`, `srvy_device_name`, `srvy_device_id`, `srvy_created_at`) VALUES (?,?,?,?,?,?,?,?)");
		$stmt->bind_param("ississss", $user_id, $name, $mobile, $rating, $comment, $device_name, $device_id, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$survey_id = $stmt->insert_id;
			$stmt->close();
			return $survey_id;
		} else {
			return NULL;
		}
	}
	
	public function responseSubmit($user_id, $insertSurvey, $question_id, $answer_id, $answer_rating){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$stmt = $this->conn->prepare("INSERT INTO `tbl_responses`(`rspn_user_id`, `rspn_survey_id`, `rspn_ques_id`, `rspn_answer`, `rspn_answer_rating`, `rspn_created_at`) VALUES (?,?,?,?,?,?)");
		$stmt->bind_param("iiiiis", $user_id, $insertSurvey, $question_id, $answer_id, $answer_rating, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$user_id = $stmt->insert_id;
			$stmt->close();
			$stmt2 = $this->conn->prepare("SELECT SUM(rspn_answer_rating)/count(rspn_answer_rating) as average_rating FROM `tbl_responses` WHERE rspn_survey_id = ?");
			$stmt2->bind_param("i", $insertSurvey);
			if ($stmt2->execute()) {
				$stmt2->bind_result($average_rating);
				$stmt2->fetch();
				$stmt2->close();
				return $average_rating;
			}
		} else {
			return NULL;
		}
	}

	public function getRating($survey_id, $question_id){
		$stmt = $this->conn->prepare("SELECT * FROM tbl_responses WHERE rspn_survey_id = ? AND rspn_ques_id = ?");
		$stmt->bind_param("ii", $survey_id, $question_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getSurveyReportList($user_id) {
		$stmt = $this->conn->prepare("SELECT *, SUM(rspn_answer_rating), count(rspn_answer_rating), SUM(rspn_answer_rating)/count(rspn_answer_rating) as average_rating, CASE
			WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>4
			THEN  'Excellent'
			WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>3
			THEN  'Good'
			WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>2
			THEN  'Average'
			WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>1
			THEN  'Poor'
			WHEN  SUM(rspn_answer_rating)/count(rspn_answer_rating)>0
			THEN  'Very Poor'
			ELSE NULL 
			END AS  'rating_status' FROM `tbl_surveys` LEFT JOIN `tbl_responses` ON srvy_id = rspn_survey_id WHERE rspn_user_id = ? GROUP BY rspn_survey_id ORDER BY srvy_id DESC");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getRecordCount($user_id) {
		$stmt = $this->conn->prepare("SELECT (SELECT count(*) FROM tbl_surveys WHERE srvy_created_at >= (DATE(NOW()) - INTERVAL 7 DAY) AND srvy_user_id = ?) as current_seven_days, count(*) as current_one_month FROM tbl_surveys WHERE srvy_created_at >= (DATE(NOW()) - INTERVAL 30 DAY) AND srvy_user_id = (SELECT usr_id FROM tbl_users WHERE usr_parent_id = ?)");
		$stmt->bind_param("ii", $user_id, $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getRecordCountLastMonth($user_id) {
		$stmt = $this->conn->prepare("SELECT (SELECT count(*) FROM tbl_surveys WHERE datediff(current_date,date(srvy_created_at)) BETWEEN  8 AND 14 AND srvy_user_id = (SELECT usr_id FROM tbl_users WHERE usr_parent_id = ?)) as last_seven_days, count(*) as last_one_month FROM tbl_surveys WHERE datediff(current_date,date(srvy_created_at)) BETWEEN  31 AND 60  AND srvy_user_id = ?");
		$stmt->bind_param("ii", $user_id, $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
                                     
    public function getNotificationData($user_id){
        $stmt = $this->conn->prepare("SELECT * FROM tbl_notifications WHERE notfctn_user_id = ?");
        //echo "SELECT * FROM tbl_notifications WHERE notfctn_user_id = (SELECT usr_parent_id FROM tbl_users WHERE usr_id = '".$user_id."')";
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = getResult($stmt);
        $stmt->close();
        return $result;
    }
	
	public function getRecordPerDay($user_id) {
		$stmt = $this->conn->prepare("select DATE_FORMAT(t1.srvy_created_at, '%d-%m') as survey_date, t2.srvy_user_id,
				coalesce(SUM(t1.attempt_count+t2.attempt_count), 0) AS number_of_surveys
				from
				(
				  select a.Date as srvy_created_at,
				  '0' as  attempt_count
				  from (
				    select curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
				    from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
				    cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
				    cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
				  ) a
				  where a.Date BETWEEN NOW() - INTERVAL 10 DAY AND NOW()
				)t1
				left join
				(
				  SELECT srvy_user_id, DATE_FORMAT(srvy_created_at,'%Y/%m/%d') AS srvy_created_at, 
				  COUNT(*) AS attempt_count
				  FROM tbl_surveys
				  WHERE DATE_SUB(srvy_created_at, INTERVAL 1 DAY) > DATE_SUB(DATE(NOW()), INTERVAL 15 DAY) AND srvy_user_id = ? 
				  GROUP BY DAY(srvy_created_at) DESC
				)t2
				on t2.srvy_created_at = t1.srvy_created_at
				group by DAY(t1.srvy_created_at)
				order by t1.srvy_created_at desc;
		");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getSurveyDetails($user_id, $survey_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_responses` INNER JOIN `tbl_options` ON rspn_answer = opt_id INNER JOIN `tbl_questions` ON rspn_ques_id = ques_id WHERE rspn_survey_id = ? ORDER BY ques_id ASC");
		$stmt->bind_param("i", $survey_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	

	public function updateAppVersionInUserTable($user_id, $app_version){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt2 = $this->conn->prepare("UPDATE `tbl_users` SET `usr_app_vrsn_code`= ?, `usr_last_login_at` = ? WHERE `usr_id` = ?");
		$stmt2->bind_param("isi", $app_version, $newdt, $user_id);
		$stmt2->execute();
		$stmt2->store_result();
		$num_rows2 = $stmt2->num_rows;
		$stmt2->close();
	}
	
	public function getCurrentAppVersion($device) {
		$stmt = $this->conn->prepare("SELECT app_vrsn_code FROM `tbl_app_versions` WHERE app_vrsn_device = ? ORDER BY app_vrsn_updated_on DESC LIMIT 1");
		$stmt->bind_param("s", $device);
		if ($stmt->execute()) {
			$stmt->bind_result($app_vrsn_code);
			$stmt->fetch();
			$stmt->close();
			return $app_vrsn_code;
		} else {
			return NULL;
		}
	}
}

function getResult($stmt){
        return $stmt->get_result();
	}
?>
