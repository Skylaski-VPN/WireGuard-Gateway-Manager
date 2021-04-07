#!/usr/bin/python3

# Create & Destroy IaaS Servers

# WGM Digitalocean integration
import do
# WGM DB Integration
import wgm_db
# DNS
import dns
# Other dependencies
import sys
import psycopg2.extras
import sshpubkeys

def create_gw_server(name,dns_zone,provider_id,provider_image,provider_zone):
	status={"command":"create_gw_server","status":False,"message":"FALSE"}
	vm_exists = False
	
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_provider_sql = "SELECT * FROM iaas_providers WHERE id="+provider_id
	
	# query db for provider
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit digitalocean provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status
		
	provider_type = get_provider_row['type']
	
	# Now check to make sure dns_zone is valid, die quickly otherwise
	get_dns_zone_sql = "SELECT * FROM dns_zones WHERE id="+dns_zone
	cur.execute(get_dns_zone_sql)
	get_dns_zone_ret = cur.fetchone()
	if get_dns_zone_ret is None:
		status['message']="DNS Zone Does Not Exist"
		return status
		
	# Now check to makesure the dns_provider is valid, die quickly otherwise
	get_dns_provider_sql = "SELECT * FROM dns_providers WHERE id="+str(get_dns_zone_ret['provider'])
	cur.execute(get_dns_provider_sql)
	get_dns_provider_ret = cur.fetchone()
	if get_dns_provider_ret is None:
		status['message']="DNS Provider Does Not Exist"
		return status
	
	dns_provider_id = str(get_dns_zone_ret['provider'])
	dns_provider_type = get_dns_provider_ret['type']
		
	# Now check to make sure provider image is valid, die quickly otherwise.
	get_image_sql = "SELECT * FROM iaas_vm_images WHERE provider=%s AND id=%s"
	cur.execute(get_image_sql,(provider_id, provider_image))
	get_image_row = cur.fetchone()
	if get_image_row is None:
		status['message']="Image does not exist"
		return status
		
	image_value = get_image_row['value']
	
	# Now check to make sure provider zone is valid, die quickly otherwise.
	get_zone_sql = "SELECT * FROM iaas_zones WHERE id=%s AND provider=%s"
	cur.execute(get_zone_sql,(provider_zone,provider_id))
	get_zone_row = cur.fetchone()
	if get_zone_row is None:
		status['message']="Image does not exist"
		return status
		
	zone_value = get_zone_row['value']
	
	if provider_type == 1:
		print("DigitalOcean IaaS Provider")
		#print("DigitalOcean IaaS Provider")
		# THERE ARE SOME DEFAULTS ASSUMED AT THE MOMENT
		ipv6 = True
		backups = False
		size = "s-1vcpu-2gb"
		tags = ["wgm","wg","vpn"]
		
		# Get Auth token
		get_auth_sql = "SELECT * FROM iaas_auth_configs WHERE provider="+provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Unable to get Auth Token"
			return status
		
		auth_token = get_auth_row['auth_key0']
		
		db_keys = [get_auth_row['ssh_key0'],get_auth_row['ssh_key1'],get_auth_row['ssh_key2']]
		config_keys = []		
		for key in db_keys:
			if key != "-":
				ssh = sshpubkeys.SSHKey(key)
				config_keys.append(ssh.hash_md5().replace('MD5:',''))
		
		config = {"token":auth_token,
			"name":name,
			"region":zone_value,
			"image":image_value,
			"size":size,
			"ssh_keys":config_keys,
			"tags":tags,
			"ipv6":ipv6,
			"backups":ipv6
			}
		#print(config)
		
		# Build Server
		#print("Deploying GW Server")
		result = do.create_do_droplet(config)
		server = result['droplet']
		if result['droplet'] is None:
			status['message']=result['message']
			return status
		
	else:
		status['message']="Unsupported IaaS Provider"
		return status
	
	# Server is deployed, let's setup DNS
	if dns_provider_type == "1":
		print("Cloudflare DNS Provider")
		# Some Defaults are Assumed
		rtype = "A"
		ttl = "120"
		
		#def add_dns_record(zone_id,name,rtype,content,ttl)
		record_status = dns.add_dns_record(dns_zone,name,rtype,server.ip_address,ttl)
		if record_status['status'] == False:
			status['message']="Failed to Create DNS Record"
			# Failed to create the DNS Record, let's destroy the droplet
			result = do.destroy_do_droplet(str(server.id),auth_token)
			if result == False:
				status['message']="Failed to create the DNS Record, Also Failed to Destroy DigitalOcean Droplet"
				return status
			
		#print(record_status['record_uid'])
		
	else:
		status['message']="Unsupported DNS Provider"
		
	# Okay, server was successfully deployed and the DNS record was successfully created. Let's update the DB
	new_unique_id = wgm_db.get_unique_id(64,'gw_servers',db_conn)
	new_gw_server_sql = "INSERT INTO gw_servers (name,pub_ipv4_addr,pub_ipv6_addr,dns_record_uid,provider,provider_uid,dns_zone, unique_id, dns_provider) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
	cur.execute(new_gw_server_sql,(name,server.ip_address,server.ip_v6_address,record_status['record_uid'],provider_id,server.id,dns_zone,new_unique_id,dns_provider_id))
	db_conn.commit()
	
	status['message']="Success"
	status['status']=True
	
	return status
	
