<?php

$TOTAL_USERS_PER_DOMAIN=100;
$TOTAL_CLIENTS_PER_USER=10;

// Increment IP. We use this for IPv6
function incrementIp($ip, $increment)
{
  $addr = inet_pton ( $ip ); 

  for ( $i = strlen ( $addr ) - 1; $increment > 0 && $i >= 0; --$i )
  {
    $val = ord($addr[$i]) + $increment;
    $increment = $val / 256;
    $addr[$i] = chr($val % 256);
  }

  return inet_ntop ( $addr );
}

// Check for valid domain names
function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}

// Return a potential IPv4 address for a client given IP/CIDR notation netmask
function getClientIPv4($ipcidr){
	if(preg_match('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})/',$ipcidr,$matches)){
		$ipv4_netid = $matches[1];
		$ipv4_subnet = $matches[2];
		$ipv4_netmask = long2ip(-1 << (32 - (int)$ipv4_subnet));	// Find regular netmask
		$ipv4_wcmask = long2ip( ~ip2long($ipv4_netmask) );	// create wildcard mask
		$ipv4_bcast = long2ip( ip2long($ipv4_netid) | ip2long($ipv4_wcmask) );	// find broadcast address
		$long_ipv4 = ip2long($ipv4_netid);
		$long_gateway = $long_ipv4+=1;	// derive gateway address
		$ipv4_gateway = long2ip($long_gateway);
		$rand_ip = rand(ip2long($ipv4_gateway) + 1, ip2long($ipv4_bcast) - 1); // Generate potential client ip
		$rand_ipv4 = long2ip($rand_ip);
		return $rand_ipv4;
	}
	else{
		return false;
	}
}

// Return a potential IPv4 address for a gateway given IP/CIDR notation netmask
function getGatewayIPv4($ipcidr){
	if(preg_match('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})/',$ipcidr,$matches)){
		$ipv4_netid = $matches[1];
		$ipv4_subnet = $matches[2];
		$ipv4_netmask = long2ip(-1 << (32 - (int)$ipv4_subnet));	// Find regular netmask
		$ipv4_wcmask = long2ip( ~ip2long($ipv4_netmask) );	// create wildcard mask
		$ipv4_bcast = long2ip( ip2long($ipv4_netid) | ip2long($ipv4_wcmask) );	// find broadcast address
		$long_ipv4 = ip2long($ipv4_netid);
		$long_gateway = $long_ipv4+=1;	// derive gateway address
		$ipv4_gateway = long2ip($long_gateway);
		return $ipv4_gateway;
	}
	else{
		return false;
	}
}


// Take ip/cidr notation of IPv6, validate it, add 1 for the gateway. 
function getGatewayIPv6($ipcidr){
	//first look for the /XXX mask
	list($ip,$cidr) = explode('/',$ipcidr);
	//valid ipv6?
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
		$ipv6_gw = incrementIp($ip,1);
		return $ipv6_gw;
	}
	else{
		return false;
	}
}

// Return a potential client IP address, 1 is reserved for the gateway
function getClientIPv6($ipcidr){
	//first look for the /XXX mask
	list($ip,$cidr) = explode('/',$ipcidr);
	//valid ipv6?
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
		$random_increment = mt_rand(2, 1000000000); //1 billion should be enough
		$ipv6_ip = incrementIp($ip,$random_increment);
		return $ipv6_ip;
	}
	else{
		return false;
	}
}

function SSHKeypasses($value)
{
    $key_parts = explode(' ', $value, 3);
    if (count($key_parts) < 2) {
        return false;
    }
    if (count($key_parts) > 3) {
        return false;
    }
    $algorithm = $key_parts[0];
    $key = $key_parts[1];
    if (!in_array($algorithm, array('ssh-rsa', 'ssh-dss'))) {
        return false;
    }
    $key_base64_decoded = base64_decode($key, true);
    if ($key_base64_decoded == FALSE) {
        return false;
    }
    $check = base64_decode(substr($key, 0, 16));
    $check = preg_replace("/[^\w\-]/", "", $check);
    if ((string)$check !== (string)$algorithm) {
        return false;
    }
    return true;
}

