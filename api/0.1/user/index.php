<?php
// api/0.1/user API
$returnArray = array('status' => 'Failed', 'message' => 'Failed', 'success' => False, 'result' => array());

// First we check for proper headers, should receive content-type: application/json & an Authorization token.
$headers = apache_request_headers();

if(isset($headers['Content-Type']) && isset($headers['Authorization'])){
	if($headers['Content-Type'] == 'application/json'){
		$auth = $headers['Authorization'];
		//var_dump($auth);
		preg_match('/^Bearer (.*)$/',$headers['Authorization'],$matches);
		$token = $matches[1];
		//echo "<p>Token: ".$token."</p>";
	}
	else{
		$returnArray['message']='Improper Content-Type Header';
		error_log("Improper Content-Type Header");
		echo json_encode($returnArray);
		//http_response_code(400);
		exit();
	}
}
else{
	$returnArray['message']='Improper Request Headers';
	error_log('Improper Request Headers');
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}

// Now we have a token to check. 
require '../include/config.php';
require '../include/include.php';
require '../include/commands.php';

$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$wgm_db){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}
$pos_db = pg_connect( "$db_host $db_port $pos_db_name $db_credentials" );
if(!$pos_db){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}

$get_user_sql = "SELECT * FROM users WHERE token='".pg_escape_string($token)."'";
$get_user_ret = pg_query($wgm_db,$get_user_sql);
if(!$get_user_ret){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}
$user = pg_fetch_assoc($get_user_ret);
if(!isset($user['id'])){
	$returnArray['message']='Unauthorized';
	error_log("Unauthorized");
	echo json_encode($returnArray);
	//http_response_code(404);
	exit();
}
//echo "<p>User ID: ".$user['id']."</p>";
// User Authorization PASS

// Get Domain Information
$get_user_domain_sql = "SELECT * FROM domains WHERE id=".pg_escape_string($user['domain_id']);
$get_user_domain_ret = pg_query($wgm_db,$get_user_domain_sql);
if(!$get_user_domain_ret){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}
$domain = pg_fetch_assoc($get_user_domain_ret);
if(!isset($domain['id'])){
	$returnArray['message']='User has no associated Domain';
	error_log("User has no associated Domain");
	echo json_encode($returnArray);
	//http_response_code(400);
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
	error_log("No Command Provided");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}