def destroy_gw_server(server_id):
	status={"commad":"destroy_gw_server","status":False,"message":"FALSE"}
	
	# Get the server information
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	get_server_sql = "SELECT * FROM gw_servers WHERE id="+server_id
	cur.execute(get_server_sql)
	get_server_ret = cur.fetchone()
	if get_server_ret is None:
		status['message']="Failed to find GW Server"
		return status
		
	# Delete the Server in IaaS
	# Get Provider Type
	get_provider_sql = "SELECT * FROM iaas_providers WHERE id="+str(get_server_ret['provider'])
	cur.execute(get_provider_sql)
	get_provider_ret = cur.fetchone()
	if get_provider_ret is None:
		status['message']="Failed to find IaaS Provider"
		return status
		
	provider_type = get_provider_ret['type']
	
	if provider_type == 1:
		print("DigitalOcean Provider")
		# get Auth
		get_auth_sql = "SELECT auth_key0 FROM iaas_auth_configs WHERE provider="+str(get_server_ret['provider'])
		cur.execute(get_auth_sql)
		get_auth_ret = cur.fetchone()
		if get_auth_ret is None:
			status['message']="Failed to get Auth Token"
		
		auth_token = get_auth_ret['auth_key0']
		
		# Delete DO VM
		result = do.destroy_do_droplet(get_server_ret['provider_uid'],auth_token)
		print(result)
		if result is False:
			status['message']="Failed to destroy DigitalOcean Droplet"
			return status
		
	else:
		status['message']="Unsupported IaaS Provider"
		return status
	
	# Delete the DNS record
	# Get record id
	get_dns_record_sql = "SELECT * FROM dns_zone_records WHERE provider_uid='"+get_server_ret['dns_record_uid']+"' AND zone="+str(get_server_ret['dns_zone'])
	cur.execute(get_dns_record_sql)
	get_dns_record_ret = cur.fetchone()
	if get_dns_record_ret is None:
		status['message']="Failed to get DNS Record"
		return status
		
	
	
	dns_result = dns.delete_dns_record(get_dns_record_ret['zone'],get_dns_record_ret['id'])
	print(dns_result)
	if dns_result['status'] is False:
		status['message']="Failed to delete DNS Record"
		return status
	
	
	# VM and DNS record removed Update DB
	delete_server_sql = "DELETE FROM gw_servers WHERE id="+server_id
	cur.execute(delete_server_sql)
	db_conn.commit()
	
	
	status['message']="Success"
	status['status']=True
	
	return status

