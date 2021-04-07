<html>
<head>
<title>WireGuard Gateway Manager - Attach/Detach GW Server</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
<style>
tr.bottom_border td{
	border-bottom: 1px solid black;
}
</style>
</head>
<body>

<h1>WireGuard Gateway Manager - Attach/Detach GW Server</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_gw_id_val="-";

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
	elseif($_POST['action'] == "update"){	// Attach a GW Server to a Network
		if($_POST['net_id'] && $_POST['loc_id'] && $_POST['gw_id']){
			if($_POST['gw_id'] != $default_gw_id_val){
				# Does a relationship already exist?
				$find_rels_sql = "SELECT count(gw_id) as count FROM rel_net_loc_gw WHERE gw_id=".pg_escape_string($_POST['gw_id']);
				$find_rels_ret = pg_query($find_rels_sql);
				if(($find_rels = pg_fetch_assoc($find_rels_ret))['count']){
					echo "<p>Server already attached, doing nothing</p>";
				}
				else{
					echo "<p>Adding GW Server to Location</p>";
					$attach_gw_sql = "INSERT INTO rel_net_loc_gw (net_id,loc_id,gw_id) VALUES(".pg_escape_string($_POST['net_id']).", ".pg_escape_string($_POST['loc_id']).", ".pg_escape_string($_POST['gw_id']).")";
					$attach_gw_ret = pg_query($attach_gw_sql);
					if($attach_gw_ret){
						echo "<p>Attached GW Server!</p>";
						echo "<p>Starting WireGuard...</p>";
						$output = shell_exec("./cli/wgmcontroller.py attach_gw ".$_POST['gw_id']." ".$_POST['loc_id']);
						echo "<pre>".$output."</pre>";
					}
					else{
						echo "<p>Failed to attach GW: ".pg_last_error($db)."</p>";
					}
				}
			}
			else{
				echo "<p>Please choose GW Server</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "clear"){	// Clear all GW Servers from Location
		if($_POST['loc_id']){
			echo "<p>Clearing Location</p>";
			echo "<p>Shutting Down WireGuard and Deleting Configs</p>";
			$get_servers_sql = "SELECT gw_id FROM rel_net_loc_gw WHERE loc_id=".pg_escape_string($_POST['loc_id']);
			$get_servers_ret = pg_query($db,$get_servers_sql);
			while($server = pg_fetch_assoc($get_servers_ret)){
				$output = shell_exec("./cli/wgmcontroller.py detach_gw ".$server['gw_id']." ".$_POST['loc_id']);
				echo "<pre>".$output."</pre>";
			}
			$clear_loc_sql = "DELETE FROM rel_net_loc_gw WHERE loc_id=".pg_escape_string($_POST['loc_id']);
			$clear_loc_ret = pg_query($clear_loc_sql);
			if($clear_loc_ret){
				echo "<p>Cleared Location</p>";
			}
			else{
				echo "<p>Failed to Clear Location</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "detach"){	// Detach GW Server from Location
		if($_POST['loc_id'] && $_POST['gw_id']){
			echo "<p>Detaching GW Server</p>";
			$detach_sql = "DELETE FROM rel_net_loc_gw WHERE loc_id=".pg_escape_string($_POST['loc_id'])." AND gw_id=".pg_escape_string($_POST['gw_id']);
			$detach_ret = pg_query($detach_sql);
			if($detach_ret){
				echo "<p>Detached GW Server</p>";
				echo "<p>Shutting down WireGuard and Deleting Config</p>";
				$output = shell_exec("./cli/wgmcontroller.py detach_gw ".$_POST['gw_id']." ".$_POST['loc_id']);
				echo "<pre>".$output."</pre>";
			}
			else{
				echo "<p>Failed to detach: ".pg_last_error($db)."</p>";
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
$locations_sql = "SELECT * FROM locations";
$locations_ret = pg_query($db, $locations_sql);
if(!$locations_ret){
	echo pg_last_error($db);
	exit;
}




?>



<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<th>ID</th><th>Network</th><th>Location</th><th>GW Server</th><th>Update</th><th>Clear</th>
</tr>
</thead>
<tbody>

<?php

while($locations = pg_fetch_assoc($locations_ret)){
	echo "<tr class=\"bottom_border\">";
	echo "<td>".$locations['id']."</td>";
	echo "<td>"; 
	// Get Network Name
	$get_network_sql = "SELECT network_name FROM networks WHERE id=".pg_escape_string($locations['network_id']);
	$get_network_ret = pg_query($db,$get_network_sql);
	if($get_network = pg_fetch_assoc($get_network_ret)){
		echo "<p>".$get_network['network_name']."</p>";
	}
	else{
		echo "<p>Failed to get Network: ".pg_last_error($db)."</p>";
	}
	echo "</td>";
	echo "<td>".$locations['name']."</td>";
	
	echo "<td>";
	echo "<ul>";
	
	//discover GW Relationships
	$loc_id = $locations['id'];
	$discover_gw_rel_sql=<<<EOF
	SELECT loc.id as loc_id, gw.id as gw_id, gw.name as gw_name 
	FROM locations loc
		JOIN rel_net_loc_gw rel ON loc.id = rel.loc_id
		JOIN gw_servers gw ON rel.gw_id = gw.id
	WHERE loc.id=$loc_id
EOF;
	$discover_gw_rel_ret = pg_query($discover_gw_rel_sql);
	while($discovered_gw = pg_fetch_assoc($discover_gw_rel_ret)){
		
		echo "<form method=\"post\" action=\"rel_net_loc_gw.php\" onsubmit=\"return confirm('Are you sure you want to remove this GW Server?');\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"detach\">";
		echo "<input type=\"hidden\" name=\"loc_id\" value=\"".$discovered_gw['loc_id']."\">";
		echo "<input type=\"hidden\" name=\"gw_id\" value=\"".$discovered_gw['gw_id']."\">";
		echo "<li>".$discovered_gw['gw_name']." <input type=\"submit\" name=\"submit\" value=\"X\"></li>";
		echo "</form>";
	}
	
	// Discover GW Server's we can add
	echo "<li>";
	echo "<form action=\"rel_net_loc_gw.php\" method=\"post\" onsubmit=\"return confirm('Are you sure you want to Update this Location?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"net_id\" value=\"".$locations['network_id']."\">";
	echo "<input type=\"hidden\" name=\"loc_id\" value=\"".$locations['id']."\">";
	echo "<select name=\"gw_id\" id=\"".$locations['id']."_dns\">";
	
	
	echo "<option value=\"".$default_gw_id_val."\">".$default_gw_id_val."</option>";
	$discover_gw_servers_sql = "SELECT * FROM gw_servers";
	$discover_gw_servers_ret = pg_query($db,$discover_gw_servers_sql);
	if($discover_gw_servers_ret){
		while($gw = pg_fetch_assoc($discover_gw_servers_ret)){
			echo "<option value=\"".$gw['id']."\">".$gw['name']."</option>";
		}
	}
	else{
		echo "<p>No GW Servers Found: ".pg_last_error($db)."</p>";
	}
	
	echo "</select>";
	echo "</li>";
	echo "</ul>";
	echo "</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Add GW\"></td>";
	echo "</form>";
	echo "<td><form method=\"post\" action=\"rel_net_loc_gw.php\" onsubmit=\"return confirm('Are you sure you want to Clear this Location?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"clear\">";
	echo "<input type=\"hidden\" name=\"loc_id\" value=\"".$locations['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Clear Location\">";
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
