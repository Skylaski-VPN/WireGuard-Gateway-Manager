<html>
<head>
<title>WireGuard Gateway Manager - Setup DNS Provider</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Setup DNS Provider</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';


// attempt to connect to DB
$db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
if(!$db) {
	echo "Error : Unable to open database\n";
} else {
	echo "<p>Opened database successfully</p>";
}

// Are we receiving data?
if($_SERVER['REQUEST_METHOD'] == "POST"){
	if(empty($_POST['action'])){
		echo "<p>action is required</p>";
	}
	elseif($_POST['action'] == "update_zones"){	// Update Provider's Zones
		if($_POST['provider_id'] && $_POST['provider_type']){
			echo "<p>Updating Provider Zones</p>";
			$output = shell_exec("./cli/iaas.py dns_provider setup_zones ".$_POST['provider_id']);
			echo "<pre>".$output."</pre>";
		}
		else{
			echo "<p>Missing Parameters</p>";
		}
	}
	elseif($_POST['action'] == "update_records"){	// Update Provider's Records
		if($_POST['provider_id'] && $_POST['provider_type']){
			echo "<p>Updating Provider Images</p>";
			$output = shell_exec("./cli/iaas.py dns_provider setup_records ".$_POST['provider_id']);
			echo "<pre>".$output."</pre>";
		}
		else{
			echo "<p>Missing Parameters</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}

?>




<h3>Current Configured Providers</h3>
<hr>

<?php // get list of current providers that have Auth Configs
$sql = "SELECT * FROM dns_auth_configs";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<table> <!-- Show table data in an html table -->
<thead>
<tr>
<th>DNS Provider</th><th>Type</th><th>Zones</th><th>Records</th><th>Update Zones</th><th>Update Records</th>
</tr>
</thead>

<?php

while ($row = pg_fetch_assoc($ret)){
	echo "<tr>";
	// get provider name
	echo "<td>";
	$provider_array = array();
	$get_provider_arr_sql = "SELECT * FROM dns_providers WHERE id = ".pg_escape_string($row['provider']);
	$get_provider_arr_ret = pg_query($db, $get_provider_arr_sql);
	if($get_provider_arr_ret){
		$provider_arr = pg_fetch_assoc($get_provider_arr_ret);
		echo "<p>".$provider_arr['name']."</p>";
	}
	else{
		echo "<p>Couldn't find provider</p>";
	}
	echo "</td>";
	
	// get provider type
	echo "<td>"; 
	$provider_type_array = array();
	$get_provider_type_sql = "SELECT * FROM dns_provider_types WHERE id = ".pg_escape_string($provider_arr['type'])."";
	$get_provider_type_ret = pg_query($db, $get_provider_type_sql);
	if($get_provider_type_ret){
		$provider_type_array = pg_fetch_assoc($get_provider_type_ret);
		echo "<p>".$provider_type_array['name']."</p>";
	}
	else{
		echo "<p>Couldn't find provider type</p>";
	}	
	echo "</td>";
	
	
	// get provider zones
	echo "<td>"; 
	$total_zones = 0;
	$get_zone_count_sql = "SELECT count(id) as zones FROM dns_zones WHERE provider = ".pg_escape_string($row['provider']);
	$get_zone_count_ret = pg_query($db, $get_zone_count_sql);
	$total_zones = pg_fetch_assoc($get_zone_count_ret);
	if($total_zones){
		echo "<p>".$total_zones['zones']."</p>";
	}
	else{
		echo "<p>0</p>";
	}
	echo "</td>";
	
	// get provider records
	$total_records = 0;
	$get_zone_ids_sql = "SELECT id FROM dns_zones WHERE provider=".pg_escape_string($row['provider']);
	$get_zone_ids_ret = pg_query($db,$get_zone_ids_sql);
	while($zone = pg_fetch_assoc($get_zone_ids_ret)){
		$get_record_count_sql = "SELECT count(id) as count FROM dns_zone_records WHERE zone=".pg_escape_string($zone['id']);
		$get_record_count_ret = pg_query($db,$get_record_count_sql);
		if($get_record_count_ret){
			while($record_count = pg_fetch_assoc($get_record_count_ret)){
				$total_records += $record_count['count'];
			}
		}
		else{
			echo "<p>Failed to get DNS Records: ".pg_last_error($db)."</p>";
		}
	}
	echo "<td>".$total_records."</td>";
	
	//Update Zones Form
	echo "<td>"; 
	echo "<form method=\"post\" action=\"setup_dns.php\" onsubmit=\"return confirm('Are you sure you want to Update this Provider\'s Zones?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update_zones\">";
	echo "<input type=\"hidden\" name=\"provider_id\" value=\"".$row['provider']."\">";
	echo "<input type=\"hidden\" name=\"provider_type\" value=\"".$provider_arr['type']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Update Zones\" >";
	echo "</form>";
	echo "</td>";
	
	//Update Records Form
	echo "<td>"; 
	echo "<form method=\"post\" action=\"setup_dns.php\" onsubmit=\"return confirm('Are you sure you want to Update this Provider\'s Zones?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update_records\">";
	echo "<input type=\"hidden\" name=\"provider_id\" value=\"".$row['provider']."\">";
	echo "<input type=\"hidden\" name=\"provider_type\" value=\"".$provider_arr['type']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Update Records\" >";
	echo "</form>";
	echo "</td>";
	
	echo "</tr>";
}

?>

<!-- Don't need a foot yet
<tfoot>
<tr><td>1</td><td>2</td><td>3</td></tr>
</tfoot>
-->
</table>



</body>
</html>

<?php
pg_close($db)
?>
