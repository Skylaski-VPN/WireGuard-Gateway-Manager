<html>
<head>
<title>WireGuard Gateway Manager - DNS Zones</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - DNS Zones</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_zone_name_val="Zone Name";
$default_dns_zone_desc_val="-";
$default_dns_zone_val="-";

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
	elseif($_POST['action'] == "add"){	// Add a DNS Zone
		if($_POST['zone_name'] && $_POST['zone_value'] && $_POST['zone_desc'] && $_POST['zone_provider']){
			if($_POST['zone_name'] != $default_dns_zone_name_val && $_POST['zone_value'] != $default_dns_zone_val && $_POST['zone_desc'] != $default_dns_zone_desc_val){
				echo "<p>Adding DNS Zone</p>";
				$new_unique_id = generateUniqueID(64,'dns_zones',$db);
				$insert_dns_zone_sql = "INSERT INTO dns_zones (name, value, description, unique_id, provider) VALUES ('".pg_escape_string($_POST['zone_name'])."','".pg_escape_string($_POST['zone_value'])."','".pg_escape_string($_POST['zone_desc'])."','".pg_escape_string($new_unique_id)."','".pg_escape_string($_POST['zone_provider'])."')";
				$insert_dns_zone_ret = pg_query($db,$insert_dns_zone_sql);
				if($insert_dns_zone_ret){
					echo "<p>Added DNS Zone</p>";
				}
				else{
					echo "<p>Failed to insert DNS Zone: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Unique Values Required</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update a DNS Zone
		if($_POST['zone_desc'] && $_POST['zone_id']){
			if($_POST['zone_name'] != $default_dns_zone_name_val && $_POST['zone_value'] != $default_dns_zone_val && $_POST['zone_desc'] != $default_dns_zone_desc_val){
				echo "<p>Updating DNS Zone</p>";
				$update_dns_zone_sql = "UPDATE dns_zones SET description = '".pg_escape_string($_POST['zone_desc'])."' WHERE id=".pg_escape_string($_POST['zone_id']);
				$update_dns_zone_ret = pg_query($db,$update_dns_zone_sql);
				if($update_dns_zone_ret){
					echo "<p>Updated DNS Zone</p>";
				}
				else{
					echo "<p>Failed to update DNS Zone: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Unique Values Required</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a DNS Zone
		if($_POST['zone_id']){
			echo "<p>Deleting DNS Zone</p>";
			$delete_dns_zone_sql = "DELETE FROM dns_zones WHERE id = ".pg_escape_string($_POST['zone_id']);
			$delete_dns_zone_ret = pg_query($db,$delete_dns_zone_sql);
			if($delete_dns_zone_ret){
				echo "<p>Deleted DNS Zone</p>";
			}
			else{
				echo "<p>Failed to delete DNS Zone: ".pg_last_error($db)."</p>";
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



// Pull the DNS Zones Table for Printing
$sql = "SELECT * FROM dns_zones";
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

<!-- <th>Insert/Modify</th> -->
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"dns_zones.php\" onsubmit=\"return confirm('Are you sure you want to Update this DNS Zone?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"zone_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td>".$row['name']."</td>";
	echo "<td>".$row['value']."</td>";
	echo "<td><input type=\"text\" name=\"zone_desc\" value=\"".$row['description']."\"></td>";	
	
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	echo "<td>";
	// Discover DNS Providers
	$discover_dns_sql = "SELECT name FROM dns_providers WHERE id = ".$row['provider'];
	$discover_dns_ret = pg_query($db,$discover_dns_sql);
	if($discover_dns_ret){
		while($dns = pg_fetch_assoc($discover_dns_ret)){
			echo $dns['name'];
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	echo "<script>setValue('".$row['id']."_provider','".$row['provider']."')</script>";
	
	 # Code for updateing/deleting DNS Zone
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Description\"></td></form>";
	/*	
	echo "<td>";
	echo "<form method=\"post\" action=\"dns_zones.php\" onsubmit=\"return confirm('Are you sure you want to Delete this DNS Zone?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"zone_id\" value=\"".$row['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete DNS Zone\" >";
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
