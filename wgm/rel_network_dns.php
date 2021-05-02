<html>
<head>
<title>WireGuard Gateway Manager - Attach/Detach DNS Server</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
<style>
tr.bottom_border td{
	border-bottom: 1px solid black;
}
</style>
</head>
<body>

<h1>WireGuard Gateway Manager - Attach/Detach DNS Server</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_id_val="-";

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
	elseif($_POST['action'] == "update"){	// Attach a Network to a Domain
		if($_POST['network_id'] && $_POST['dns_id']){
			if($_POST['dns_id'] != $default_dns_id_val){
				echo "<p>Adding DNS to Network</p>";
				// Does relationship already exist?
				$find_existing_rel_sql = "SELECT count(network_id) as count FROM rel_network_dns WHERE network_id=".pg_escape_string($_POST['network_id'])." AND dns_id=".pg_escape_string($_POST['dns_id']);
				$find_existing_rel_ret = pg_query($db,$find_existing_rel_sql);
				if(($find_row = pg_fetch_assoc($find_existing_rel_ret))['count']){
					echo "<p>Relationship Exists, doing nothing</p>";
				}
				else{
					echo "<p>Building Relationship</p>";
					$insert_rel_network_dns_sql = "INSERT INTO rel_network_dns (network_id,dns_id) VALUES (".pg_escape_string($_POST['network_id']).",".pg_escape_string($_POST['dns_id']).")";
					$insert_rel_network_dns_ret = pg_query($db,$insert_rel_network_dns_sql);
					if($insert_rel_network_dns_ret){
						echo "<p>Added DNS to Network</p>";
					}
					else{
						echo "<p>Failed to add DNS to Network: ".pg_last_error($db)."</p>";
					}
				}
			}
			else{
				echo "<p>Please Select unique DNS Server</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "clear"){	// Clear all DNS Servers from Network
		if($_POST['network_id']){
			echo "<p>Clearing DNS Servers from Network</p>";
			$clear_network_dns_sql = "DELETE FROM rel_network_dns WHERE network_id=".pg_escape_string($_POST['network_id']);
			$clear_network_dns_ret = pg_query($db, $clear_network_dns_sql);
			if($clear_network_dns_ret){
				echo "<p>Cleared DNS from Network</p>";
			}
			else{
				echo "<p>Failed to clear Network DNS: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "detach"){	// Detach DNS Server from Network
		if($_POST['network_id'] && $_POST['dns_id']){
			echo "<p>Detaching DNS Server</p>";
			$detach_dns_sql = "DELETE FROM rel_network_dns WHERE network_id=".pg_escape_string($_POST['network_id'])." AND dns_id=".pg_escape_string($_POST['dns_id'])."";
			$detach_dns_ret = pg_query($db,$detach_dns_sql);
			if($detach_dns_ret){
				echo "<p>Detached DNS Server</p>";
			}
			else{
				echo "<p>Failed to detach DNS Server: ".pg_last_error($db)."</p>";
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



// Pull the Domains table for printing
$networks_sql = "SELECT * FROM networks";
$networks_ret = pg_query($db, $networks_sql);
if(!$networks_ret){
	echo pg_last_error($db);
	exit;
}




?>



<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<th>ID</th><th>Network</th><th>DNS Server</th><th>Update</th><th>Clear</th>
</tr>
</thead>
<tbody>

<?php

while($network = pg_fetch_assoc($networks_ret)){
	echo "<tr class=\"bottom_border\">";
	echo "<td>".$network['id']."</td>";
	echo "<td>".$network['network_name']."</td>";
	
	echo "<td>";
	echo "<ul>";
	
	//discover DNS Relationships
	$net_id = $network['id'];
	$discover_dns_rel_sql=<<<EOF
	SELECT net.id as net_id, dns.id as dns_id, dns.dns_name as dns_name 
	FROM networks net
		JOIN rel_network_dns rel ON net.id = rel.network_id
		JOIN dns_servers dns ON rel.dns_id = dns.id
	WHERE net.id=$net_id
EOF;
	$discover_dns_rel_ret = pg_query($discover_dns_rel_sql);
	while($discovered_dns = pg_fetch_assoc($discover_dns_rel_ret)){
		
		echo "<form method=\"post\" action=\"rel_network_dns.php\" onsubmit=\"return confirm('Are you sure you want to remove this DNS Server?');\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"detach\">";
		echo "<input type=\"hidden\" name=\"network_id\" value=\"".$discovered_dns['net_id']."\">";
		echo "<input type=\"hidden\" name=\"dns_id\" value=\"".$discovered_dns['dns_id']."\">";
		echo "<li>".$discovered_dns['dns_name']." <input type=\"submit\" name=\"submit\" value=\"X\"></li>";
		echo "</form>";
	}
	
	// Discover DNS Server's we can add
	echo "<li>";
	echo "<form action=\"rel_network_dns.php\" method=\"post\" onsubmit=\"return confirm('Are you sure you want to Update this Network?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"network_id\" value=\"".$network['id']."\">";
	echo "<select name=\"dns_id\" id=\"".$network['id']."_dns\">";
	echo "<option value=\"".$default_dns_id_val."\">".$default_dns_id_val."</option>";
	$discover_dns_servers_sql = "SELECT * FROM dns_servers";
	$discover_dns_servers_ret = pg_query($db,$discover_dns_servers_sql);
	if($discover_dns_servers_ret){
		while($dns = pg_fetch_assoc($discover_dns_servers_ret)){
			echo "<option value=\"".$dns['id']."\">".$dns['dns_name']."</option>";
		}
	}
	else{
		echo "<p>No DNS Servers Found: ".pg_last_error($db)."</p>";
	}
	
	echo "</select>";
	echo "</li>";
	echo "</ul>";
	echo "</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Add DNS\"></td>";
	echo "</form>";
	echo "<td><form method=\"post\" action=\"rel_network_dns.php\" onsubmit=\"return confirm('Are you sure you want to Clear this Network?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"clear\">";
	echo "<input type=\"hidden\" name=\"network_id\" value=\"".$network['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Clear Network\">";
	echo "</form></td>";
	
	echo "</tr>";
}


?>
</tbody>
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
