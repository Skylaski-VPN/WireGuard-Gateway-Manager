<html>
<head>
<title>WireGuard Gateway Manager - Setup IaaS Provider</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Setup IaaS Provider</h1>

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
			$output = shell_exec("./cli/iaas.py iaas_provider setup_zones ".$_POST['provider_id']);
			echo "<pre>".$output."</pre>";
		}
		else{
			echo "<p>Missing Parameters</p>";
		}
	}
	elseif($_POST['action'] == "update_images"){	// Update Provider's Images
		if($_POST['provider_id'] && $_POST['provider_type']){
			echo "<p>Updating Provider Images</p>";
			$output = shell_exec("./cli/iaas.py iaas_provider setup_images ".$_POST['provider_id']);
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
$sql = "SELECT * FROM iaas_auth_configs";
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
<th>Auth Provider</th><th>Type</th><th>Zones</th><th>Images</th><th>Update Zones</th><th>Update Images</th>
</tr>
</thead>

<?php

while ($row = pg_fetch_assoc($ret)){
	echo "<tr>";
	// get provider name
	echo "<td>";
	$provider_array = array();
	$get_provider_arr_sql = "SELECT * FROM iaas_providers WHERE id = ".pg_escape_string($row['provider']);
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
	$get_provider_type_sql = "SELECT * FROM iaas_provider_types WHERE id = ".pg_escape_string($provider_arr['type'])."";
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
	$get_zone_count_sql = "SELECT count(id) as zones FROM iaas_zones WHERE provider = ".pg_escape_string($row['provider']);
	$get_zone_count_ret = pg_query($db, $get_zone_count_sql);
	$total_zones = pg_fetch_assoc($get_zone_count_ret);
	if($total_zones){
		echo "<p>".$total_zones['zones']."</p>";
	}
	else{
		echo "<p>0</p>";
	}
	echo "</td>";
	
	// get provider images
	echo "<td>"; 
	$total_images = 0;
	$get_image_count_sql = "SELECT count(id) as images FROM iaas_vm_images WHERE provider = ".pg_escape_string($row['provider']);
	$get_image_count_ret = pg_query($db, $get_image_count_sql);
	$total_images = pg_fetch_assoc($get_image_count_ret);
	if($total_images){
		echo "<p>".$total_images['images']."</p>";
	}
	else{
		echo "<p>0</p>";
	}
	echo "</td>";
	
	//Update Zones Form
	echo "<td>"; 
	echo "<form method=\"post\" action=\"setup_iaas.php\" onsubmit=\"return confirm('Are you sure you want to Update this Zone\'s Providers?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update_zones\">";
	echo "<input type=\"hidden\" name=\"provider_id\" value=\"".$row['provider']."\">";
	echo "<input type=\"hidden\" name=\"provider_type\" value=\"".$provider_arr['type']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Update Zones\" >";
	echo "</form>";
	echo "</td>";
	
	//Update Images Form
	echo "<td>"; 
	echo "<form method=\"post\" action=\"setup_iaas.php\" onsubmit=\"return confirm('Are you sure you want to Update this Zone\'s Images?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update_images\">";
	echo "<input type=\"hidden\" name=\"provider_id\" value=\"".$row['provider']."\">";
	echo "<input type=\"hidden\" name=\"provider_type\" value=\"".$provider_arr['type']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Update Images\" >";
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
