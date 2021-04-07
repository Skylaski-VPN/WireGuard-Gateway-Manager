#!/usr/bin/python3
#
#
#	Setup DNS Provider for WGM
#
#	- Get Zones
#	- Get Records

#	Usage:
# 	./setup_dns.py <provider_id> <provider_type> <command>

# WGM CloudFlare integration
import cf
# WGM DB Integration
import wgm_db
# Other dependencies
import sys
import psycopg2.extras

def test_dns_auth(provider_id,auth_token):
	status={"command":"test_dns_auth","status":False,"message":"FALSE"}
	
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_provider_sql = "SELECT * FROM dns_providers WHERE id=%s;" % provider_id
	
	# query db for provider
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status
	
	provider_type = get_provider_row['type']
	#print("Provider Type: "+provider_type)
	
	if provider_type == "1":
		#print("Cloudflare DNS Provider")
		
		result = cf.test_cf_auth(auth_token)
		if result['success'] != True:
			status['message']="Not an active Cloudflare token"
			return status
		else:
			if result['result']['status'] != "active":
				status['message']="Not an active Cloudflare token"
				return status
				
	else:
		status['message']="Unsupported DNS Provider"
		return status

	status['status']=True
	status['message']="Success"
	
	return status

def setup_dns_zones(provider_id):
	status={"command":"setup_dns_zones","status":False,"message":"FALSE"}
	
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_provider_sql = "SELECT * FROM dns_providers WHERE id=%s;" % provider_id
	
	# query db for provider
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status
	
	provider_type = get_provider_row['type']
	#print("Provider Type: "+provider_type)
	
	if provider_type == "1":
		#print("Cloudflare Provider")
		current_zones = list()
		# Get current zones
		get_cur_zones_sql = "SELECT * FROM dns_zones WHERE provider="+str(provider_id)
		cur.execute(get_cur_zones_sql)
		get_cur_zones_ret = cur.fetchall()
		#print(len(get_cur_zones_ret))
		for cur_zone in get_cur_zones_ret:
			#print(cur_zone['name'])
			current_zones.append(cur_zone['value'])
		
		#print(current_zones)
		
		get_auth_sql = "SELECT auth_key0 FROM dns_auth_configs WHERE provider=%s" % provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Can't get Auth Token for Provider"
			return status
			
		# Auth Acquired
		auth_token = get_auth_row['auth_key0']
		# Get Zones from DNS Provider
		zones = cf.get_dns_zones(auth_token)
		
		# Update with New Zones Only
		new_zones = list()
		for zone in zones:
			new_zones.append(zone['id'])
			if zone['id'] not in current_zones:
				uid = wgm_db.get_unique_id(64,'dns_zones',db_conn)
				#print("Found Zone: "+zone['name'])
				insert_zone_sql = "INSERT INTO dns_zones (name, value, unique_id, provider) VALUES(%s, %s, %s, %s)"
				cur.execute(insert_zone_sql,(zone['name'], zone['id'], uid, provider_id))
				db_conn.commit()
		
		# Cleanup Old Zones
		for zone in current_zones:
			if zone not in new_zones:
				print("Old Zone")
				get_zone_id_sql = "SELECT id FROM dns_zones WHERE value='"+zone+"'"
				cur.execute(get_zone_id_sql)
				get_zone_id_ret = cur.fetchone()
				zid = get_zone_id_ret['id']
				delete_zone_records_sql = "DELETE FROM dns_zone_records WHERE zone="+str(zid)
				cur.execute(delete_zone_records_sql)
				db_conn.commit()
				cur.execute("DELETE FROM dns_zones WHERE id="+str(zid))
				db_conn.commit()
		
	else:
		status['message']="Provider type unsupported"
		return status
	
	
	status['status']=True
	status['message']="Success"
	
	return status
	
	
def setup_dns_records(provider_id):
	status={"command":"setup_dns_records","status":False,"message":"FALSE"}
	
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_provider_sql = "SELECT * FROM dns_providers WHERE id=%s;" % provider_id
	
	# query db for provider
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status
	
	provider_type = get_provider_row['type']
	#print("Provider Type: "+provider_type)
	
	if provider_type == "1":
		#print("Cloudflare DNS Provider")
		# get auth_token
		get_auth_sql = "SELECT auth_key0 FROM dns_auth_configs WHERE provider="+str(provider_id)
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Can't get Auth Token for Provider"
			return status
			
		# Auth Acquired
		auth_token = get_auth_row['auth_key0']
		
		# foreach provider zone, update A records
		get_zones_sql = "SELECT id, value FROM dns_zones WHERE provider="+str(provider_id)
		cur.execute(get_zones_sql)
		#print("Getting Records")
		get_zones_result = cur.fetchall()
		# Foreach zone, get records
		for res in get_zones_result:
			#print("Zone: "+res['value'])
			#print("ID: "+str(res['id']))
			zone_id=str(res['id'])

			# Delete all records for current zone, the unique ids from the provider should remain the same so this doesn't impact current configured servers
			delete_zone_records_sql = "DELETE FROM dns_zone_records WHERE zone="+zone_id
			cur.execute(delete_zone_records_sql)
			db_conn.commit()

			zone_records  = cf.get_dns_records(res['value'],auth_token)
			# foreach record, make entry in DB
			for record in zone_records:
				uid = wgm_db.get_unique_id(64,'dns_zone_records',db_conn)
				insert_records_sql = "INSERT INTO dns_zone_records (name, type, content, zone, ttl, unique_id, provider_uid) VALUES (%s, %s, %s, %s, %s, %s, %s)"
				cur.execute(insert_records_sql,(record['name'], record['type'], record['content'], res['id'], record['ttl'], uid, record['id']))
				db_conn.commit()
	
	status['status']=True
	status['message']="Success"
	return status