function get_client_lease($client_id,$loc_id,$db)
{
	# Get Domain ID and DNS Type
	$get_client_sql = "SELECT * FROM clients WHERE id=".pg_escape_string($client_id);
	$get_client_ret = pg_query($db,$get_client_sql);
	$get_client = pg_fetch_assoc($get_client_ret);
	$domain = $get_client['domain_id'];
	$dns_type = $get_client['dns_type'];
	#echo "<p>Domain: ".$domain."</p>";
	
	# Get the ID's of gateway's available at this location and count the number of current clients on each
	$get_gw_loc_sql = "SELECT * FROM rel_net_loc_gw WHERE loc_id=".pg_escape_string($loc_id);
	$get_gw_loc_ret = pg_query($get_gw_loc_sql);
	$gw_counts = array();
	while($gw_loc = pg_fetch_assoc($get_gw_loc_ret)){
		# Counting the IPv4 Leases should be enough because every client gets both an IPv4 AND IPv6 Address
		$get_gw_leases_sql = "SELECT count(id) as lease_count FROM ipv4_client_leases WHERE gw_id=".pg_escape_string($gw_loc['gw_id']);
		$get_gw_leases_ret = pg_query($db,$get_gw_leases_sql);
		$get_gw_leases = pg_fetch_assoc($get_gw_leases_ret);

		array_push($gw_counts,array( 'gw' => $gw_loc['gw_id'], 'lease_count' => $get_gw_leases['lease_count']));
	}
	#var_dump($gw_counts);
	# Sort counts
	$columns = array_column($gw_counts, 'lease_count');
	array_multisort($columns, SORT_ASC, $gw_counts);
	#var_dump($gw_counts);
	#echo "<p>Chosen GW: ".$gw_counts[0]['gw']."</p>";

	# Least utilized server is used. 
	$chosen_gw = $gw_counts[0]['gw'];
	$get_net_id_sql = "SELECT net_id FROM rel_net_loc_gw WHERE gw_id=".pg_escape_string($chosen_gw);
	$get_net_id_ret = pg_query($get_net_id_sql);
	$net_id = (pg_fetch_assoc($get_net_id_ret))['net_id'];

	# Get Network Information
	$get_networks_sql = "SELECT * FROM networks WHERE id=".pg_escape_string($net_id);
	$get_networks_ret = pg_query($db,$get_networks_sql);
	$network = pg_fetch_assoc($get_networks_ret);
	$ipv4_net = $network['ipv4_netmask'];
	preg_match('(\/\d{1,3})',$ipv4_net,$match);
	#echo "<p>".var_dump($match)."</p>";
	$ipv4_mask = $match[0];
	$ipv6_net = $network['ipv6_netmask'];
	preg_match('(\/\d{1,3})',$ipv6_net,$match);
	#echo "<p>".var_dump($match)."</p>";
	$ipv6_mask = $match[0];
	#echo "<p>IPv4 Net: ".$ipv4_net." IPv6 Net: ".$ipv6_net."</p>";

	# Get an IPv4 Address for this client, make sure it's available, repeat until a unique one is found.
	$ipv4_address = getClientIPv4($ipv4_net);
	$ipv4_free = False;
	while($ipv4_free == False){
		# lookup ip, if it exists, generate a new one.
		$lookup_ipv4_sql = "SELECT * FROM ipv4_client_leases WHERE gw_id=".pg_escape_string($chosen_gw);
		$lookup_ipv4_ret = pg_query($db,$lookup_ipv4_sql);
		$match = False;
		while($cur_ips = pg_fetch_assoc($lookup_ipv4_ret)){
			if($cur_ips['address'] == $ipv4_address){
				$match = True;
			}
		}
		if($match == True){
			# We found a match, generate a new IP
			$ipv4_address = getClientIPv4($ipv4_net);
		}
		else{ # No Matches Found, Address is free
			$ipv4_free = True;
		}
	}
	#echo "<p>Client IPv4: ".$ipv4_address."</p>";
	$ipv4_lease = array( 'network_id' => $net_id, 'address' => $ipv4_address, 'domain_id' => $domain, 'loc_id' => $loc_id, 'client_id' => $client_id, 'gw_id' => $chosen_gw, 'v4_mask' => $ipv4_mask );

	# Get an IPv6 Address for this client, make sure it's available, repeat until a unique one is found.
	$ipv6_address = getClientIPv6($ipv6_net);
	$ipv6_free = False;
	while($ipv6_free == False){
		# Lookup ip, if it exists, generate a new one
		$lookup_ipv6_sql = "SELECT * FROM ipv6_client_leases WHERE gw_id=".pg_escape_string($chosen_gw);
		$lookup_ipv6_ret = pg_query($db,$lookup_ipv6_sql);
		$match = False;
		while($cur_ips = pg_fetch_assoc($lookup_ipv6_ret)){
			if($cur_ips['address'] == $ipv6_address){
				$match = True;
			}
		}
		if($match == True){
			# We found a match, generate a new IP
			$ipv6_address = getClientIPv6($ipv6_net);
		}
		else{ # No matches found, address is free
			$ipv6_free = True;				
		}
	}
	# echo "<p>Client IPv6: ".$ipv6_address."</p>";
	$ipv6_lease = array( 'network_id' => $net_id, 'address' => $ipv6_address, 'domain_id' => $domain, 'loc_id' => $loc_id, 'client_id' => $client_id, 'gw_id' => $chosen_gw, 'v6_mask' => $ipv6_mask );
	
	# Get GW Information
	$get_gw_sql = "SELECT * FROM gw_servers WHERE id=".pg_escape_string($chosen_gw);
	$get_gw_ret = pg_query($db,$get_gw_sql);
	$get_gw = pg_fetch_assoc($get_gw_ret);
	$gw_information = array('id' => $chosen_gw, 'gw_key' => $get_gw['pub_key'], 'gw_port' => $get_gw['port'], 'gw_addr' => $get_gw['pub_ipv4_addr']);
	# Get DNS Information
	$get_dns_sql = "SELECT dns.ipv4_addr FROM dns_servers dns JOIN rel_network_dns netdns ON dns.id=netdns.dns_id JOIN rel_domain_network domnet ON netdns.network_id=domnet.network_id JOIN clients client ON domnet.domain_id=client.domain_id WHERE client.dns_type=".pg_escape_string($dns_type);
	#echo "<p>".$get_dns_sql."</p>";
	$get_dns_ret = pg_query($db,$get_dns_sql);
	$get_dns = pg_fetch_assoc($get_dns_ret);
	$dns_server_ip = $get_dns['ipv4_addr'];
	
	$lease = array ( 'ipv4_lease' => $ipv4_lease, 'ipv6_lease' => $ipv6_lease, 'gw' => $gw_information, 'dns_ip' => $dns_server_ip );
	
	#var_dump($lease);

	return $lease;
}

?>
