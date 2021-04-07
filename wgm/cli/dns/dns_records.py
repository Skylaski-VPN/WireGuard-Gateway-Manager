#!/usr/bin/python3

# Manage DNS Records
#	Functions:
#	- Add
#	- Update
#	- Delete

# Usage:
# ./dns_records.py <zone_id> <command> [name] [type] [content] [ttl] [record_id]

# WGM CloudFlare integration
import cf
# WGM DB Integration
import wgm_db
# Other dependencies
import sys
import psycopg2.extras

def update_dns_record(zone_id,record_id,name,rtype,content,ttl):
	record_name = name
	record_type = rtype
	record_content = content
	record_ttl = ttl
	status = {"command":"update_dns_record","status":False,"message":"False"}
	
	# Should have everything we need. Let's find out what type of provider this is. 
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)

	#print("Zone id: %s" % zone_id)
	# Find out Zone specific information
	get_zone_sql = "SELECT * FROM dns_zones WHERE id="+zone_id
	cur.execute(get_zone_sql)
	get_zone_row = cur.fetchone()
	if get_zone_row is None:
		status['message']="Zone Not Found"
		return status

	# Setup Zone provider_uid
	zone_uid = get_zone_row['value']
	#print("Zone value: "+zone_uid)


	# Find out Provider Specific Information
	get_provider_sql = "SELECT p.type,p.id FROM dns_providers AS p JOIN dns_zones AS z ON z.provider=p.id WHERE z.id="+zone_id
	# query db for zone
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit digitalocean provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status

	# Set up provider variables
	provider_id = str(get_provider_row['id'])
	provider_type = get_provider_row['type']	

	# Find out Record UID
	get_record_sql = "SELECT provider_uid FROM dns_zone_records WHERE id="+record_id
	cur.execute(get_record_sql)
	get_record_row = cur.fetchone()
	if get_record_row is None:
		status['message']="Record has no uid"
		return status
	
	# Setup record variables
	record_uid = get_record_row['provider_uid']

	if provider_type == "1": ## UPDATE A RECORD FOR A CLOUDFLARE DNS PROVIDER TYPE
		#print("Cloudflare DNS Provider")
		#print("Unique Provider ID: "+provider_id)
		# Now we can get the auth_token
		get_auth_sql = "SELECT auth_key0 FROM dns_auth_configs WHERE provider="+provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Auth Token Unavailable"
			return status

		# Setup Auth Token
		auth_token = get_auth_row['auth_key0']

		success = cf.update_dns_record(zone_uid,record_uid,auth_token,record_name,record_type,record_content,record_ttl)

		if success == True:
			#print("---SUCCESS---")
			#print("Updating db")
			update_rec_sql = "UPDATE dns_zone_records SET name = %s, type = %s, content = %s, ttl = %s WHERE id = %s"
			cur.execute(update_rec_sql,(record_name,record_type,record_content,record_ttl,record_id))
			db_conn.commit()
			#print("DB Updated")

		else:
			status['message']="Failed to update DNS record"
			return status
	
	else:
		status['message']="Provider Type Unknown"
		return status

	status['status']=True
	status['message']="Success"
	
	return status
	

