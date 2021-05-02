<html>
<head>
<title>WireGuard Gateway Manager - Users</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Users</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_user_name_val="Username";
$default_user_email_val="username@example.com";
//$default_user_auth_val="Email";
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
	elseif($_POST["action"] == "add"){	// Add a user
		if($_POST["user_name"] && $_POST["user_email"] && $_POST["auth_provider"] && $_POST["user_domain"] && $_POST['total_clients']){
			# Make sure we haven't reached max users on this domain
			$get_max_domain_users_sql = "SELECT total_users FROM domains WHERE id=".pg_escape_string($_POST['user_domain']);
			$get_max_domain_users_ret = pg_query($db,$get_max_domain_users_sql);
			$get_cur_user_count_sql = "SELECT count(id) as user_count FROM users WHERE domain_id=".pg_escape_string($_POST['user_domain']);
			$get_cur_user_count_ret = pg_query($db,$get_cur_user_count_sql);
			
			if((($get_cur_user_count = pg_fetch_assoc($get_cur_user_count_ret))['user_count'] + 1)   <= ($get_max = pg_fetch_assoc($get_max_domain_users_ret))['total_users']){
			
				if($_POST["user_name"] == $default_user_name_val || $_POST["user_email"] == $default_user_email_val){
					echo "<p>Please enter unique Username and Email</p>";
				}
				else{
					if(!filter_var($_POST["user_email"], FILTER_VALIDATE_EMAIL)){
						echo "<p>Enter valid Email Address</p>";
					}
					else{
						if(is_numeric($_POST['total_clients']) && $_POST['total_clients'] > 0 && $_POST['total_clients'] <= $TOTAL_CLIENTS_PER_USER){
							
							$new_unique_id = generateUniqueID(64,'users',$db);
							$insert_user_sql="INSERT INTO users (user_name,user_email,auth_provider,unique_id,domain_id,total_clients) VALUES ('".pg_escape_string($_POST["user_name"])."','".pg_escape_string($_POST["user_email"])."','".pg_escape_string($_POST["auth_provider"])."', '".pg_escape_string($new_unique_id)."', ".pg_escape_string($_POST['user_domain']).", '".pg_escape_string($_POST['total_clients'])."')";
							$insert_user_ret = pg_query($db,$insert_user_sql);
							if(!$insert_user_ret){
								echo "<p>".pg_last_error($db)."</p>";
							}
							else{
								echo "<p>User Added!</p>";
							}
						}
						else{
							echo "<p>Total Clients Must be Between 1 and ".$TOTAL_CLIENTS_PER_USER."</p>";
						}
					}
				}
			}
			else{
				echo "<p>Domain at maximum number of configured users: ".$get_max['total_users']."</p>";
			}
		}
		else{
			echo "<p>Username, Email, and Auth Method required</p>";
		}
	}
	elseif($_POST["action"] == "update"){	// Update a user
		if($_POST["user_name"] && $_POST["user_email"] && $_POST["user_id"] && $_POST["user_domain"] && $_POST['total_clients']){
			if($_POST["user_name"] == $default_user_name_val || $_POST["user_email"] == $default_user_email_val){
				echo "<p>Please enter unique Username and Email</p>";
			}
			else{
				if(!filter_var($_POST["user_email"], FILTER_VALIDATE_EMAIL)){
					echo "<p>Enter valid Email Address</p>";
				}
				else{
					if(is_numeric($_POST['total_clients']) && $_POST['total_clients'] > 0 && $_POST['total_clients'] <= $TOTAL_CLIENTS_PER_USER){
						
						$update_user_sql="UPDATE users SET user_name = '".pg_escape_string($_POST["user_name"])."', user_email = '".pg_escape_string($_POST["user_email"])."', domain_id='".pg_escape_string($_POST['user_domain'])."', total_clients='".pg_escape_string($_POST['total_clients'])."' WHERE id=".$_POST["user_id"]."";
						$update_user_ret=pg_query($db,$update_user_sql);
						if(!$update_user_ret){
							echo "<p>".pg_last_error($db)."</p>";
						}
						else{
							echo "<p>User Updated!</p>";
							//echo "<p>".$update_user_sql."</p>";
						}
					}
					else{
						echo "<p>Total Clients must be between 1 and ".$TOTAL_CLIENTS_PER_USER."</p>";
					}
				}
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST["action"] == "delete"){	// Delete a user
		if($_POST["user_id"]){
			$delete_user_sql="DELETE FROM users WHERE id=".pg_escape_string($_POST["user_id"])."";
			$delete_user_ret=pg_query($db,$delete_user_sql);
			if(!$delete_user_ret){
				echo "<p>".pg_last_error($db)."</p>";
			}
			else{
				echo "<p>User Deleted</p>";
			}
		}
		else{
			echo "<p>Missing user_id</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST["action"]."</p>";
	}
}



// Pull the Domains table for printing
$sql = "SELECT * FROM users";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert user -->
<table>
	<thead>
<tr><th>Name</th><th>Email</th><th>Auth Method</th><th>Total Clients</th><th>Domain</th></tr>
</thead>
	
<tr>
<form method="post" action="users.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="user_name" value="<?php echo $default_user_name_val; ?>"></td>
	
	<td><input type="text" name="user_email" value="<?php echo $default_user_email_val; ?>"></td>
	
	<td><select name="auth_provider" id="insert_auth_provider"> <!-- auth method selector -->
	
<?php
// Discover Auth Methods
$sel_type_sql = "SELECT * FROM auth_providers";
$sel_type_ret = pg_query($db,$sel_type_sql);
if(!$sel_type_ret){
	echo "<p>Error getting Auth Providers: ".pg_last_error($db)."</p>";
}
else{
	while($sel_row = pg_fetch_assoc($sel_type_ret)){
		echo "<option value=\"".$sel_row['id']."\">".$sel_row['name']."</option>";
	}
}

?>
	
	</select></td>

	<td><input type="number" name="total_clients" value="1"></td>

	<td><select name="user_domain" id="insert_user_domain">

<?php
// Discover Available Domains
$sel_domains_sql="SELECT id,name FROM domains";
$sel_domains_ret=pg_query($sel_domains_sql);
if(!$sel_domains_ret){
	echo "<p>".pg_last_error($db)."</p>";
}
else{
	while($domains_row = pg_fetch_assoc($sel_domains_ret)){
		echo "<option value=\"".$domains_row['id']."\">".$domains_row['name']."</option>";
	}
}

?>

</select></td>

	
	<td><input type="submit" name="submit" value="Add User"></td>
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
	
	echo "<form method=\"post\" action=\"users.php\" onsubmit=\"return confirm('Are you sure you want to Update this User?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"user_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"user_name\" value=\"".$row['user_name']."\"></td>";
	echo "<td><input type=\"text\" name=\"user_email\" value=\"".$row['user_email']."\"></td>";

	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";
	
	//Discover Domains then set proper domain
	echo "<td>";
	echo "<input type=\"hidden\" name=\"user_domain\" value=\"".$row['domain_id']."\">";
	echo "<p>".$row['domain_id']."</p>";
	echo "</td>";
	echo "<td><input type=\"number\" name=\"total_clients\" value=\"".$row['total_clients']."\"></td>";
	echo "<td>".$row['auth_provider']."</td>";
	echo "<td>".$row['provider_id']."</td>";
	echo "<td>".$row['token']."</td>";
	echo "<td>".$row['role']."</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update User\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"users.php\" onsubmit=\"return confirm('Are you sure you want to Delete this User?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"user_id\" value=\"".$row['id']."\">";
	# If user has clients configured, don't delete it.
	$get_client_count_sql = "SELECT count(id) as client_count FROM clients WHERE user_id=".pg_escape_string($row['id']);
	$get_client_count_ret = pg_query($db,$get_client_count_sql);
	if(($get_client_count = pg_fetch_assoc($get_client_count_ret))['client_count'] > 0){
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete User\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete User\" >";
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