def create_dns_server(name,dns_type,provider_id,provider_image,provider_zone):
	status={"status":False,"message":"FALSE"}
	
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_provider_sql = "SELECT * FROM iaas_providers WHERE id="+provider_id
	
	# query db for provider
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit digitalocean provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status
		
	provider_type = get_provider_row['type']
	
	# Now check to make sure provider image is valid, die quickly otherwise.
	get_image_sql = "SELECT * FROM iaas_vm_images WHERE provider=%s AND id=%s"
	cur.execute(get_image_sql,(provider_id, provider_image))
	get_image_row = cur.fetchone()
	if get_image_row is None:
		status['message']="Image does not exist"
		return status
		
	image_value = get_image_row['value']
	
	# Now check to make sure provider zone is valid, die quickly otherwise.
	get_zone_sql = "SELECT * FROM iaas_zones WHERE id=%s AND provider=%s"
	cur.execute(get_zone_sql,(provider_zone,provider_id))
	get_zone_row = cur.fetchone()
	if get_zone_row is None:
		status['message']="Image does not exist"
		return status
		
	zone_value = get_zone_row['value']
	
	if provider_type == 1:
		#print("DigitalOcean IaaS Provider")
		# THERE ARE SOME DEFAULTS ASSUMED AT THE MOMENT
		ipv6 = True
		backups = False
		size = "s-1vcpu-2gb"
		tags = ["wgm","dns"]
		
		# Get Auth token
		get_auth_sql = "SELECT * FROM iaas_auth_configs WHERE provider="+provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Unable to get Auth Token"
			return status
		
		auth_token = get_auth_row['auth_key0']
		
		db_keys = [get_auth_row['ssh_key0'],get_auth_row['ssh_key1'],get_auth_row['ssh_key2']]
		config_keys = []		
		for key in db_keys:
			if key != "-":
				ssh = sshpubkeys.SSHKey(key)
				config_keys.append(ssh.hash_md5().replace('MD5:',''))
		
		config = {"token":auth_token,
			"name":name,
			"region":zone_value,
			"image":image_value,
			"size":size,
			"ssh_keys":config_keys,
			"tags":tags,
			"ipv6":ipv6,
			"backups":ipv6
			}

		#print(config)
		print("Deploying DNS Server")
		result = do.create_do_droplet(config)
		server = result['droplet']
		if server is None:
			status['message']=result['message']
			return status
				
	else:
		print("Unknown IaaS Provider")
		status['message']="Unknown IaaS Provider"
		return status
		
		
	# Update DB
	new_unique_id = wgm_db.get_unique_id(64,'dns_servers',db_conn)
	new_dns_server_sql = "INSERT INTO dns_servers (dns_name,type,ipv4_addr,ipv6_addr,unique_id,provider,provider_uid) VALUES (%s, %s, %s, %s, %s, %s, %s)"
	cur.execute(new_dns_server_sql,(name,dns_type,server.ip_address,server.ip_v6_address,new_unique_id,provider_id,str(server.id)))
	db_conn.commit()
	
	status['status']=True
	status['message']="Success"
	return status
	
	
def destroy_dns_server(dns_server_id):
	status={"status":False,"message":"FALSE"}
	
	# Make sure this is a valid server_id
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	get_dns_server_sql = "SELECT * FROM dns_servers WHERE id="+dns_server_id
	cur.execute(get_dns_server_sql)
	get_dns_server_row = cur.fetchone()
	if get_dns_server_row is None:
		status['message']="Invalid DNS Server ID"
		return status
	
	provider_id = get_dns_server_row['provider']
	provider_uid = get_dns_server_row['provider_uid']
	
	print("Provider: "+str(provider_id))
	print("Provider UID: "+provider_uid)
	
	# Now let's make sure this is a legit provider and get type
	get_provider_sql = "SELECT * FROM iaas_providers WHERE id="+str(provider_id)
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	if get_provider_row is None:
		status['message'] = "Provider is invalid"
		return status
	
	provider_type = get_provider_row['type']
	
	print("Provider Type: "+str(provider_type))
	
	if provider_type == 1:
		print("DigitalOcean DNS Server")
		# Get Auth Token
		get_auth_sql = "SELECT auth_key0 FROM iaas_auth_configs WHERE provider="+str(provider_id)
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Failed to get Auth Token"
			return status
			
		auth_token = get_auth_row['auth_key0']
		
		result = do.destroy_do_droplet(provider_uid,auth_token)
		if result == False:
			status['message']="Failed to destroy DNS Server"
			return status
	
	else:
		status['message']="Unknown IaaS Provider"
		return status
		
	# Update DB
	delete_dns_sql = "DELETE FROM dns_servers WHERE id="+str(dns_server_id)
	cur.execute(delete_dns_sql)
	db_conn.commit()
	
	status['status']=True
	status['message']="Success"
	return status
