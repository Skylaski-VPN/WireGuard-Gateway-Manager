<html>
<head>
<title>WireGuard Gateway Manager - IaaS Zones</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - IaaS Zones</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_iaas_zone_name_val="Zone Name";
$default_iaas_zone_val="-";

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
	elseif($_POST['action'] == "add"){	// Add an Auth Config
		if($_POST['zone_name'] && $_POST['zone_value'] && $_POST['zone_provider']){
			if($_POST['zone_name'] != $default_iaas_zone_name_val && $_POST['zone_value'] != $default_iaas_zone_val){
				$new_unique_id = generateUniqueID(64,'iaas_zones',$db);
				$insert_zone_sql = "INSERT INTO iaas_zones (name,value,provider,unique_id) VALUES ('".pg_escape_string($_POST['zone_name'])."','".pg_escape_string($_POST['zone_value'])."','".pg_escape_string($_POST['zone_provider'])."','".pg_escape_string($new_unique_id)."')";
				$insert_zone_ret = pg_query($db,$insert_zone_sql);
				if($insert_zone_ret){
					echo "<p>Added IaaS Zone</p>";
				}
				else{
					echo "<p>Failed to Add IaaS Zone: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Unique values required</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update an Auth Config
		if($_POST['zone_name'] && $_POST['zone_value'] && $_POST['zone_provider']){
			if($_POST['zone_name'] != $default_iaas_zone_name_val && $_POST['zone_value'] != $default_iaas_zone_val){
				$update_zone_sql = "UPDATE iaas_zones SET name = '".pg_escape_string($_POST['zone_name'])."', value = '".pg_escape_string($_POST['zone_value'])."', provider = '".pg_escape_string($_POST['zone_provider'])."' WHERE id = '".pg_escape_string($_POST['zone_id'])."'";
				$update_zone_ret = pg_query($db,$update_zone_sql);
				if($update_zone_ret){
					echo "<p>Updated IaaS Zone</p>";
				}
				else{
					echo "<p>Failed to Add IaaS Zone: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Unique values required</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete an Auth Config
		if($_POST['zone_id']){
			$delete_zone_sql = "DELETE FROM iaas_zones WHERE id=".pg_escape_string($_POST['zone_id']);
			$delete_zone_ret = pg_query($db,$delete_zone_sql);
			if($delete_zone_ret){
				echo "<p>Deleted IaaS Zone</p>";
			}
			else{
				echo "<p>Failed to delete IaaS Zone: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Mising Arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the IaaS Zones Table for Printing
$sql = "SELECT * FROM iaas_zones";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>


	
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

</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"iaas_zones.php\" onsubmit=\"return confirm('Are you sure you want to Update this IaaS Zones?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"zone_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" disabled=\"disabled\" name=\"zone_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" disabled=\"disabled\" name=\"zone_value\" value=\"".$row['value']."\"></td>";	
	echo "<td>";
	// Discover Auth Providers
	$discover_iaas_sql = "SELECT * FROM iaas_providers WHERE id = ".$row['provider'];
	$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
	if($discover_iaas_ret){
		while($iaas = pg_fetch_assoc($discover_iaas_ret)){
			echo $iaas['name'];
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
		
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	
	/*	# Update & Delete Code
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update IaaS Zone\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"iaas_zones.php\" onsubmit=\"return confirm('Are you sure you want to Delete this IaaS Zone?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"zone_id\" value=\"".$row['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete IaaS Zone\" >";
	echo "</form>";
	echo "</td>";
	* 
	*/
	
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
