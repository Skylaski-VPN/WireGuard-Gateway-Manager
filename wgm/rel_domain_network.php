<html>
<head>
<title>WireGuard Gateway Manager - Attach/Detach Network</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Attach/Detach Network</h1>

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
if($_SERVER['REQUEST_METHOD'] == "POST"){
	if(empty($_POST['action'])){
		echo "<p>action is required</p>";
	}
	elseif($_POST['action'] == "update"){	// Attach a Network to a Domain
		if($_POST['domain_id'] && $_POST['network_id']){
			if($_POST['network_id'] != "-"){
				// First Delete current relationships (there can only be 1) this can fail because there might not be a relationship yet
				$delete_cur_sql = "DELETE FROM rel_domain_network WHERE domain_id=".pg_escape_string($_POST['domain_id']);
				$delete_cur_ret = pg_query($db,$delete_cur_sql);
				// Now insert the new relationship
				$insert_rel_sql = "INSERT INTO rel_domain_network (domain_id,network_id) VALUES (".pg_escape_string($_POST['domain_id']).", ".$_POST['network_id'].")";
				$insert_rel_ret = pg_query($db, $insert_rel_sql);
				if(!$insert_rel_ret){
					echo "<p>Failed to update Domain: ".pg_last_error($db)."</p>";
				}
				else{
					echo "<p>Updated Domain</p>";
				}
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "clear"){	// Detach a Network from a domain
		if($_POST['domain_id']){
			// Clear Domain from rel_domain_network table
			$delete_domain_rel_sql = "DELETE FROM rel_domain_network WHERE domain_id=".pg_escape_string($_POST['domain_id']);
			$delete_domain_rel_ret = pg_query($db, $delete_domain_rel_sql);
			if(!$delete_domain_rel_ret){
				echo "<p>Failed to Clear Domain: ".pg_last_error($db)."</p>";
			}
			else{
				echo "<p>Cleared Network From Domain</p>";
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
$domains_sql = "SELECT * FROM domains";
$domains_ret = pg_query($db, $domains_sql);
if(!$domains_ret){
	echo pg_last_error($db);
	exit;
}




?>



<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<th>ID</th><th>Domain</th><th>Network</th><th>Update</th><th>Clear</th>
</tr>
</thead>
<tbody>

<?php

while($domain = pg_fetch_assoc($domains_ret)){
	echo "<tr>";
	echo "<form action=\"rel_domain_network.php\" method=\"post\" onsubmit=\"return confirm('Are you sure you want to Update this Domain?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"domain_id\" value=\"".$domain['id']."\">";
	echo "<td>".$domain['id']."</td>";
	echo "<td>".$domain['name']."</td>";

	echo "<td>";
	echo "<select name=\"network_id\" id=\"".$domain['id']."_network_id\">";
	//echo "<option value=\"-\">-</option>";
	// Pull the Networks for Printing
	$networks_sql = "SELECT * FROM networks";
	$networks_ret = pg_query($db, $networks_sql);
	if(!$networks_ret){
		echo pg_last_error($db);
		exit;
	}
	while($network = pg_fetch_assoc($networks_ret)){
		echo "<option value=\"".$network['id']."\">".$network['network_name']."</option>";
	}
	echo "</select>";
	// Find out if Domain already has a Network
	$discover_domain_network_sql = "SELECT network_id FROM rel_domain_network WHERE domain_id=".$domain['id'];
	$discover_domain_network_ret = pg_query($db, $discover_domain_network_sql);
	if($discover_domain_network_ret){
		$discover_row = pg_fetch_assoc($discover_domain_network_ret);
		if(isset($domain['id'])){
			echo "<script>setValue('".$domain['id']."_network_id', '".$discover_row['network_id']."')</script>";
		}
	}
	else{
		//echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Domain\"></td>";
	echo "</form>";
	echo "<td><form method=\"post\" action=\"rel_domain_network.php\" onsubmit=\"return confirm('Are you sure you want to Clear this Domain?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"clear\">";
	echo "<input type=\"hidden\" name=\"domain_id\" value=\"".$domain['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Clear Domain\">";
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
