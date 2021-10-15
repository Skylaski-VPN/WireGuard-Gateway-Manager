<?php

require 'mailer.php';

// Generate unique_id's for table entries
function generateUniqueID($length,$table,$db) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_-=+';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    //get list of uniqueIDs from $table
    $get_ids_sql = "SELECT unique_id FROM ".$table;
    $get_ids_ret = pg_query($db,$get_ids_sql);
    $ids_array = array();
    while($id = pg_fetch_assoc($get_ids_ret)){
		array_push($ids_array,$id['unique_id']);
	}
	// While random string isn't unique, generate a new one
    while(in_array($randomString,$ids_array)){
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
	}
    
    
    return $randomString;
}

function generateNonce($length){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function createBasicVPNUser($email,$account,$auth_provider,$db){
	// Defaults for Basic User
	// Each User get's their own Domain
	// 1 User Domain
	// NetworkID = 5
	// 0 Clients to start
	$user_info = array( 'status' => False, 'id' => 0, 'domain_id' => 0 );
	
	// First create the Domain
	$domain_uid = generateUniqueID(64,'domains',$db);
	$new_domain_sql = "INSERT INTO domains (name,type,unique_id,total_users) VALUES ('".pg_escape_string($email)."','user','".pg_escape_string($domain_uid)."',1)";
	$new_domain_ret = pg_query($db,$new_domain_sql);
	if(!$new_domain_ret){
		//Creating domain failed, fail user creation
		echo "<p>Failed Domain Creation: ".pg_last_error($db)."</p>";
		return $user_info;
	}
	// Now get the domain id
	$get_domain_sql = "SELECT * FROM domains WHERE unique_id='".pg_escape_string($domain_uid)."'";
	$get_domain_ret = pg_query($db,$get_domain_sql);
	if(!$get_domain_ret){
		//Getting created domain failed, fail user creation
		echo "<p>Failed Getting Domain ID: ".pg_last_error($db)."</p>";
		return $user_info;
	}
	$domain = pg_fetch_assoc($get_domain_ret);
	$domain_id = $domain['id'];
	$user_info['domain_id']=$domain_id;
	//Domain created, create user
	$user_uid = generateUniqueID(64,'users',$db);
	$new_user_token = createAPIToken('users',$db);
	$new_user_sql = "INSERT INTO users (user_name,user_email,domain_id,unique_id,total_clients,auth_provider,provider_id,token) VALUES ('".pg_escape_string($email)."','".pg_escape_string($email)."',".pg_escape_string($domain_id).",'".pg_escape_string($user_uid)."',0,".$auth_provider.",'".pg_escape_string($account)."', '".pg_escape_string($new_user_token)."')";
	$new_user_ret = pg_query($db,$new_user_sql);
	if(!$new_user_ret){
		// Failed User Creation
		echo "<p>Failed Creating User: ".pg_last_error($db)."</p>";
		return $user_info;
	}
	//now get user id
	$get_user_sql = "SELECT * FROM users WHERE auth_provider=".pg_escape_string($auth_provider)." AND provider_id='".pg_escape_string($account)."'";
	$get_user_ret = pg_query($db,$get_user_sql);
	if(!$get_user_ret){
		echo "<p>Failed Creating User: ".pg_last_error($db)."</p>";
		return $user_info;
	}
	$get_user = pg_fetch_assoc($get_user_ret);
	$user_info['status']=True;
	$user_info['id']=$get_user['id'];
	
	return $user_info;
}

function createCheckoutCode($db){
	// Checkout codes max len 32
	$length = 32;
	$table = 'checkouts';
	
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    //get list of uniqueIDs from $table
    $get_ids_sql = "SELECT unique_id FROM ".$table;
    $get_ids_ret = pg_query($db,$get_ids_sql);
    $ids_array = array();
    while($id = pg_fetch_assoc($get_ids_ret)){
		array_push($ids_array,$id['unique_id']);
	}
	// While random string isn't unique, generate a new one
    while(in_array($randomString,$ids_array)){
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
	}
    
    
    return $randomString;
}

function createAPIToken($table,$db){
	// Checkout codes max len 32
	$length = 32;
	
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    //get list of uniqueIDs from $table
    $get_ids_sql = "SELECT token FROM ".$table;
    $get_ids_ret = pg_query($db,$get_ids_sql);
    $ids_array = array();
    while($id = pg_fetch_assoc($get_ids_ret)){
		array_push($ids_array,$id['token']);
	}
	// While random string isn't unique, generate a new one
    while(in_array($randomString,$ids_array)){
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
	}
    
    
    return $randomString;
}
function createAffiliateCode($db){
	// Affiliate codes max len 32
	$length = 32;
	$table='active_plans';
	
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    //get list of uniqueIDs from $table
    $get_ids_sql = "SELECT affiliate_code FROM ".$table;
    $get_ids_ret = pg_query($db,$get_ids_sql);
    $ids_array = array();
    while($id = pg_fetch_assoc($get_ids_ret)){
		array_push($ids_array,$id['affiliate_code']);
	}
	// While random string isn't unique, generate a new one
    while(in_array($randomString,$ids_array)){
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
	}
    
    
    return $randomString;
}

function createTeamCode($db){
	// Affiliate codes max len 32
	$length = 32;
	$table='active_plans';
	
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    //get list of uniqueIDs from $table
    $get_ids_sql = "SELECT team_code FROM ".$table;
    $get_ids_ret = pg_query($db,$get_ids_sql);
    $ids_array = array();
    while($id = pg_fetch_assoc($get_ids_ret)){
		array_push($ids_array,$id['team_code']);
	}
	// While random string isn't unique, generate a new one
    while(in_array($randomString,$ids_array)){
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
	}
    
    
    return $randomString;
}

function validateCoupon($product_id,$coupon_code,$db){
	$couponArray = array( 'modifier' => 0, 'id' => null );
	
	$find_coupon_sql = "SELECT * FROM coupons WHERE value='".pg_escape_string($coupon_code)."'";
	$find_coupon_ret = pg_query($db,$find_coupon_sql);
	$coupon = pg_fetch_assoc($find_coupon_ret);
	if(!isset($coupon['id'])){
		// Invalid coupon code
		return $couponArray;
	}
	else{
		// Valid coupon, does it support this product?
		$products_supported = explode(",",$coupon['products']);
		foreach($products_supported as $prd){
			if($prd == $product_id){
				// Product supported, return modifier
				$couponArray['modifier'] = $coupon['modifier'];
				$couponArray['id'] = $coupon['id'];
				return $couponArray;
			}
		}
	}
	
	
	return $couponArray;

}

function checkReferralCode($code,$pos_db,$wgm_db){
	// Default reward in Months
	$reward_months = 1;
	// get active plan with matching affiliate code
	$get_affiliate_plan_sql = "SELECT * FROM active_plans WHERE affiliate_code='".pg_escape_string($code)."'";
	$get_affiliate_plan_ret = pg_query($pos_db,$get_affiliate_plan_sql);
	$affiliate_plan = pg_fetch_assoc($get_affiliate_plan_ret);
	if(isset($affiliate_plan['id'])){
		// We found a matching affiliate, increase their expiration date by number of months 
		$dt = new DateTime($affiliate_plan['expiration']);
		$dt->modify("+".$reward_months." months");
		$dt_formatted = $dt->format("Y-m-d H:i:s P");
		// Now Update affiliate plan
		$update_plan_sql = "UPDATE active_plans SET expiration='".pg_escape_string($dt_formatted)."' WHERE id=".pg_escape_string($affiliate_plan['id']);
		$update_plan_ret = pg_query($pos_db,$update_plan_sql);
		
		// Find out the primary owner of the plan and send them an email
		$get_primary_user_sql = "SELECT user_email FROM users WHERE domain_id=".$affiliate_plan['domain_id']." AND role='primary'";
		$get_primary_user_ret = pg_query($wgm_db,$get_primary_user_sql);
		$primary_user = pg_fetch_assoc($get_primary_user_ret);
		if (filter_var($primary_user['user_email'], FILTER_VALIDATE_EMAIL)) {
			sendPlanEmail($primary_user['user_email'],"ReferralConfirmed");
		}
	}


}

function createNewVPNPlan($checkout,$pos_db,$wgm_db){
	$status = array('status' => False, 'user_token' => '');
	
	// Only for NEW VPN Plans do we check for a referral code and take action if it exists.
	checkReferralCode($checkout['referral_code'],$pos_db,$wgm_db);
	
	//get product information
	$get_product_sql = "SELECT * FROM products WHERE id=".pg_escape_string($checkout['product_id']);
	$get_product_ret = pg_query($pos_db,$get_product_sql);
	if(!$get_product_ret){
		error_log("Failed to get product: ".pg_last_error($pos_db));
	}
	$product = pg_fetch_assoc($get_product_ret);
	$product_total_users = $product['total_users'];
	
	//get user information
	$get_user_sql = "SELECT * FROM users WHERE id=".pg_escape_string($checkout['user_id']);
	$get_user_ret = pg_query($wgm_db,$get_user_sql);
	if(!$get_user_ret){
		error_log("Failed to get user: ".pg_last_error($wgm_db));
	}
	$user = pg_fetch_assoc($get_user_ret);
	
	// Add Domain to Network
	$insert_rel_domain_net_sql = "INSERT INTO rel_domain_network (domain_id,network_id) VALUES (".pg_escape_string($user['domain_id']).", ".$product['network_id'].")";
	$insert_rel_domain_net_ret = pg_query($wgm_db, $insert_rel_domain_net_sql);
	if(!$insert_rel_domain_net_ret){
		error_log("Failed to add domain to network: ".pg_last_error($wgm_db));
		return $status;
	}
	
	//Update Domain with user count
	$update_domain_sql = "UPDATE domains SET total_users=".pg_escape_string($product['total_users'])." WHERE id=".pg_escape_string($user['domain_id']);
	$update_domain_ret = pg_query($wgm_db,$update_domain_sql);
	if(!$update_domain_ret){
		error_log("Failed to update domain's total users: ".pg_last_error($wgm_db));
		return $status;
	}
	
	//Update user
	$update_user_sql = "UPDATE users SET role='primary', total_clients=".pg_escape_string($product['total_clients_per_user'])." WHERE id=".pg_escape_string($checkout['user_id']);
	$update_user_ret = pg_query($wgm_db,$update_user_sql);
	if(!$update_user_ret){
		error_log("Failed to update user: ".pg_last_error($wgm_db));
		return $status;
	}
	
	//Create VPN Plan
	if(isset($checkout['coupon_id'])){
		$coupon_id=$checkout['coupon_id'];
	}
	else{
		$coupon_id="NULL";
	}
	if(isset($checkout['discounts'])){
		$discounts = $checkout['discounts'];
	}
	else{
		$discounts="NULL";
	}
	if(isset($checkout['fees'])){
		$fees = $checkout['fees'];
	}
	else{
		$fees="NULL";
	}
	$dt = new DateTime();
	$dt = $dt->modify("+".$product['periods']." ".$product['period_unit']."s");
	$dt_formatted = $dt->format("Y-m-d H:i:s P");
	$new_affiliate_code = createAffiliateCode($pos_db);
	
	# If this is a team plan, we create the team code
	if($product_total_users > 1){
		$new_team_code = createTeamCode($pos_db);
	}
	else{
		$new_team_code = NULL;
	}
	
	$insert_active_plan_sql = "INSERT INTO active_plans (team_code,domain_id,checkout_id,product_id,coupon_id,total,subtotal,discounts,fees,total_users,total_clients_per_user,expiration,referral_code,affiliate_code) VALUES ('".pg_escape_string($new_team_code)."', ".pg_escape_string($user['domain_id']).", ".pg_escape_string($checkout['id']).", ".pg_escape_string($product['id']).", ".pg_escape_string($coupon_id).", ".pg_escape_string($checkout['total']).", ".pg_escape_string($checkout['subtotal']).", ".pg_escape_string($discounts).", ".pg_escape_string($fees).", ".pg_escape_string($product['total_users']).", ".pg_escape_string($product['total_clients_per_user']).", '".pg_escape_string($dt_formatted)."', '".pg_escape_string($checkout['referral_code'])."', '".pg_escape_string($new_affiliate_code)."')";
	$insert_active_plan_ret = pg_query($pos_db,$insert_active_plan_sql);
	if(!$insert_active_plan_ret){
		error_log("Failed to insert active VPN Plan: ".pg_last_error($pos_db));
		return $status;
	}
	
	// Send email
	if (filter_var($user['user_email'], FILTER_VALIDATE_EMAIL)) {
	  sendPlanEmail($user['user_email'],"NewPlan");
	}
	
	$status['status']=True;
	return $status;
}

