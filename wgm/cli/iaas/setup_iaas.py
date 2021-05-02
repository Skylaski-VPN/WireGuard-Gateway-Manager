#!/usr/bin/python3

# Setup IaaS Provider
#
#	This script is for setting up and IaaS provider in WGM.
#	It takes 2 arguments, provider id and type. 
#	Once ID is verified, based on provider type we leverage certain other libraries to setup the specific provider 
#		Digitalocean, AWS, Google, Azure, ect. 

# Usage:
# ./setup_iaas.py <provider_id> <provider_type> <command>

# WGM Digitalocean integration
import do
# WGM DB Integration
import wgm_db
# Other dependencies
import sys
import psycopg2.extras

def test_iaas_auth(provider_id,auth_token):
	status={"command":"test_iaas_auth","status":False,"message":"FALSE",'provider_id':provider_id}
	
	# Verify Provider
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

	if provider_type == 1:
		# DigitalOcean Provider. Test token.
		regions = do.get_do_regions(provider_id,auth_token)
		if regions is False:
			status['message']="Unable to get DigitalOcean regions"
			return status
	
	status['message']="Success"
	status['status']=True
	
	return status

def setup_iaas_images(provider_id):
	status={"command":"setup_iaas_images","status":False,"message":"FALSE",'provider_id':provider_id}
	
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
	
	if provider_type == 1:
		#print("DigitalOcean IaaS Provider")
		
		# provider is digitalocean type, let's get the token
		get_auth_sql = "SELECT auth_key0 FROM iaas_auth_configs WHERE provider=%s" % provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="No Auth Token Found"
			return status
		auth_token = get_auth_row['auth_key0']
		
		# Get current images
		current_images = dict()
		get_cur_images_sql = "SELECT * FROM iaas_vm_images"
		cur.execute(get_cur_images_sql)
		get_cur_images_ret = cur.fetchall()
		for image in get_cur_images_ret:
			current_images[image['id']]=image['value']
			#print(image['value'])
		
		#print(current_images)
			
		# Let's get the relevant available images and update the database
		new_images = do.get_do_images(provider_id, auth_token)
		new_image_values = list()
		for image in new_images:
			new_image_values.append(str(image.id))
			matching_record=False
			for key in current_images:
				if current_images[key] == str(image.id):
					matching_record=True
					# This is an image we already know about. Update name
					#print("Image Name: "+image.name)
					#print("Record ID: "+str(key))
					update_image_sql = "UPDATE iaas_vm_images SET name = %s WHERE id=%s"
					cur.execute(update_image_sql,(image.name,key))
					db_conn.commit()
			if matching_record is False:
				# No matching record currently, make a new one
				new_uid = wgm_db.get_unique_id(64,'iaas_vm_images',db_conn)
				new_image_sql = "INSERT INTO iaas_vm_images (name,value,unique_id,provider) VALUES (%s, %s, %s, %s)"
				cur.execute(new_image_sql,(image.name,str(image.id),new_uid,provider_id))
				db_conn.commit()
		
		# Now let's cleanup the old images
		for key in current_images:
			if current_images[key] not in new_image_values:
				# Old image, delete it
				delete_image_sql = "DELETE FROM iaas_vm_images WHERE id="+str(key)
				cur.execute(delete_image_sql)
				db_conn.commit()
		
						
	status['status']=True
	status['message']="Success"
	return status
	
	
	
def setup_iaas_zones(provider_id):
	status={"command":"setup_iaas_zones","status":False,"message":"FALSE",'provider_id':provider_id}
	
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
	
	if provider_type == 1:
		#print("DigitalOcean IaaS Provider")
		
		# provider is digitalocean type, let's get the token
		get_auth_sql = "SELECT auth_key0 FROM iaas_auth_configs WHERE provider="+provider_id
		cur.execute(get_auth_sql)
		get_auth_row = cur.fetchone()
		if get_auth_row is None:
			status['message']="No Auth Token Found"
			return status
		auth_token = get_auth_row['auth_key0']
		
		# Get current zones(regions) to check against
		get_cur_zones = "SELECT * FROM iaas_zones WHERE provider="+provider_id
		cur.execute(get_cur_zones,(provider_id))
		get_cur_zones_ret = cur.fetchall()
		
		cur_zones = list()
		for zone in get_cur_zones_ret:
			cur_zones.append(zone['value'])
		#print(cur_zones)
		
		# Let's get the available regions and update the database
		regions = do.get_do_regions(provider_id, auth_token)
		new_slugs = list()
		for region in regions:
			new_slugs.append(region.slug)
			if region.slug not in cur_zones:
				# This is a new slug, add it to the DB
				new_uid = wgm_db.get_unique_id(64,'iaas_zones',db_conn)
				new_slug_sql = "INSERT INTO iaas_zones (name, value,unique_id,provider) VALUES (%s, %s, %s, %s)"
				cur.execute(new_slug_sql,(region.name,region.slug,new_uid,provider_id))
				db_conn.commit()
		
		# Great, now let's delete zones that don't exist anymore
		for zone in cur_zones:
			if zone not in new_slugs:
				#print(zone)
				delete_zone_sql = "DELETE FROM iaas_zones WHERE value='"+zone+"' AND provider="+provider_id
				cur.execute(delete_zone_sql)
				db_conn.commit()

		
	else:
		status['message']="Provider Type Unknown"
		return status
	
	
	status['status']=True
	status['message']="Success"
	return status
