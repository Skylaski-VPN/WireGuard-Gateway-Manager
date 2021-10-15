<?php
// api/0.1/domain API
$returnArray = array('status' => 'Failed', 'message' => 'Failed', 'success' => False, 'result' => array());

// First we check for proper headers, should receive content-type: application/json & an Authorization token.
$headers = apache_request_headers();

// DEBUG LOG INCOMING HEADERS
foreach($headers as $key => $value){
	error_log("HEADERS: - $key : $value");
}

// Cloudflare sometimes messes with the cases on these headers
if($headers['Content-Type'] == 'application/json' || $headers['content-type'] == 'application/json'){
	if(isset($headers['Authorization']) || isset($headers['authorization'])){
		$auth = $headers['Authorization'];
		//var_dump($auth);
		if(isset($headers['Authorization'])){
			preg_match('/^Bearer (.*)$/',$headers['Authorization'],$matches);
			$token = $matches[1];
		}
		else{
			preg_match('/^Bearer (.*)$/',$headers['authorization'],$matches);
			$token = $matches[1];
		}
		//echo "<p>Token: ".$token."</p>";
	}
	else{
		$returnArray['message']='Improper Authorization Header';
		error_log("Improper Authorization Header");
		echo json_encode($returnArray);
		exit();
	}
}
else{
	$returnArray['message']='Improper Content-Type Header';
	error_log("Improper Content-Type Header");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}

// Now we have a token to check. 
require '../include/config.php';
require '../include/include.php';
require '../include/commands.php';
require '../include/wgm_include.php';

$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$wgm_db){
	$returnArray['message']='Encountered and Error';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}

$get_user_sql = "SELECT * FROM users WHERE token='".pg_escape_string($token)."'";
$get_user_ret = pg_query($wgm_db,$get_user_sql);
if(!$get_user_ret){
	$returnArray['message']='Encountered an Error';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}
$user = pg_fetch_assoc($get_user_ret);
if(!isset($user['id'])){
	$returnArray['message']='Unauthorized';
	echo json_encode($returnArray);
	//http_response_code(404);
	pg_close($wgm_db);
	exit();
}
//echo "<p>User ID: ".$user['id']."</p>";
// User Authorization PASS

// Get Domain Information
$get_user_domain_sql = "SELECT * FROM domains WHERE id=".pg_escape_string($user['domain_id']);
$get_user_domain_ret = pg_query($wgm_db,$get_user_domain_sql);
if(!$get_user_domain_ret){
	$returnArray['message']='Encountered an Error';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}
$domain = pg_fetch_assoc($get_user_domain_ret);
if(!isset($domain['id'])){
	$returnArray['message']='User has no associated Domain';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}

// Get contents
// Takes raw data from the request
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

// This MUST be called with a 'cmd'
if(!isset($data->cmd)){
	$returnArray['message']='No Command Provided';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}

switch ($data->cmd){
	case "get_domain_users":
		if($user['role'] == 'primary'){
			$get_domain_users_sql = "SELECT * FROM users WHERE domain_id=".pg_escape_string($domain['id'])." AND id<>".pg_escape_string($user['id']);
			//error_log($get_domain_users_sql);
			$get_domain_users_ret = pg_query($wgm_db,$get_domain_users_sql);
			if($get_domain_users_ret){
				$result = array();
				while($user = pg_fetch_assoc($get_domain_users_ret)){
					array_push($result,array($user['user_email'],$user['unique_id']));
				}
				$returnArray['result'] = $result;
				$returnArray['status'] = 'Success';
				$returnArray['message'] = 'Success';
				$returnArray['success'] = True;
				//echo "<p>Getting User</p>";
				echo json_encode($returnArray);
				pg_close($wgm_db);
				exit();
			}
			else{
				$returnArray['message'] = "Failed to query DB";
				echo json_encode($returnArray);
				pg_close($wgm_db);
				exit();
			}
		}
		else{
			$returnArray['message'] = "Not domain primary";
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
		
		break;
	
	case "delete_domain_user":
		if($user['role'] == 'primary'){
	
			if(isset($data->user_uid)){
				// Single user deletion
				// Find the user
				$get_user_sql = "SELECT * FROM users WHERE unique_id='".pg_escape_string($data->user_uid)."'";
				//error_log("Finding user: $get_user_sql");
				$get_user_ret = pg_query($wgm_db,$get_user_sql);
				$domain_user = pg_fetch_assoc($get_user_ret);
				
				// First get clients and delete them properly
				$get_user_clients_sql = "SELECT * FROM clients WHERE user_id=".pg_escape_string($domain_user['id']);
				$get_user_clients_ret = pg_query($wgm_db,$get_user_clients_sql);
				if(!$get_user_clients_ret){
					error_log("user api: delete_user: DB Error: ".pg_last_error($wgm_db));
					pg_close($wgm_db);
					exit();
				}
				while($client = pg_fetch_assoc($get_user_clients_ret)){
					deleteClient($client['local_uid'],$wgm_db,$PATH_TO_CLI);
				}
				
				// Delete user
				$delete_user_sql = "DELETE FROM users WHERE id=".pg_escape_string($domain_user['id']);
				$delete_user_ret = pg_query($wgm_db,$delete_user_sql);
				
				// Leave the domain alone, this user isn't a primary member. 
				
				// Email the user to let them know they've been removed from the VPN plan
				if (filter_var($domain_user['user_email'], FILTER_VALIDATE_EMAIL)) {
					sendPlanEmail($domain_user['user_email'],"MemberRemoved");
				}
				
				// We're done!
				$returnArray['status']='Success';
				$returnArray['message']='Success';
				$returnArray['success']=True;
				echo json_encode($returnArray);
				pg_close($wgm_db);
				exit();
			}
		}
		
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		exit();
	
		break;
	
}


pg_close($wgm_db);

?>

