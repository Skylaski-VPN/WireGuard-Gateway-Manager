<html>
<head>
<title>WireGuard Gateway Manager - Locations</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Locations</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_loc_name_val="Location Name";
$default_geo_name_val="Geo Name";
$default_loc_addr_val="-";
//
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
	elseif($_POST['action'] == "add"){	// Add a Location
		if($_POST['loc_name'] && $_POST['geo_name'] && $_POST['loc_addr'] && $_POST['type'] && POST['network_id']){
			if($_POST['loc_name'] != $default_loc_name_val && $_POST['geo_name'] != $default_geo_name_val){
				echo "<p>Adding Location</p>";
				$new_unique_id = generateUniqueID(64,'locations',$db);
				$insert_loc_sql = "INSERT INTO locations (name,geo_name,address,type,network_id,unique_id) VALUES ('".pg_escape_string($_POST['loc_name'])."','".pg_escape_string($_POST['geo_name'])."','".pg_escape_string($_POST['loc_addr'])."','".pg_escape_string($_POST['type'])."','".pg_escape_string($_POST['network_id'])."','".pg_escape_string($new_unique_id)."')";
				$insert_loc_ret = pg_query($db,$insert_loc_sql);
				if($insert_loc_ret){
					echo "<p>Added Location</p>";
				}
				else{
					echo "<p>Failed to Add Location: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Default values not allowed.</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update a Location
		if($_POST['loc_id'] && $_POST['loc_name'] && $_POST['geo_name'] && $_POST['loc_addr'] && $_POST['type'] && POST['network_id']){
			if($_POST['loc_name'] != $default_loc_name_val && $_POST['geo_name'] != $default_geo_name_val){
				echo "<p>Updating Location</p>";
				$update_dns_sql = "UPDATE locations SET name = '".pg_escape_string($_POST['loc_name'])."', geo_name = '".pg_escape_string($_POST['geo_name'])."', address = '".pg_escape_string($_POST['loc_addr'])."', type = '".pg_escape_string($_POST['type'])."' , network_id = '".pg_escape_string($_POST['network_id'])."' WHERE id=".pg_escape_string($_POST['loc_id'])."";
				$update_dns_ret = pg_query($db,$update_dns_sql);
				if($update_dns_ret){
					echo "<p>Updated Location</p>";
				}
				else{
					echo "<p>Failed to update Location: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Default values not allowed.</p>";
			}
		}
		else{
			echo "<p>Not enough parameters.</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a Location
		if($_POST['loc_id']){
			echo "<p>Deleting Location</p>";
			$delete_loc_sql = "DELETE FROM locations WHERE id=".pg_escape_string($_POST['loc_id']);
			$delete_loc_ret = pg_query($db,$delete_loc_sql);
			if($delete_loc_ret){
				echo "<p>Deleted Location</p>";
			}
			else{
				echo "<p>Failed to delete Location: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the locations Table for Printing
$sql = "SELECT * FROM locations";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert Location -->
<table>
	<thead>
<tr><th>Name</th><th>Geo Name</th><th>Address</th><th>Type</th></tr>
</thead>
<tr>
<form method="post" action="locations.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="loc_name" value="<?php echo $default_loc_name_val; ?>"></td>
	<td><input type="text" name="geo_name" value="<?php echo $default_geo_name_val; ?>"></td>
	<td><input type="text" name="loc_addr" value="<?php echo $default_loc_addr_val; ?>"></td>
	<td><select name="type" id="insert_loc_type">
<?php

// Discover Location Types
$discover_loc_sql = "SELECT * FROM location_types";
$discover_loc_ret = pg_query($db,$discover_loc_sql);
if($discover_loc_ret){
	while($type = pg_fetch_assoc($discover_loc_ret)){
		echo "<option value=\"".$type['id']."\">".$type['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>
	
	</select>
	</td>
	
	<td><select name="network_id" id="insert_network_id">
<?php

$discover_net_sql = "SELECT * FROM networks";
$discover_net_ret = pg_query($db,$discover_net_sql);
if($discover_net_ret){
	while($net = pg_fetch_assoc($discover_net_ret)){
		echo "<option value=\"".$net['id']."\">".$net['network_name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}
?>
	
	
	</select></td>
		
	<td><input type="submit" name="submit" value="Add Location"></td>
</form>
</tr>
</table>

<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<?php
//dynamically print field names
for ($i = 0; $i < $num_fields; $i++) {
	if(pg_field_name($ret,$i) != "unique_id"){
		echo "<th>".pg_field_name($ret,$i)."</th>";
	}
}
?>

<th>Insert/Modify</th><th>Or Delete</th>
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"locations.php\" onsubmit=\"return confirm('Are you sure you want to Update this locations?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"loc_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"loc_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"geo_name\" value=\"".$row['geo_name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"loc_addr\" value=\"".$row['address']."\"></td>";
	echo "<td><input type=\"hidden\" name=\"type\" value=\"".$row['type']."\">";
	// Discover Location Types
	$discover_loc_sql = "SELECT * FROM location_types WHERE id=".pg_escape_string($row['type']);
	$discover_loc_ret = pg_query($db,$discover_loc_sql);
	if($discover_loc_ret){
		while($loc = pg_fetch_assoc($discover_loc_ret)){
			echo "<p>".$loc['name']."</p>";
		}
	}
	echo "</td>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	echo "<td><input type=\"hidden\" name=\"network_id\" value=\"".$row['network_id']."\">";
	// Discover Network ID
	$discover_net_sql = "SELECT * FROM networks WHERE id=".pg_escape_string($row['network_id']);
	$discover_net_ret = pg_query($db,$discover_net_sql);
	if($discover_net_ret){
		while($net = pg_fetch_assoc($discover_net_ret)){
			echo "<p>".$net['network_name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Location\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"locations.php\" onsubmit=\"return confirm('Are you sure you want to Delete this locations?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"loc_id\" value=\"".$row['id']."\">";
	# If location has servers attached don't delete it.
	$find_servers_sql = "SELECT count(loc_id) FROM rel_net_loc_gw WHERE loc_id=".pg_escape_string($row['id']);
	$find_servers_ret = pg_query($db,$find_servers_sql);
	if(($find_servers = pg_fetch_assoc($find_servers_ret))['count']){
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Location\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete Location\" >";
	}
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
pg_close($db);
?>
