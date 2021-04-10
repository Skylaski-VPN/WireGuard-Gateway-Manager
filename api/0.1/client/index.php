<?php
// api/0.1/client API
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
		echo json_encode($returnArray);
		//http_response_code(400);
		pg_close($wgm_db);
		exit();
	}
}
else{
	$returnArray['message']='Improper Request Headers';
	echo json_encode($returnArray);
	//http_response_code(400);
	pg_close($wgm_db);
	exit();
}

// Now we have a token to check. 
require '../include/config.php';
require '../include/include.php';
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
	case "get_client_config":
		//Check local_uid
		if(!isset($data->local_uid)){
			$returnArray['message']="Must provide local_uid";
			//http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		$get_client_config_sql = "SELECT unique_id,client_config,token FROM clients WHERE local_uid='".pg_escape_string($data->local_uid)."'";
		$get_client_config_ret = pg_query($wgm_db,$get_client_config_sql);
		if(!$get_client_config_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
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
			exit();
		}
		if(!isset($get_client['client_config'])){
			// Client exists, but no config
			$returnArray['message']="Client has no config";
			$returnArray['result'] = array('client_uid' => $get_client['unique_id'], 'client_token' => $get_client['token']);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		// Get user unique_id & config
		$result = array ('client_uid' => $get_client['unique_id'], 'config' => $get_client['client_config'], 'client_token' => $get_client['token']);
		$returnArray['result'] = $result;
		$returnArray['status'] = 'Success';
		$returnArray['message'] = 'Success';
		$returnArray['success'] = True;
		//echo "<p>Getting User</p>";
		echo json_encode($returnArray);
		pg_close($wgm_db);
		exit();
		break;
	
	case "create_client":
		// Check for mandatory input
		if(!isset($data->local_uid) || !isset($data->name) || !isset($data->pub_key) || !isset($data->dns_type)){
			$returnArray['message']="Missing Parameters";
			error_log("create_client: Missing Parameters");
			//http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		// Make sure we're not creating more clients than the user is allowed.
		$max_allowed_clients_sql = "SELECT total_clients FROM users WHERE id=".pg_escape_string($user['id']);
		$max_allowed_clients_ret = pg_query($wgm_db,$max_allowed_clients_sql);
		$cur_configured_clients_sql = "SELECT count(id) as client_count FROM clients WHERE user_id=".pg_escape_string($user['id']);
		$cur_configured_clients_ret = pg_query($wgm_db,$cur_configured_clients_sql);
		if((($cur_clients = pg_fetch_assoc($cur_configured_clients_ret))['client_count'] +1) > ($max_allowed = pg_fetch_assoc($max_allowed_clients_ret)['total_clients'])){
			$results = array( 'max_clients' => True);
			$returnArray['result']=$results;
			$returnArray['message']="Maximum Clients Already Created";
			error_log("create_client: Maximum Clients Already Created");
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
		//max clients hasn't been reached, we're good to create this client.
		$new_client_uid = generateUniqueID(64,'clients',$wgm_db);
		$new_client_api_token = createAPIToken('clients',$wgm_db);
		$insert_client_sql = "INSERT INTO clients (client_name,unique_id,pub_key,user_id,domain_id,dns_type,local_uid,token) VALUES ('".pg_escape_string($data->name)."', '".pg_escape_string($new_client_uid)."', '".pg_escape_string($data->pub_key)."', ".pg_escape_string($user['id']).", ".pg_escape_string($user['domain_id']).", '".pg_escape_string($data->dns_type)."','".pg_escape_string($data->local_uid)."', '".pg_escape_string($new_client_api_token)."')";
		$insert_client_ret = pg_query($wgm_db,$insert_client_sql);
		if($insert_client_ret){
			$results=array('client_uid' => $new_client_uid, 'client_token' => $new_client_api_token, 'max_clients' => False);
			$returnArray['result'] = $results;
			$returnArray['status'] = 'Success';
			$returnArray['message'] = 'Success';
			$returnArray['success'] = True;
			pg_close($wgm_db);
			echo json_encode($returnArray);
			exit();
		}
		else{
			$returnArray['message']="Failed to create client".pg_last_error($wgm_db);
			http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
	
		break;
		
	case "get_locations":
		// Check for proper input
		if(!isset($data->client_uid) || !isset($data->client_token)){
			$returnArray['message']="Missing Parameters";
			http_response_code(400);
			error_log("client api: get_locations: Missing parameters");
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		// Now check to make sure this is a legit client
		$get_client_sql = "SELECT * FROM clients WHERE unique_id='".pg_escape_string($data->client_uid)."' AND token='".pg_escape_string($data->client_token)."'";
		$get_client_ret = pg_query($wgm_db,$get_client_sql);
		if(!$get_client_ret){
			$returnArray['message']="Error Encountered";
			error_log("client api: get_locations: Couldn't validate client");
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		$client_row = pg_fetch_assoc($get_client_ret);
		if(!isset($client_row['id'])){
			$returnArray['message']="Client Does Not Exist";
			error_log("client api: get_locations: client doesn't exist");
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		
		// We have proper input and the client exists, get a list of locations and feed back to the user. 
		$locations_sql = "SELECT loc.id,loc.name,loc.unique_id FROM locations loc
        JOIN rel_domain_network domnet ON loc.network_id=domnet.network_id
WHERE domnet.domain_id=".pg_escape_string($user['domain_id']);
		$locations_ret = pg_query($wgm_db,$locations_sql);
		if(!$locations_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
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
		exit();
		break;
		
		
	case "attach_location":
		
		if(!isset($data->client_uid) || !isset($data->client_token) || !isset($data->loc_uid)){
			$returnArray['message']="Missing Parameters";
			//http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
		// Now check to make sure this is a legit client
		$get_client_sql = "SELECT * FROM clients WHERE unique_id='".pg_escape_string($data->client_uid)."' AND token='".pg_escape_string($data->client_token)."'";
		$get_client_ret = pg_query($wgm_db,$get_client_sql);
		if(!$get_client_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		$client_row = pg_fetch_assoc($get_client_ret);
		if(!isset($client_row['id'])){
			$returnArray['message']="Client Does Not Exist";
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		
		// Now check to make sure this is a legit location
		$get_location_sql = "SELECT * FROM locations WHERE unique_id='".pg_escape_string($data->loc_uid)."'";
		$get_location_ret = pg_query($wgm_db,$get_location_sql);
		if(!$get_location_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		$location_row = pg_fetch_assoc($get_location_ret);
		if(!isset($location_row['id'])){
			$returnArray['message']="Location Does Not Exist";
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		
		// Everything should be good. Let's build the relationship in the DB and setup the server + client.
		# Get IP Leases
		$lease = get_client_lease($client_row['id'],$location_row['id'],$wgm_db);
		# Record IPv4
		$insert_ipv4_sql = "INSERT INTO ipv4_client_leases (network_id,address,domain_id,loc_id,client_id,gw_id) VALUES ('".pg_escape_string($lease['ipv4_lease']['network_id'])."','".pg_escape_string($lease['ipv4_lease']['address'])."','".pg_escape_string($lease['ipv4_lease']['domain_id'])."','".pg_escape_string($lease['ipv4_lease']['loc_id'])."','".pg_escape_string($lease['ipv4_lease']['client_id'])."','".pg_escape_string($lease['ipv4_lease']['gw_id'])."')";
		$insert_ipv4_ret = pg_query($wgm_db,$insert_ipv4_sql);
		if($insert_ipv4_ret){
			//echo "<p>Inserted IPv4 Lease</p>";
			# Record IPv6
			$insert_ipv6_sql = "INSERT INTO ipv6_client_leases (network_id,address,domain_id,client_id,loc_id,gw_id) VALUES ('".pg_escape_string($lease['ipv6_lease']['network_id'])."','".pg_escape_string($lease['ipv6_lease']['address'])."','".pg_escape_string($lease['ipv6_lease']['domain_id'])."','".pg_escape_string($lease['ipv6_lease']['client_id'])."','".pg_escape_string($lease['ipv6_lease']['loc_id'])."','".pg_escape_string($lease['ipv6_lease']['gw_id'])."')";
			$insert_ipv6_ret = pg_query($wgm_db,$insert_ipv6_sql);
			if($insert_ipv6_ret){
				//echo "<p>Inserted IPv6 Lease</p>";
				# Updating Client Record
				$update_client_sql = "UPDATE clients SET ipv4_addr='".pg_escape_string($lease['ipv4_lease']['address'])."', ipv6_addr='".pg_escape_string($lease['ipv6_lease']['address'])."', gw_id='".pg_escape_string($lease['gw']['id'])."', v4_mask='".pg_escape_string($lease['ipv4_lease']['v4_mask'])."', v6_mask='".pg_escape_string($lease['ipv6_lease']['v6_mask'])."', dns_ip='".pg_escape_string($lease['dns_ip'])."', gw_key='".pg_escape_string($lease['gw']['gw_key'])."', gw_addr='".pg_escape_string($lease['gw']['gw_addr'])."', gw_port='".pg_escape_string($lease['gw']['gw_port'])."' WHERE id=".pg_escape_string($client_row['id']);
				$update_client_ret = pg_query($wgm_db,$update_client_sql);
				if($update_client_ret){
					//echo "<p>Updated Client Record</p>";
					# Attaching Client via rel table
					$attach_client_sql = "INSERT INTO rel_client_loc (client_id,loc_id) VALUES ('".pg_escape_string($client_row['id'])."','".pg_escape_string($location_row['id'])."')";
					$attach_client_ret = pg_query($wgm_db,$attach_client_sql);
					if($attach_client_ret){
						
						# Everything is good, add peer to GW
						$output = shell_exec($PATH_TO_CLI."wgmcontroller.py attach_client ".$client_row['id']." 2>/dev/null");
						$returnArray['result']=array('message' => "Attached Client");
						$returnArray['status']='Success';
						$returnArray['message']='Success';
						$returnArray['success']=True;
						echo json_encode($returnArray);
						pg_close($wgm_db);
						exit();
					}
					else{
						$returnArray['message']="Failed to setup relationship on backend between client and location";
						echo json_encode($returnArray);
						pg_close($wgm_db);
						exit();
					}
				}
				else{
					$returnArray['message']="Failed to update client record on backend";
					echo json_encode($returnArray);
					pg_close($wgm_db);
					exit();
				}
			}
			else{
				$returnArray['message']="Failed to insert IPv6 Lease";
				echo json_encode($returnArray);
				pg_close($wgm_db);
				exit();
			}
		}
		else{
			$returnArray['message']="Failed to setup IPv4 Lease";
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
	
		break;
		
	case "detach_location":
		// Check for proper input
		if(!isset($data->client_uid) || !isset($data->client_token)){
			$returnArray['message']="Missing Parameters";
			http_response_code(400);
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
		// Now check to make sure this is a legit client
		$get_client_sql = "SELECT * FROM clients WHERE unique_id='".pg_escape_string($data->client_uid)."' AND token='".pg_escape_string($data->client_token)."'";
		//error_log("client api: detach_location: Finding client with: ".$get_client_sql);
		$get_client_ret = pg_query($wgm_db,$get_client_sql);
		if(!$get_client_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		$client_row = pg_fetch_assoc($get_client_ret);
		if(!isset($client_row['id'])){
			$returnArray['message']="Client Does Not Exist";
			echo json_encode($returnArray);
			http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		
		// Now find the location the client is currently attached to
		$delete_ipv4_lease_sql = "DELETE FROM ipv4_client_leases WHERE client_id=".pg_escape_string($client_row['id']);
		$delete_ipv4_lease_ret = pg_query($wgm_db,$delete_ipv4_lease_sql);
		if($delete_ipv4_lease_ret){
			$delete_ipv6_lease_sql = "DELETE FROM ipv6_client_leases WHERE client_id=".pg_escape_string($client_row['id']);
			$delete_ipv6_lease_ret = pg_query($wgm_db,$delete_ipv6_lease_sql);
			if($delete_ipv6_lease_ret){
				$detach_client_sql = "DELETE FROM rel_client_loc WHERE client_id=".pg_escape_string($client_row['id']);
				$detach_client_ret = pg_query($wgm_db,$detach_client_sql);
				if($detach_client_ret){
					
					$output = shell_exec($PATH_TO_CLI."wgmcontroller.py detach_client ".$client_row['id']);
					
					$clear_client_config_sql = "UPDATE clients set ipv4_addr=NULL, ipv6_addr=NULL, gw_id=NULL, v4_mask=NULL, v6_mask=NULL, dns_ip=NULL, gw_key=NULL, gw_addr=NULL, gw_port=NULL, client_config=NULL WHERE id=".pg_escape_string($client_row['id']);
					$clear_client_config_ret = pg_query($wgm_db,$clear_client_config_sql);
					
					$returnArray['message']="Detached Client";
					$returnArray['status']="Success";
					$returnArray['success']=true;
					$returnArray['result'] = $output;
					echo json_encode($returnArray);
					pg_close($wgm_db);
					
					exit();
						
				}
				else{
					error_log("Failed to clear client_loc relationship: ".pg_last_error($wgm_db));
				}
			}
			else{
				error_log("Failed to clear IPv6 lease: ".pg_last_error($wgm_db));
			}
		}
		else{
			error_log("Failed to clear IPv4 lease: ".pg_last_error($wgm_db));
		}
	
		break;
		
	case "get_dns":
		// Check for proper input
		if(!isset($data->client_uid) || !isset($data->client_token) || !isset($data->dns_type)){
			$returnArray['message']="Missing Parameters";
			//http_response_code(400);
			error_log("get_dns: Missing Parameters");
			echo json_encode($returnArray);
			pg_close($wgm_db);
			exit();
		}
		
		// Now check to make sure this is a legit client
		$get_client_sql = "SELECT * FROM clients WHERE unique_id='".pg_escape_string($data->client_uid)."' AND token='".pg_escape_string($data->client_token)."'";
		//error_log("client api: detach_location: Finding client with: ".$get_client_sql);
		$get_client_ret = pg_query($wgm_db,$get_client_sql);
		if(!$get_client_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			//http_response_code(400);
			error_log("get_dns: Error Encountered");
			pg_close($wgm_db);
			exit();
		}
		$client_row = pg_fetch_assoc($get_client_ret);
		if(!isset($client_row['id'])){
			$returnArray['message']="Client Does Not Exist";
			error_log("get_dns: Client Does Not Exist");
			echo json_encode($returnArray);
			//http_response_code(400);
			pg_close($wgm_db);
			exit();
		}
		
		$get_avail_dns_sql = "SELECT dns.type,dns.ipv4_addr FROM dns_servers dns
												JOIN rel_network_dns netdns ON dns.id=netdns.dns_id
												JOIN rel_domain_network domnet ON netdns.network_id=domnet.network_id
												WHERE domnet.domain_id=".pg_escape_string($domain['id']);
		$get_avail_dns_ret = pg_query($wgm_db,$get_avail_dns_sql);
		if(!$get_avail_dns_ret){
			$returnArray['message']="Error Encountered";
			echo json_encode($returnArray);
			error_log("get_dns: Error encountered getting DNS: ".pg_last_error($wgm_db));
			pg_close($wgm_db);
			exit();
		}
		while($dns_row = pg_fetch_assoc($get_avail_dns_ret)){
			if($dns_row['type'] == $data->dns_type){
				// success, we found a matching DNS server for this client
				
				// First let's update the client record with the new preferred DNS Type
				$update_client_dns_sql = "UPDATE clients SET dns_type=".pg_escape_string($data->dns_type)." WHERE WHERE unique_id='".pg_escape_string($data->client_uid)."' AND token='".pg_escape_string($data->client_token)."'";
				$update_client_dns_ret = pg_query($wgm_db,$update_client_dns_sql);
				if(!$update_client_dns_ret){
					error_log("get_dns: Failed to update client with new DNS type");
				}
				$result = array("dns_server" => $dns_row['ipv4_addr']);
				$returnArray['result'] = $result;
				$returnArray['message']="Found DNS Server";
				$returnArray['status']="Success";
				$returnArray['success']=True;
				echo json_encode($returnArray);
				pg_close($wgm_db);
				exit();
			}			
		}
		$returnArray['message']="No DNS Server Found";
		echo json_encode($returnArray);
		error_log("get_dns: No DNS Server Found");
		pg_close($wgm_db);
		exit();
		
		break;
}


pg_close($wgm_db);

?>

