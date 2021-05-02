<html>
<head>
<title>WireGuard Gateway Manager - IaaS VM Images</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - IaaS VM Images</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_iaas_image_name_val="Image Name";
$default_iaas_image_val="-";
$default_iaas_image_desc_val="-";

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
	elseif($_POST['action'] == "add"){	// Add a VM Image
		if($_POST['vm_name'] && $_POST['vm_value'] && $_POST['vm_desc'] && $_POST['vm_type'] && $_POST['vm_provider']){
			if($_POST['vm_name'] != $default_iaas_image_name_val && $_POST['vm_value'] != $default_iaas_image_val){
				echo "<p>Adding VM Image</p>";
				$new_unique_id = generateUniqueID(64,'iaas_vm_images',$db);
				$insert_vm_sql = "INSERT INTO iaas_vm_images (name,value,description,type,provider,unique_id) VALUES ('".pg_escape_string($_POST['vm_name'])."','".pg_escape_string($_POST['vm_value'])."','".pg_escape_string($_POST['vm_desc'])."','".pg_escape_string($_POST['vm_type'])."','".pg_escape_string($_POST['vm_provider'])."','".pg_escape_string($new_unique_id)."')";
				$insert_vm_ret = pg_query($db,$insert_vm_sql);
				if($insert_vm_ret){
					echo "<p>Added DNS Server</p>";
				}
				else{
					echo "<p>Failed to Add DNS Server: ".pg_last_error($db)."</p>";
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
	elseif($_POST['action'] == "update"){	// Update a VM Image
		if($_POST['vm_desc'] && $_POST['vm_type']){
			if($_POST['vm_desc'] != $default_iaas_image_desc_val){
				$update_vm_sql = "UPDATE iaas_vm_images SET description = '".pg_escape_string($_POST['vm_desc'])."', type = '".pg_escape_string($_POST['vm_type'])."' WHERE id = '".pg_escape_string($_POST['vm_id'])."'";
				$update_vm_ret = pg_query($db,$update_vm_sql);
				if($update_vm_ret){
					echo "<p>Updated VM Image</p>";
				}
				else{
					echo "<p>Failed to update VM Image: ".pg_last_error($db)."</p>";
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
	elseif($_POST['action'] == "delete"){	// Delete an Auth Config
		if($_POST['vm_id']){
			echo "<p>Deleting VM Image</p>";
			$delete_vm_sql = "DELETE FROM iaas_vm_images WHERE id=".pg_escape_string($_POST['vm_id']);
			$delete_vm_ret = pg_query($db,$delete_vm_sql);
			if($delete_vm_ret){
				echo "<p>Deleted DNS Server</p>";
			}
			else{
				echo "<p>Failed to delete DNS Server: ".pg_last_error($db)."</p>";
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



// Pull the IaaS Zones Table for Printing
$sql = "SELECT * FROM iaas_vm_images";
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

<th>Insert/Modify</th>
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"iaas_vm_images.php\" onsubmit=\"return confirm('Are you sure you want to Update this VM Image?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"vm_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" disabled=\"disabled\" name=\"vm_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" disabled=\"disabled\" name=\"vm_value\" value=\"".$row['value']."\"></td>";
	echo "<td><input type=\"text\" name=\"vm_desc\" value=\"".$row['description']."\"></td>";	
	echo "<td><select name=\"vm_type\" id=\"".$row['id']."_type\">";
	// Discover VM Types
	$discover_vm_sql = "SELECT * FROM iaas_vm_types";
	$discover_vm_ret = pg_query($db,$discover_vm_sql);
	if($discover_vm_ret){
		while($vm = pg_fetch_assoc($discover_vm_ret)){
			echo "<option value=\"".$vm['id']."\">".$vm['name']."</option>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</select></td>";
	echo "<script>setValue('".$row['id']."_type','".$row['type']."')</script>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	echo "<td>";
	// Discover VM Providers
	$discover_iaas_sql = "SELECT * FROM iaas_providers WHERE id=".$row['provider'];
	$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
	if($discover_iaas_ret){
		while($vm = pg_fetch_assoc($discover_iaas_ret)){
			echo $vm['name'];
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
		
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update VM Image\"></td></form>";
	
	/* # Delete VM Image Code
	echo "<td>";
	echo "<form method=\"post\" action=\"iaas_vm_images.php\" onsubmit=\"return confirm('Are you sure you want to Delete this VM Image?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"vm_id\" value=\"".$row['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete VM Image\" >";
	echo "</form>";
	echo "</td>";
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