def delete_dns_record(zone_id,record_id):
	status = {"command":"delete_dns_record","status":False,"message":"False"}
	
	# Find out what kind of provider this is.
	# Should have everything we need. Let's find out what type of provider this is. 
	# Now check to make sure provider id is valid, die quickly otherwise. 
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)

	#print("Zone id: %s" % zone_id)
	# Find out Zone specific information
	get_zone_sql = "SELECT * FROM dns_zones WHERE id="+str(zone_id)
	cur.execute(get_zone_sql)
	get_zone_row = cur.fetchone()
	if get_zone_row is None:
		#print("Zone Not Found")
		status['message']="Zone Not Found"
		return status

	# Setup Zone provider_uid
	zone_uid = get_zone_row['value']
	#print("Zone value: "+zone_uid)

	# Find out Provider Specific Information
	get_provider_sql = "SELECT p.type,p.id FROM dns_providers AS p JOIN dns_zones AS z ON z.provider=p.id WHERE z.id="+str(zone_id)
	# query db for zone
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit digitalocean provider
	if get_provider_row is None:
		status['message']="Provider Does Not Exist"
		return status

	# Set up provider variables
	provider_id = str(get_provider_row['id'])
	provider_type = get_provider_row['type']

	# Find Record provider_uid
	get_record_sql = "SELECT provider_uid FROM dns_zone_records WHERE id="+str(record_id)
	cur.execute(get_record_sql)
	get_record_row = cur.fetchone()
	if get_record_row is None:
		status['message'] = "Provider uid does not exist"
		return status

	record_uid = get_record_row['provider_uid']


	if provider_type == "1": ## DELETE A RECORD FOR A CLOUDFLARE DNS PROVIDER TYPE
		#print("Cloudflare provider")
		#print("Unique Provider ID: "+provider_id)
		# Now we can get the auth_token
		get_auth_sql = "SELECT auth_key0 FROM dns_auth_configs WHERE provider="+str(provider_id)
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="Auth Token Unavailable"
			return status
		
		# Setup Auth Token
		auth_token = get_auth_row['auth_key0']

		# Deleting DNS Record
		#print("Deleting Record in Cloudflare")
		success = cf.delete_dns_record(zone_uid,record_uid,auth_token)

		if success == True:
			#print("---SUCCESS---")
			#print("Deleting from DB")
			delete_rec_sql = "DELETE FROM dns_zone_records WHERE id="+str(record_id)
			cur.execute(delete_rec_sql)
			db_conn.commit()
		else:
			status['Message']="Failed to delete DNS Record"
			return status
	
	else:
		status['message']="Unknown Provider"
		return status

				
	status['status']=True
	status['message']="Success"
	
	return status

def add_dns_record(zone_id,name,rtype,content,ttl):
	record_name = name
	record_type = rtype
	record_content = content
	record_ttl = ttl
	status = {"command":"add_dns_record","status":False,"message":"False","record_uid":""}

	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)

	print("Zone id: %s" % zone_id)
	# Find out Zone specific information
	get_zone_sql = "SELECT * FROM dns_zones WHERE id="+zone_id
	cur.execute(get_zone_sql)
	get_zone_row = cur.fetchone()
	if get_zone_row is None:
		#print("Zone Not Found")
		status['message'] = "Zone Not Found"
		return status

	# Setup Zone provider_uid
	zone_uid = get_zone_row['value']
	#print("Zone value: "+zone_uid)

	# Find out Provider Specific Information
	get_provider_sql = "SELECT p.type,p.id FROM dns_providers AS p JOIN dns_zones AS z ON z.provider=p.id WHERE z.id="+zone_id
	# query db for zone
	cur.execute(get_provider_sql)
	get_provider_row = cur.fetchone()
	# make sure we have a legit digitalocean provider
	if get_provider_row is None:
		#print("Provider Does Not Exist")
		status['message'] = "Provider Does Not Exist"
		return status

	# Set up provider variables
	provider_id = str(get_provider_row['id'])
	provider_type = get_provider_row['type']	

	if provider_type == "1": ## ADD A RECORD FOR A CLOUDFLARE DNS PROVIDER TYPE
		#print("Cloudflare DNS Provider")
		#print("Unique Provider ID: "+provider_id)
		# Now we can get the auth_token
		get_auth_sql = "SELECT auth_key0 FROM dns_auth_configs WHERE provider="+provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			#print("Auth Token Unavailable")
			status['message'] = "Auth Token Unavailable"
			return status

		# Setup Auth token
		auth_token = get_auth_row['auth_key0']

		# We should have everything we need now to create the record in Cloudflare
		#print("Creating Record in Cloudflare")
		record_uid = cf.add_dns_record(zone_uid, auth_token, record_name, record_type, record_content, record_ttl)
		if record_uid != "FALSE":
			#print("Successfully created record in Cloudflare with uid: "+record_uid)
			#print("---SUCCESS---")
			#print("Updating Database")
			new_unique_id = wgm_db.get_unique_id(64,'dns_zone_records',db_conn)
			sql = "INSERT INTO dns_zone_records (name, type, content, zone, ttl, unique_id, provider_uid) VALUES (%s, %s, %s, %s, %s, %s, %s)"
			cur.execute(sql,(record_name,record_type,record_content,zone_id,record_ttl,new_unique_id,record_uid))
			db_conn.commit()
			#print("DB Updated")
		else:
			#print("---FAILED---")
			status['message']="Failed to Create DNS Record"
			return status

	else:
		#print("Unknown Provider Type, Failing")
		status['message']="Unknown Provider Type"
		return status
	
	status['message']="Success"
	status['status']=True
	status['record_uid']=record_uid
	return status
