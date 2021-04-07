<html>
<head>
<title>WireGuard Gateway Manager - DNS Zone Records</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - DNS Zone Records</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_record_name_val="Record Name";
$default_dns_record_type_val="-";
$default_dns_record_content_val="-";
$default_dns_record_ttl_val="0";

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
	elseif($_POST['action'] == "add"){	// Add a DNS Record
		if($_POST['record_name'] && $_POST['record_type'] && $_POST['record_content'] && $_POST['record_zone'] && isset($_POST['record_ttl'])){
			if($_POST['record_name'] != $default_dns_record_name_val && $_POST['record_type'] != $default_dns_record_type_val && $_POST['record_content'] != $default_dns_record_content_val && $_POST['record_ttl'] != $default_dns_record_ttl_val){
				if($_POST['record_type'] == "A"){
					if(filter_var($_POST['record_content'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($_POST['record_content'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
						if(is_valid_domain_name($_POST['record_name']) == 1){
							echo "<p>Adding DNS Record</p>";
							$output = shell_exec("./cli/iaas.py dns_record add ".$_POST['record_zone']." ".$_POST['record_name']." ".$_POST['record_type']." ".$_POST['record_content']." ".$_POST['record_ttl']."");
							echo "<pre>".$output."</pre>";
						}
						else{
							echo "<p>Invalid Domain Name</p>";
						}
					}
					else{
						echo "<p>Content must be valid IP Address</p>";
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
	elseif($_POST['action'] == "update"){	// Update a DNS Record
		if($_POST['record_id'] && $_POST['record_name'] && $_POST['record_type'] && $_POST['record_content'] && $_POST['record_zone'] && isset($_POST['record_ttl'])){
			if($_POST['record_name'] != $default_dns_record_name_val && $_POST['record_type'] != $default_dns_record_type_val && $_POST['record_content'] != $default_dns_record_content_val && $_POST['record_ttl'] != $default_dns_record_ttl_val){
				if(filter_var($_POST['record_content'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var($_POST['record_content'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
					if(is_valid_domain_name($_POST['record_name']) == 1){
						echo "<p>Updating DNS Record</p>";
						$output = shell_exec("./cli/iaas.py dns_record update ".$_POST['record_zone']." ".$_POST['record_id']." ".$_POST['record_name']." ".$_POST['record_type']." ".$_POST['record_content']." ".$_POST['record_ttl']);
						echo "<pre>".$output."</pre>";
					}
					else{
						echo "<p>Invalid Domain Name</p>";
					}
				}
				else{
					echo "<p>Content must be valid IP Address</p>";
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
	elseif($_POST['action'] == "delete"){	// Delete a DNS Record
		if($_POST['record_id'] && $_POST['zone_id']){
			echo "<p>Deleting DNS Record</p>";
			$output = shell_exec("./cli/iaas.py dns_record delete ".$_POST['zone_id']." ".$_POST['record_id']);
			echo "<pre>".$output."</pre>";
			
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}

// Pull the DNS Records Table for Printing
$sql = "SELECT * FROM dns_zone_records";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert DNS Records 
<table>
	<thead>
<tr><th>Name</th><th>Type</th><th>Content</th><th>Zone</th><th>ttl</th></tr>
</thead>
<tr>
<form method="post" action="dns_zone_records.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="record_name" value="<?php echo $default_dns_record_name_val; ?>"></td>
	<td><select name="record_type" id="insert_record_type">
<?php 
//Discover DNS Record Types
$discover_record_type_sql = "SELECT * FROM dns_zone_record_types";
$discover_record_type_ret = pg_query($db,$discover_record_type_sql);
if($discover_record_type_ret){
	while($type = pg_fetch_assoc($discover_record_type_ret)){
		echo "<option value=\"".$type['value']."\">".$type['value']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

echo $default_dns_record_type_val; 

?>"
	</select>
	</td>
	<td><input type="text" name="record_content" value="<?php echo $default_dns_record_content_val; ?>"></td>
	<td><select name="record_zone" id="insert_record_zone">
<?php
// Discover DNS Zones
$discover_zone_sql = "SELECT * FROM dns_zones";
$discover_zone_ret = pg_query($db,$discover_zone_sql);
if($discover_zone_ret){
	while($dns = pg_fetch_assoc($discover_zone_ret)){
		echo "<option value=\"".$dns['id']."\">".$dns['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>
	</select>
	</td>
	
	<td><input type="text" name="record_ttl" value="<?php echo $default_dns_record_ttl_val; ?>"></td>
			
	<td><input type="submit" name="submit" value="Add DNS Record"></td>
</form>
</tr>
</table>
-->
<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<?php
//dynamically print field names
for ($i = 0; $i < $num_fields; $i++) {
	if(pg_field_name($ret,$i) != "unique_id" && pg_field_name($ret,$i) != "provider_uid"){
		echo "<th>".pg_field_name($ret,$i)."</th>";
	}
}
?>

<!-- <th>Insert/Modify</th><th>Or Delete</th> -->
</tr>
</thead>

<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"dns_zone_records.php\" onsubmit=\"return confirm('Are you sure you want to Update this DNS Record?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"record_zone\" value=\"".$row['zone']."\">";
	echo "<input type=\"hidden\" name=\"record_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"record_name\" value=\"".$row['name']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"record_type\" value=\"".$row['type']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"record_content\" value=\"".$row['content']."\"></td>";	
	echo "<td>";
	// Discover DNS Zones
	$discover_dns_sql = "SELECT * FROM dns_zones WHERE id='".$row['zone']."'";
	#echo $discover_dns_sql;
	
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
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"record_ttl\" value=\"".$row['ttl']."\"></td>";	
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	# Code for updating and deleting DNS records
	/*
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update DNS Record\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"dns_zone_records.php\" onsubmit=\"return confirm('Are you sure you want to Delete this DNS Record?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"record_id\" value=\"".$row['id']."\">";
	echo "<input type=\"hidden\" name=\"zone_id\" value=\"".$row['zone']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete DNS Record\" >";
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