// HERE THE API COMMANDS BEGIN
switch ($data->cmd){
	case "get_checkout":
		$get_checkout_sql = "SELECT * FROM checkouts che JOIN payment_methods pay ON che.payment_method=pay.id WHERE user_id=".pg_escape_string($user['id'])." ORDER BY updated_at DESC";
		$get_checkout_ret = pg_query($pos_db,$get_checkout_sql);
		$checkout = pg_fetch_assoc($get_checkout_ret);
		
		$returnArray['result'] = $checkout;
		$returnArray['status'] = 'Success';
		$returnArray['message'] = 'Success';
		$returnArray['success'] = True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		exit();
		break;
		
	case "google_checkout":
		error_log("GOOGLE_CHECKOUT");
		if(!isset($data->purchaseCode) || !isset($data->sku)){
			error_log("Not Enough Parameters");
			$returnArray['message']='Not Enough Parameters';
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		
		// First we check to make sure there is an official purchaseCode already provided by Google via our subscription
		$get_google_payment_sql = "SELECT * FROM payments_googleplay WHERE purchase_code='".pg_escape_string($data->purchaseCode)."'";
		$get_google_payment_ret = pg_query($pos_db,$get_google_payment_sql);
		if(!isset($get_google_payment_ret)){
			$returnArray['message'] = 'DB Error';
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$google_payment = pg_fetch_assoc($get_google_payment_ret);
		// Is there a match?
		if($google_payment['purchase_code'] != $data->purchaseCode){
			$returnArray['message'] = 'Invalid Purchase Code';
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		// Match found. Let's see if there is a checkout already
		// Google checkouts start in complete
		$get_cur_checkout_sql = "SELECT * FROM checkouts WHERE transaction_id='".pg_escape_string($data->purchaseCode)."'";
		$get_cur_checkout_ret = pg_query($pos_db,$get_cur_checkout_sql);
		$cur_checkout = pg_fetch_assoc($get_cur_checkout_ret);
		if($cur_checkout){
			if($cur_checkout['transaction_id'] == $data->purchaseCode){
				// checkout already exists
				// Find an active VPN plan
				$find_vpn_plan_sql = "SELECT * FROM active_plans WHERE checkout_id=".pg_escape_string($cur_checkout['id']);
				$find_vpn_plan_ret = pg_query($pos_db,$find_vpn_plan_sql);
				$vpn_plan = pg_fetch_assoc($find_vpn_plan_ret);
				if($vpn_plan['checkout_id'] == $cur_checkout['id']){
					$returnArray['Message'] = 'VPN Plan Already Exists';
					echo json_encode($returnArray);
					pg_close($wgm_db);
					pg_close($pos_db);
					exit();
				}
				
				// Checkout already exists, but a VPN plan doesn't exist
				$result = createNewVPNPlan($cur_checkout,$pos_db,$wgm_db);
				if($result['status']){
					//$_SESSION['user_token']=$result['user_token'];
					//error_log("New VPN Plan Created: coinbase");
				}
				else{
					error_log("FAILED to create VPN Plan: coinbase");
				}
			}
		}
		
		// Transaction confirmed by google and no current plan exists.
		// Get Product
		$get_product_sql = "SELECT * FROM products WHERE id=".pg_escape_string($data->sku);
		$get_product_ret = pg_query($pos_db,$get_product_sql);
		$product = pg_fetch_assoc($get_product_ret);
		
		// Create a checkout
		$new_checkout_uid = createCheckoutCode($pos_db);
		$create_checkout_sql = "INSERT INTO checkouts (payment_method,domain_id,user_id,product_id,total,subtotal,unique_id,status,referral_code,transaction_id) VALUES ( 4, ".pg_escape_string($domain['id']).", ".pg_escape_string($user['id']).", ".pg_escape_string($product['id']).", ".pg_escape_string($product['price']).", ".pg_escape_string($product['price']).", '".pg_escape_string($new_checkout_uid)."', '".pg_escape_string("google:".$google_payment['last_event'])."', '".pg_escape_string($domain['referral_code'])."', '".pg_escape_string($data->purchaseCode)."' )";
		$create_checkout_ret = pg_query($pos_db,$create_checkout_sql);
		if(!$create_checkout_ret){
			error_log("Failed to create new checkout: ".pg_last_error($pos_db));
		}
		// Get the new checkout
		$get_checkout_sql = "SELECT * FROM checkouts WHERE transaction_id='".pg_escape_string($data->purchaseCode)."'";
		$get_checkout_ret = pg_query($pos_db,$get_checkout_sql);
		$new_checkout = pg_fetch_assoc($get_checkout_ret);
		
		// Create the VPN Plan
		$result = createNewVPNPlan($new_checkout,$pos_db,$wgm_db);
		if($result['status']){
			//$_SESSION['user_token']=$result['user_token'];
			error_log("New VPN Plan Created: googleplay");
		}
		else{
			error_log("FAILED to create VPN Plan: googleplay");
			$returnArray['message']="FAILED to create VPN Plan: googleplay";
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
			
		$returnArray['status'] = 'Success';
		$returnArray['message'] = 'Success';
		$returnArray['success'] = True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		exit();
		break;
		
	case "get_user":
		// Get user unique_id & total clients
		$result = array ('user_id' => $user['unique_id'], 'total_clients' => $user['total_clients'], 'role' => $user['role']);
		$returnArray['result'] = $result;
		$returnArray['status'] = 'Success';
		$returnArray['message'] = 'Success';
		$returnArray['success'] = True;
		//echo "<p>Getting User</p>";
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		break;
	
	case "get_plan":
		$get_plan_info_sql = "SELECT * FROM active_plans WHERE domain_id=".pg_escape_string($domain['id']);
		$get_plan_info_ret = pg_query($pos_db,$get_plan_info_sql);
		if(!$get_plan_info_ret){
			$returnArray['message']='Encountered an Error';
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$plan = pg_fetch_assoc($get_plan_info_ret);
		// We have the plan, let's get some information about the product.
		$get_product_sql = "SELECT * FROM products WHERE id=".pg_escape_string($plan['product_id']);
		$get_product_ret = pg_query($pos_db,$get_product_sql);
		if(!$get_product_ret){
			$returnArray['message']='Encountered an Error';
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$product = pg_fetch_assoc($get_product_ret);
		
		$result = array('product' => $product,
						'plan' => $plan);
		$returnArray['result']=$result;
		$returnArray['status']='Success';
		$returnArray['message']='Success';
		$returnArray['success']=True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
	
		break;
	
	case "get_clients":
		$get_clients_sql = "SELECT * FROM clients WHERE user_id=".pg_escape_string($user['id']);
		$get_clients_ret = pg_query($wgm_db,$get_clients_sql);
		if(!$get_clients_ret){
			$returnArray['message']='Encountered an Error';
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$clientsResult = array();
		while($client = pg_fetch_assoc($get_clients_ret)){
			array_push($clientsResult,array('unique_id' => $client['unique_id'], 'local_uid' => $client['local_uid'], 'pub_key' => $client['pub_key']));
		}
		$returnArray['result'] = $clientsResult;
		$returnArray['status']='Success';
		$returnArray['message']='Success';
		$returnArray['success']=True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		
		break;
		
	case "get_upgrades":
		$get_plan_sql = "SELECT * FROM active_plans WHERE domain_id=".pg_escape_string($domain['id']);
		$get_plan_ret = pg_query($pos_db,$get_plan_sql);
		$plan = pg_fetch_assoc($get_plan_ret);
	
		// New product selection
		$get_products_sql = "SELECT count(id) as count FROM products WHERE total_users > ".pg_escape_string($plan['total_users'])." OR total_clients_per_user > ".pg_escape_string($plan['total_clients_per_user']);
		$get_products_ret = pg_query($pos_db,$get_products_sql);
		if(!$get_products_ret){
			$returnArray['message']='Encountered an Error';
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$upgradeCount = pg_fetch_assoc($get_products_ret);
		$returnArray['result'] = array('upgrades_available' => $upgradeCount['count'], 'plans' => array());
		
		// if $upgrade_count > 0, find out what those plans were.
		if($upgradeCount['count'] > 0){
			$get_plans_sql = "SELECT * FROM products WHERE total_users > ".pg_escape_string($plan['total_users'])." OR total_clients_per_user > ".pg_escape_string($plan['total_clients_per_user']);
			$get_plans_ret = pg_query($pos_db,$get_plans_sql);
			while($plan = pg_fetch_assoc($get_plans_ret)){
				array_push($returnArray['result']['plans'],$plan);
			}
		}
		
		
		$returnArray['status']='Success';
		$returnArray['message']='Success';
		$returnArray['success']=True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
	
		break;
		
	case "delete_client":
		if(!isset($data->local_uid)){
			$returnArray['message']="Must provide local_uid";
			//http_response_code(400);
			echo json_encode($returnArray);
			error_log("user api: delete_client: Must provide local_uid");
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$get_client_config_sql = "SELECT * FROM clients WHERE local_uid='".pg_escape_string($data->local_uid)."'";
		//error_log("Looking for client with: ".$get_client_config_sql);
		$get_client_config_ret = pg_query($wgm_db,$get_client_config_sql);
		if(!$get_client_config_ret){
			$returnArray['message']="Error Encountered";
			error_log("user api: delete_client: Error Encountered");
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$get_client = pg_fetch_assoc($get_client_config_ret);
		// Does client exist?
		if(!isset($get_client['unique_id'])){
			$returnArray['message']="Client Does Not Exist";
			//error_log("client api: get_client_config: Client Does Not Exist");
			//http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		
		deleteClient($data->local_uid,$wgm_db,$PATH_TO_CLI);
				
		$returnArray['message']="Client deleted";
		$returnArray['status']="Success";
		$returnArray['success']=true;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		exit();
	
		break;
	case 'get_potential_locations':

		$locations_sql = "SELECT loc.id,loc.name,loc.unique_id FROM locations loc
        JOIN rel_domain_network domnet ON loc.network_id=domnet.network_id
WHERE domnet.domain_id=".pg_escape_string($user['domain_id']);
		$locations_ret = pg_query($wgm_db,$locations_sql);
		if(!$locations_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			//http_response_code(400);
			error_log("user api: get_potential_locations: DB Error: ".pg_last_error($wgm_db));
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
		}
		$result = array();
		while($location = pg_fetch_assoc($locations_ret)){
			array_push($result,array('name' => $location['name'], 'loc_uid' => $location['unique_id']));
		}
		$returnArray['result']=$result;
		$returnArray['status']='Success';
		$returnArray['message']='Success';
		$returnArray['success']=True;
		echo json_encode($returnArray);
		pg_close($wgm_db);
		pg_close($pos_db);
		exit();
		break;
		
	case "delete_user":
		/* Deleting a user we need to do the following.... 
			1) Check Role,
			2) For Primary Users, we'll clear the entire domain of users and clients before deleting the VPN plan.
		
			3) Delete all Clients.
			4) Delete all Users
			5) Delete all Domains
			6) Delete VPN Plan
		*/
		
		if($user['role'] == 'primary'){
			// This is a primary user, we need to get all clients associated with the domain and delete them, detaching them while we do so.
			$get_all_clients_sql = "SELECT * FROM clients WHERE domain_id=".pg_escape_string($domain['id']);
			$get_all_clients_ret = pg_query($wgm_db,$get_all_clients_sql);
			if(!$get_all_clients_ret){
				error_log("user api: delete_user: DB Error: ".pg_last_error($wgm_db));
				pg_close($wgm_db);
				pg_close($pos_db);
				exit();
			}
			while($client = pg_fetch_assoc($get_all_clients_ret)){
				deleteClient($client['local_uid'],$wgm_db,$PATH_TO_CLI);
			}
			
			// Delete all the users of the domain
			$delete_users_sql = "DELETE FROM users WHERE domain_id=".pg_escape_string($domain['id']);
			$delete_users_ret = pg_query($wgm_db,$delete_users_sql);
			
			// Detach the domain
			$delete_domain_rel_sql = "DELETE FROM rel_domain_network WHERE domain_id=".pg_escape_string($domain['id']);
			$delete_domain_rel_ret = pg_query($wgm_db, $delete_domain_rel_sql);
			
			// Delete the domain
			$delete_domain_sql = "DELETE FROM domains WHERE id=".pg_escape_string($domain['id']);
			$delete_domain_ret = pg_query($wgm_db,$delete_domain_sql);
			
			// Delete the VPN Plan
			$delete_vpn_plan_sql = "DELETE FROM active_plans WHERE domain_id=".pg_escape_string($domain['id']);
			$delete_vpn_plan_ret = pg_query($pos_db,$delete_vpn_plan_sql);
			
			// We're done, return success
			$returnArray['status']='Success';
			$returnArray['message']='Success';
			$returnArray['success']=True;
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
			
		}
		else{
			// Single user deletion
			// First get clients and delete them properly
			$get_user_clients_sql = "SELECT * FROM clients WHERE user_id=".pg_escape_string($user['id']);
			$get_user_clients_ret = pg_query($wgm_db,$get_user_clients_sql);
			if(!$get_user_clients_ret){
				error_log("user api: delete_user: DB Error: ".pg_last_error($wgm_db));
				pg_close($wgm_db);
				pg_close($pos_db);
				exit();
			}
			while($client = pg_fetch_assoc($get_user_clients_ret)){
				deleteClient($client['local_uid'],$wgm_db);
			}
			
			// Delete user
			$delete_user_sql = "DELETE FROM users WHERE id=".pg_escape_string($user['id']);
			$delete_user_ret = pg_query($wgm_db,$delete_user_sql);
			
			// Leave the domain alone, this user isn't a primary member. 
			
			// We're done!
			$returnArray['status']='Success';
			$returnArray['message']='Success';
			$returnArray['success']=True;
			echo json_encode($returnArray);
			pg_close($wgm_db);
			pg_close($pos_db);
			exit();
			
		}
	
		break;
}




// CLOSE DB CONNECTIONS
//pg_close($wgm_db);
//pg_close($pos_db);
// This now happens above.

?>

