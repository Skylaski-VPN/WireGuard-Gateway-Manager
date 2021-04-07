<html>
<head>
<title>WireGuard Gateway Manager - IaaS Providers</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - IaaS Providers</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_iaas_name_val="Provider Name";
$default_iaas_desc_val="-";

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
	elseif($_POST['action'] == "add"){	// Add a DNS Server
		if($_POST['iaas_name'] && $_POST['iaas_desc'] && $_POST['type']){
			if($_POST['iaas_name'] != $default_iaas_name_val && $_POST['iaas_desc'] != $default_iaas_desc_val){
				echo "<p>Adding IaaS Provider</p>";
				$new_unique_id = generateUniqueID(64,'iaas_providers',$db);
				$insert_iaas_sql = "INSERT INTO iaas_providers (name,description,type,unique_id) VALUES ('".pg_escape_string($_POST['iaas_name'])."','".pg_escape_string($_POST['iaas_desc'])."','".pg_escape_string($_POST['type'])."','".pg_escape_string($new_unique_id)."')";
				$insert_iaas_ret = pg_query($db,$insert_iaas_sql);
				if($insert_iaas_ret){
					echo "<p>Added IaaS Provider</p>";
				}
				else{
					echo "<p>Failed to Add IaaS Provider: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Please use unique values</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
		
	}
	elseif($_POST['action'] == "update"){	// Update a DNS Server
		if($_POST['iaas_id'] && $_POST['iaas_name'] && $_POST['iaas_desc'] && $_POST['type']){
			if($_POST['iaas_name'] != $default_iaas_name_val && $_POST['iaas_desc'] != $default_iaas_desc_val){
				echo "<p>Deleting IaaS Provider</p>";
				$update_iaas_sql = "UPDATE iaas_providers SET name = '".pg_escape_string($_POST['iaas_name'])."', description = '".pg_escape_string($_POST['iaas_desc'])."', type = '".pg_escape_string($_POST['type'])."' WHERE id = '".pg_escape_string($_POST['iaas_id'])."'";
				$update_iaas_ret = pg_query($db,$update_iaas_sql);
				if($update_iaas_ret){
					echo "<p>Updated IaaS Provider</p>";
				}
				else{
					echo "<p>Failed to Update IaaS Provider: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Unique values required</p>";
			}
		}
		else{
			echo "<p>Not Enough Parameters</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a DNS Server
		if($_POST['iaas_id']){
			# Clear out any related zones and images
			echo "<p>Deleting related images</p>";
			$delete_images_sql = "DELETE FROM iaas_vm_images WHERE provider=".pg_escape_string($_POST['iaas_id']);
			$delete_images_ret = pg_query($delete_images_sql);
			if($delete_images_ret){
				echo "<p>Deleted related images</p>";
				echo "<p>Deleting related zones</p>";
				$delete_zones_sql = "DELETE FROM iaas_zones WHERE provider=".pg_escape_string($_POST['iaas_id']);
				$delete_zones_ret = pg_query($delete_zones_sql);
				if($delete_zones_ret){
					echo "<p>Deleted Related Zones</p>";
				}
				else{
					echo "<p>Failed to delete zones: ".pg_last_error($db)."</p>";
				}
			}
			else{
				echo "<p>Failed to delete images: ".pg_last_error($db)."</p>";
			}
			
			echo "<p>Deleting IaaS Provider</p>";
			$delete_iaas_sql = "DELETE FROM iaas_providers WHERE id=".pg_escape_string($_POST['iaas_id']);
			$delete_iaas_ret = pg_query($db,$delete_iaas_sql);
			if($delete_iaas_ret){
				echo "<p>Deleted DNS Server</p>";
			}
			else{
				echo "<p>Failed to delete DNS Server: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the IaaS Providers Table for Printing
$sql = "SELECT * FROM iaas_providers";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert IaaS Provider -->
<table>
	<thead>
<tr><th>Name</th><th>Description</th><th>Type</th></tr>
</thead>
<tr>
<form method="post" action="iaas.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="iaas_name" value="<?php echo $default_iaas_name_val; ?>"></td>
	<td><input type="text" name="iaas_desc" value="<?php echo $default_iaas_desc_val; ?>"></td>
	<td><select name="type" id="insert_iaas_type">
<?php
// Discover IaaS Types
$discover_iaas_sql = "SELECT * FROM iaas_provider_types";
$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
if($discover_iaas_ret){
	while($iaas = pg_fetch_assoc($discover_iaas_ret)){
		echo "<option value=\"".$iaas['id']."\">".$iaas['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>
	
	</select>
	</td>
	
		
	<td><input type="submit" name="submit" value="Add IaaS Provider"></td>
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
	
	echo "<form method=\"post\" action=\"iaas.php\" onsubmit=\"return confirm('Are you sure you want to Update this IaaS Provider?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"iaas_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"iaas_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"iaas_desc\" value=\"".$row['description']."\"></td>";	
	
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	# If IaaS Provider has an auth_config let's not allow type changing
	$get_auth_sql = "SELECT provider FROM iaas_auth_configs WHERE provider=".pg_escape_string($row['id']);
	$get_auth_ret = pg_query($db,$get_auth_sql);
	$auth = pg_fetch_assoc($get_auth_ret);
	if($auth['provider'] == $row['id']){
		echo "<input type=\"hidden\" name=\"type\" value=\"".$row['type']."\">";
		$get_iaas_type_sql = "SELECT name FROM iaas_provider_types WHERE id=".pg_escape_string($row['type']);
		$get_iaas_type_ret = pg_query($db,$get_iaas_type_sql);
		$type = pg_fetch_assoc($get_iaas_type_ret);
		echo "<td><p>".$type['name']."</p></td>";
	}
	else{
		echo "<td><select name=\"type\" id=\"".$row['id']."_type\">";
		// Discover IaaS Types
		$discover_iaas_sql = "SELECT * FROM iaas_provider_types";
		$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
		if($discover_iaas_ret){
			while($iaas = pg_fetch_assoc($discover_iaas_ret)){
				echo "<option value=\"".$iaas['id']."\">".$iaas['name']."</option>";
			}
		}
		else{
			echo "<p>".pg_last_error($db)."</p>";
		}
		echo "</select></td>";
		echo "<script>setValue('".$row['id']."_type','".$row['type']."')</script>";
	}
	
		
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update IaaS Provider\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"iaas.php\" onsubmit=\"return confirm('Are you sure you want to Delete this IaaS Provider?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"iaas_id\" value=\"".$row['id']."\">";
	
	if($auth['provider'] == $row['id']){
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete IaaS Provider\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete IaaS Provider\" >";
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
