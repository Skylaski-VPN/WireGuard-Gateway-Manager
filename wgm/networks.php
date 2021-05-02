<html>
<head>
<title>WireGuard Gateway Manager - Networks</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Networks</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_network_name_val="Network Name";
//
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
	elseif($_POST['action'] == "add"){	// Add a Network
		if($_POST['network_name'] && $_POST['ipv4_netmask'] && $_POST['ipv6_netmask'] ){
			if($_POST['network_name'] != $default_network_name_val){
				echo "<p>Adding Network</p>";
				echo "<p>Checking ipv4 netmask: ".$_POST['ipv4_netmask']."</p>";
				// Check if valid ipv4 netmask
				if($ipv4_gateway = getGatewayIPv4($_POST['ipv4_netmask'])){
					echo "<p>VALID: GW: ".$ipv4_gateway."</p>";
					echo "<p>Potential Client IP: ".getClientIPv4($_POST['ipv4_netmask'])."</p>";
					echo "Checking ipv6 netmask: ".$_POST['ipv6_netmask']."";
					if($ipv6_gateway = getGatewayIPv6($_POST['ipv6_netmask'])){
						echo "<p>Valid: GW: ".$ipv6_gateway."</p>";
						echo "<p>Potential Client IP: ".getClientIPv6($_POST['ipv6_netmask'])."</p>";
						$new_unique_id = generateUniqueID(64,'networks',$db);
						// Alright, we have a valid IPv4 Network and IPv6 Network, let's INSERT
						$insert_network_sql = "INSERT INTO networks (network_name, ipv4_netmask, ipv4_gateway, ipv6_netmask,  ipv6_gateway, unique_id) VALUES ('".pg_escape_string($_POST['network_name'])."', '".pg_escape_string($_POST['ipv4_netmask'])."', '".pg_escape_string($ipv4_gateway)."', '".pg_escape_string($_POST['ipv6_netmask'])."', '".pg_escape_string($ipv6_gateway)."', '".pg_escape_string($new_unique_id)."')";
						$insert_network_ret = pg_query($db,$insert_network_sql);
						if($insert_network_ret){
							echo "<p>Created Network: ".$_POST['network_name']."</p>";
						}
						else{
							echo "<p>Failed to create Network: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Bad IP/CIDR value for IPv6 Netmask</p>";
					}
				}
				else{
					echo "<p>Bad IP/CIDR value for IPv4 Netmask</p>";
				}
			}
			else{
				echo "<p>Please enter unique network name</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update a Network
		if($_POST['network_name'] && $_POST['ipv4_netmask'] && $_POST['ipv6_netmask'] && $_POST['network_id']){
			if($_POST['network_name'] != $default_network_name_val){
				echo "<p>Updating Network</p>";
				echo "<p>Checking ipv4 netmask: ".$_POST['ipv4_netmask']."</p>";
				// Check if valid ipv4 netmask
				if($ipv4_gateway = getGatewayIPv4($_POST['ipv4_netmask'])){
					echo "<p>VALID: GW: ".$ipv4_gateway."</p>";
					echo "<p>Potential Client IP: ".getClientIPv4($_POST['ipv4_netmask'])."</p>";
					echo "Checking ipv6 netmask: ".$_POST['ipv6_netmask']."";
					if($ipv6_gateway = getGatewayIPv6($_POST['ipv6_netmask'])){
						echo "<p>Valid: GW: ".$ipv6_gateway."</p>";
						echo "<p>Potential Client IP: ".getClientIPv6($_POST['ipv6_netmask'])."</p>";
						//$new_unique_id = generateUniqueID(64,'networks',$db);
						// Alright, we have a valid IPv4 Network and IPv6 Network, let's INSERT
						$update_network_sql = "UPDATE networks SET network_name = '".pg_escape_string($_POST['network_name'])."', ipv4_netmask = '".pg_escape_string($_POST['ipv4_netmask'])."', ipv4_gateway = '".pg_escape_string($ipv4_gateway)."', ipv6_netmask = '".pg_escape_string($_POST['ipv6_netmask'])."',  ipv6_gateway = '".pg_escape_string($ipv6_gateway)."' WHERE id=".pg_escape_string($_POST['network_id'])."";
						$update_network_ret = pg_query($db,$update_network_sql);
						if($update_network_ret){
							echo "<p>Update Network: ".$_POST['network_name']."</p>";
						}
						else{
							echo "<p>Failed to create Network: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Bad IP/CIDR value for IPv6 Netmask</p>";
					}
				}
				else{
					echo "<p>Bad IP/CIDR value for IPv4 Netmask</p>";
				}
			}
			else{
				echo "<p>Please enter unique network name</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a Network
		if($_POST['network_id']){
			$delete_network_sql = "DELETE FROM networks WHERE id=".pg_escape_string($_POST['network_id']);
			$delete_network_ret = pg_query($db, $delete_network_sql);
			if($delete_network_ret){
				echo "<p>Deleted Network</p>";
			}
			else{
				echo "<p>Failed to delete Network: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Network ID Invalid</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST["action"]."</p>";
	}
}



// Pull the Networks table for printing
$sql = "SELECT * FROM networks";
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
<tr><th>Name</th><th>IPv4 Netmask</th><th>IPv6 Netmask</th></tr>
</thead>
<tr>
<form method="post" action="networks.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="network_name" value="<?php echo $default_network_name_val; ?>"></td>
	<td><input type="text" name="ipv4_netmask"></td>
	<!-- <td><input type="text" name="ipv4_gateway"></td> -->
	<td><input type="text" name="ipv6_netmask"></td>
	<!-- <td><input type="text" name="ipv6_gateway"></td> -->
		
	<td><input type="submit" name="submit" value="Add Network"></td>
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
	
	echo "<form method=\"post\" action=\"networks.php\" onsubmit=\"return confirm('Are you sure you want to Update this Network?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"network_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";
	echo "<td><input type=\"text\" name=\"network_name\" value=\"".$row['network_name']."\"></td>";
	
	# If network has gateway's attached we can't change the IP Configurations
	$find_gw_sql = "SELECT net_id FROM rel_net_loc_gw WHERE net_id=".pg_escape_string($row['id']);
	$find_gw_ret = pg_query($db,$find_gw_sql);
	if(($gw_row = pg_fetch_assoc($find_gw_ret))['net_id'] == $row['id']){
		echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv4_netmask\" value=\"".$row['ipv4_netmask']."\"></td>";	
	}
	else{
		echo "<td><input type=\"text\" name=\"ipv4_netmask\" value=\"".$row['ipv4_netmask']."\"></td>";	
	}
		
	
	echo "<td>".$row['ipv4_gateway']."</td>";
	if($gw_row['net_id'] == $row['id']){
		echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv6_netmask\" value=\"".$row['ipv6_netmask']."\"></td>";
	}
	else{
		echo "<td><input type=\"text\" name=\"ipv6_netmask\" value=\"".$row['ipv6_netmask']."\"></td>";
	}
	echo "<td>".$row['ipv6_gateway']."</td>";
	
	
	
	// Count DNS Servers
	// Count Gateway Servers
	
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Network\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"networks.php\" onsubmit=\"return confirm('Are you sure you want to Delete this Domain?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"network_id\" value=\"".$row['id']."\">";
	# If Network has locations or DNS servers let's not delete it
	$find_dns_servers_sql = "SELECT network_id FROM rel_network_dns WHERE network_id=".pg_escape_string($row['id']);
	$find_dns_servers_ret = pg_query($find_dns_servers_sql);
	if($dns_servers = pg_fetch_assoc($find_dns_servers_ret)){
		if($dns_servers['network_id']==$row['id']){
				echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Network\" >";
		}
		else{
			$find_locations_sql = "SELECT network_id FROM locations WHERE network_id=".pg_escape_string($row['id']);
			$find_locations_ret = pg_query($find_locations_sql);
			if($locations = pg_fetch_assoc($find_locations_ret)){
				echo "NETWORK ID: ".$locations['network_id'];
				if($locations['network_id']==$row['id']){
					echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Network\" >";
				}
			}
			else{
				echo "<input type=\"submit\" name=\"submit\" value=\"Delete Network\" >";
			}
		}
	}
	else{
		$find_locations_sql = "SELECT network_id FROM locations WHERE network_id=".pg_escape_string($row['id']);
		$find_locations_ret=pg_query($find_locations_sql);
		if($locations = pg_fetch_assoc($find_locations_ret)){
			if($locations['network_id']==$row['id']){
				echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Network\" >";
			}
		}
		else{
			echo "<input type=\"submit\" name=\"submit\" value=\"Delete Network\" >";
		}
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
