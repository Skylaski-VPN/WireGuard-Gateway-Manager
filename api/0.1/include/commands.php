<?php
#	Commands the API may use
#
#
#

function deleteClient($local_uid, $wgm_db, $PATH_TO_CLI){
	
	if(!isset($local_uid)){
		error_log("user api: delete_client: Must provide local_uid");
		return false;
	}
	$get_client_config_sql = "SELECT * FROM clients WHERE local_uid='".pg_escape_string($local_uid)."'";
	//error_log("Looking for client with: ".$get_client_config_sql);
	$get_client_config_ret = pg_query($wgm_db,$get_client_config_sql);
	if(!$get_client_config_ret){
		error_log("user api: delete_client: DB Error: ".pg_last_error($wgm_db));
		return false;
	}
	$get_client = pg_fetch_assoc($get_client_config_ret);
	// Does client exist?
	if(!isset($get_client['unique_id'])){
		error_log("api: delete_client: Client Does Not Exist");
		return false;
	}

	$output = shell_exec($PATH_TO_CLI."wgmcontroller.py detach_client ".$get_client['id']);
	
	$delete_ipv4_lease_sql = "DELETE FROM ipv4_client_leases WHERE client_id=".pg_escape_string($get_client['id']);
	$delete_ipv4_lease_ret = pg_query($wgm_db,$delete_ipv4_lease_sql);
	if($delete_ipv4_lease_ret){
		
		$delete_ipv6_lease_sql = "DELETE FROM ipv6_client_leases WHERE client_id=".pg_escape_string($get_client['id']);
		$delete_ipv6_lease_ret = pg_query($wgm_db,$delete_ipv6_lease_sql);
		if($delete_ipv6_lease_ret){
			
			$detach_client_sql = "DELETE FROM rel_client_loc WHERE client_id=".pg_escape_string($get_client['id']);
			$detach_client_ret = pg_query($wgm_db,$detach_client_sql);
			if($detach_client_ret){
	
				$clear_client_config_sql = "UPDATE clients set ipv4_addr=NULL, ipv6_addr=NULL, gw_id=NULL, v4_mask=NULL, v6_mask=NULL, dns_ip=NULL, gw_key=NULL, gw_addr=NULL, gw_port=NULL, client_config=NULL WHERE id=".pg_escape_string($get_client['id']);
				$clear_client_config_ret = pg_query($wgm_db,$clear_client_config_sql);
				if($clear_client_config_ret){
					
					
					$detach_client_sql = "DELETE FROM rel_client_loc WHERE client_id=".pg_escape_string($get_client['id']);
					$detach_client_ret = pg_query($wgm_db,$detach_client_sql);
					if($detach_client_ret){
						$delete_client_sql = "DELETE FROM clients WHERE id=".pg_escape_string($get_client['id']);
						$delete_client_ret = pg_query($wgm_db, $delete_client_sql);
						if($delete_client_ret){
							return true;
						}
						else{
							return false;
						}
					}
					else{
						return false;
					}
					
					
					
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}

	}
	else{
		return false;
	}

	
	
}




?>
