<html>
<head>
<title>WireGuard Gateway Manager - Config Templates</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Config Templates</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_temp_name_val="Config Name";
$default_temp_data_val="-";

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
		if($_POST['name'] && $_POST['data']){
			if($_POST['name'] != $default_temp_name_val && $_POST['data']!= $default_temp_data_val){
				# Make sure this config doesn't already exist
				$find_config_sql = "SELECT count(id) as count FROM templates WHERE name='".pg_escape_string($_POST['name'])."'";
				$find_config_ret = pg_query($find_config_sql);
				if(($row = pg_fetch_assoc($find_config_ret))['count']){
					echo "<p>Config already exists, doing nothing</p>";
				}
				else{
					echo "<p>Adding Config</p>";
					$insert_config_sql = "INSERT INTO templates (name,data) VALUES ('".pg_escape_string($_POST['name'])."','".pg_escape_string($_POST['data'])."')";
					$insert_config_ret = pg_query($insert_config_sql);
					if($insert_config_ret){
						echo "<p>Config Added</p>";
					}
					else{
						echo "<p>Failed to add config: ".pg_last_error($db)."</p>";
					}
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
	elseif($_POST['action'] == "update"){	// Update a DNS Server
		if($_POST['id'] && $_POST['name'] && $_POST['data']){
			echo "<p>Updating Config</p>";
			$update_config_sql = "UPDATE templates SET name='".pg_escape_string($_POST['name'])."', data='".pg_escape_string($_POST['data'])."' WHERE id=".pg_escape_string($_POST['id']);
			$update_config_ret = pg_query($update_config_sql);
			if($update_config_ret){
				echo "<p>Updated Config</p>";
			}
			else{
				echo "<p>Failed to update Config: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a DNS Server
		if($_POST['id']){
			# Make sure this isn't a default config
			$select_config_name_sql = "SELECT name FROM templates WHERE id=".pg_escape_string($_POST['id']);
			$select_config_name_ret = pg_query($select_config_name_sql);
			if($config = pg_fetch_assoc($select_config_name_ret)){
				if(preg_match("/default_gw/",$config['name']) == True || preg_match("/default_client/",$config['name']) == True){
					echo "<p>Can't delete default config</p>";
				}
				else{
					echo "<p>Deleting config</p>";
					$delete_config_sql = "DELETE FROM templates WHERE id=".pg_escape_string($_POST['id']);
					$delete_config_ret = pg_query($db,$delete_config_sql);
					if($delete_config_ret){
						echo "<p>Deleted Config</p>";
					}
					else{
						echo "<p>Failed to delete config: ".pg_last_error($db)."</p>";
					}
				}
			}
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the IaaS Providers Table for Printing
$sql = "SELECT * FROM templates";
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
<tr><th>Name</th><th>Data</th></tr>
</thead>
<tr>
<form method="post" action="config_templates.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="name" value="<?php echo $default_temp_name_val; ?>"></td>
	<td><textarea cols="40" rows="10" name="data"><?php echo $default_temp_data_val; ?></textarea></td>
			
	<td><input type="submit" name="submit" value="Add Config Template"></td>
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
	
	echo "<form method=\"post\" action=\"config_templates.php\" onsubmit=\"return confirm('Are you sure you want to Update this Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"name\" value=\"".$row['name']."\"></td>";	
	echo "<td><textarea name=\"data\" cols=\"40\" rows=\"10\">".$row['data']."</textarea>";	
	
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
			
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Config\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"config_templates.php\" onsubmit=\"return confirm('Are you sure you want to Delete this Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\">";
	
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete Config\" >";
	
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
