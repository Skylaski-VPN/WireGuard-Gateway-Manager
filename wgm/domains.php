<html>
<head>
<title>WireGuard Gateway Manager - Domains</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Domains</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_domain_name_val="Domain Name";
$default_domain_type_val="Team/User";
// attempt to connect to DB
$db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
if(!$db) {
	echo "Error : Unable to open database\n";
} else {
	echo "<p>Opened database successfully</p>";
}

// Are we receiving data?
if($_SERVER["REQUEST_METHOD"] == "POST"){
	if(empty($_POST["action"])){
		echo "<p>action is required</p>";
	}
	elseif($_POST["action"] == "add"){	// Add a domain
		if($_POST["domain_name"] && $_POST["domain_type"] && $_POST['total_users']){
			if($_POST["domain_name"] == $default_domain_name_val){
				echo "<p>Please enter a unique Domain Name</p>";
			}
			else {
				if(is_numeric($_POST['total_users']) && $_POST['total_users'] > 0 && $_POST['total_users'] <= $TOTAL_USERS_PER_DOMAIN){
					echo "<p>Adding Domain ".$_POST["domain_name"]."</p>";
					$new_unique_id = generateUniqueID(64,'domains',$db);
					$insert_sql = "INSERT INTO domains (name,type,unique_id,total_users) VALUES ('".pg_escape_string($_POST["domain_name"])."', '".pg_escape_string($_POST["domain_type"])."', '".pg_escape_string($new_unique_id)."', '".pg_escape_string($_POST['total_users'])."')";
					$insert_ret = pg_query($db,$insert_sql);
					if(!$insert_ret){
						echo "<p>".pg_last_error($db)."</p>";
					}
					else{
						echo "<p>Domain Added!</p>";
					}
				}
				else{
					echo "<p>Total Users must be a number between 1 and ".$TOTAL_USERS_PER_DOMAIN."</p>";
				}
			}
		}
		else{
			echo "<p>Missing Domain Name or Type</p>";
		}
	}
	elseif($_POST["action"] == "update"){	// Update a domain
		echo "<p>Modifying Domain</p>";
		if($_POST["domain_name"] && $_POST["domain_type"] && $_POST["domain_id"] && $_POST['total_users']){
			if(is_numeric($_POST['total_users']) && $_POST['total_users'] > 0 && $_POST['total_users'] <= 100){
				# Let's make sure total_users is higher or equal to the current number of configured users
				$get_user_count_sql = "SELECT count(id) as count FROM users WHERE domain_id=".pg_escape_string($_POST['domain_id']);
				$get_user_count_ret = pg_query($db,$get_user_count_sql);
				if(($get_user_count = pg_fetch_assoc($get_user_count_ret))['count'] <= $_POST['total_users']){
					$update_sql = "UPDATE domains SET name = '".pg_escape_string($_POST["domain_name"])."', type = '".pg_escape_string($_POST["domain_type"])."', total_users='".pg_escape_string($_POST['total_users'])."' WHERE id=".pg_escape_string($_POST["domain_id"])."";
					$update_ret = pg_query($db,$update_sql);
					if(!$update_ret){
						echo "<p>".pg_last_error($db)."</p>";
					}
					else{
						echo "<p>Updated Domain</p>";
					}
				}
				else{
					echo "<p>There are more than ".$_POST['total_users']." users currently configured for this domain</p>";
				}
			}
			else{
				echo "<p>Total Users Must Be a Number Between 1 and ".$TOTAL_USERS_PER_DOMAIN."</p>";
			}
		}
		else{
			echo "<p>Missing Domain Name or Type</p>";
		}
	}
	elseif($_POST["action"] == "delete"){	// Delete a domain
		echo "<p>Deleting Domain</p>";
		if(empty($_POST["domain_id"])){
			echo "<p>Domain ID required</p>";
		}
		else{
			$delete_sql = "DELETE FROM domains WHERE id=".pg_escape_string($_POST["domain_id"])."";
			$delete_ret = pg_query($db,$delete_sql);
			if(!$delete_ret){
				echo "<p>".pg_last_error($db)."</p>";
			}
			else{
				echo "<p>Deleted Domain ".$_POST["domain_id"]."</p>";
			}
			# Now delete associated network relationships
			$delete_net_rel_sql = "DELETE FROM rel_domain_network WHERE domain_id=".pg_escape_string($_POST['domain_id']);
			$delete_net_rel_ret = pg_query($db,$delete_net_rel_sql);
			if($delete_net_rel_ret){
				echo "<p>Deleted Network Relationships</p>";
			}
			else{
				echo"<p>Failed to Delete Network Relationships: ".pg_last_error($db)."</p>";
			}
		}
	}
	else{
		echo "<p>undefined action: ".$_POST["action"]."</p>";
	}
}



// Pull the Domains table for printing
$sql = "SELECT * FROM domains";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert Domain -->
<table>
	<thead>
<tr><th>Name</th><th>Type</th><th>Total Users</th></tr>
</thead>
<tr>
<form method="post" action="domains.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="domain_name" value="<?php echo $default_domain_name_val; ?>"></td>
	
	<td><select name="domain_type" id="insert_domain_type"> <!-- domain type selector -->
	
<?php
// Discover Domain Types
$sel_type_sql = "SELECT * FROM domain_types";
$sel_type_ret = pg_query($db,$sel_type_sql);
if(!$sel_type_ret){
	echo "<p>Error getting domain types: ".pg_last_error($db)."</p>";
}
else{
	while($sel_row = pg_fetch_assoc($sel_type_ret)){
		echo "<option value=\"".$sel_row['value']."\">".$sel_row['name']."</option>";
	}
}
?>
	
	</select></td>
	<td><input type="number" name="total_users" value="1"></td>
	
	<td><input type="submit" name="submit" value="Add Domain"></td>
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
<th>Users</th>
<th>Insert/Modify</th><th>Or Delete</th>
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"domains.php\" onsubmit=\"return confirm('Are you sure you want to Update this Domain?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"domain_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"domain_name\" value=\"".$row['name']."\"></td>";	
	echo "<td>";
	echo "<select name=\"domain_type\" id=\"".$row['id']."_type\">";

// Discover Domain Types
$sel_type_sql = "SELECT * FROM domain_types";
$sel_type_ret = pg_query($db,$sel_type_sql);
if(!$sel_type_ret){
	echo "<p>Error getting domain types: ".pg_last_error($db)."</p>";
}
else{
	while($sel_row = pg_fetch_assoc($sel_type_ret)){
		echo "<option value=\"".$sel_row['value']."\">".$sel_row['name']."</option>";
	}
}

	
	echo "</select>";
	//set proper value
	echo "<script>setValue('".$row['id']."_type', '".$row['type']."')</script>";
	echo "</td>";
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";
	
	echo "<td><input type=\"number\" name=\"total_users\" value=\"".$row['total_users']."\"></td>";
	echo "<td>".$row['referral_code']."</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Domain\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"domains.php\" onsubmit=\"return confirm('Are you sure you want to Delete this Domain?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"domain_id\" value=\"".$row['id']."\">";
	# If this domain has users, don't delete it
	$get_users_sql = "SELECT count(id) as count FROM users WHERE domain_id=".pg_escape_string($row['id']);
	$get_users_ret = pg_query($db,$get_users_sql);
	if(($get_users = pg_fetch_assoc($get_users_ret))['count'] > 0){
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Domain\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete Domain\" >";
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
