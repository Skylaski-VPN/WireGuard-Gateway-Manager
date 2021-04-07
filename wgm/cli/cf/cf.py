#!/usr/bin/python3
#
#	WGM CloudFlare Integration
#
#	In order to update DNS records (A Types) for provisioned servers we integrate with Cloudflare DNS Services
#

# Dependencies
import requests
#import CloudFlare

CF_TIMEOUT = 3

# Test Auth Config
def test_cf_auth(auth_token):
	
	verify_token_url = "https://api.cloudflare.com/client/v4/user/tokens/verify"
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	resp = requests.get(verify_token_url,headers=headers,timeout=CF_TIMEOUT)
		
	return resp.json()

# Get DNS Zones
def get_dns_zones(auth_token):
	zones = list()

	list_zones_url="https://api.cloudflare.com/client/v4/zones"
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	#print(headers)
	resp=requests.get(list_zones_url,headers=headers, timeout=CF_TIMEOUT)
	data = resp.json()
	
	for res in data['result']:
		zones.append(res)
	
	
	return zones

# Get DNS Records
def get_dns_records(zone_id, auth_token):
	records = list()
	
	list_dns_record_url = "https://api.cloudflare.com/client/v4/zones/"+zone_id+"/dns_records?type=A"
	#print("URL: "+list_dns_record_url)
	
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	#print(headers)
	
	resp=requests.get(list_dns_record_url,headers=headers,timeout=CF_TIMEOUT)
	data = resp.json()
	for record in data['result']:
		records.append(record)
		#print(record['type']+" "+record['name']+" "+record['content'])
	
	#print(resp)
	
	return records



# Proxied = False & Priority = 10 by default
def add_dns_record(zone_id, auth_token, name, record_type, content, ttl):
	record_uid = "FALSE"
	
	create_record_url = "https://api.cloudflare.com/client/v4/zones/"+zone_id+"/dns_records"
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	data={"type":record_type,"name":name,"content":content,"ttl":ttl,"priority":10,"proxied":False}
	
	resp=requests.post(create_record_url,headers=headers,json=data,timeout=CF_TIMEOUT)
	return_data = resp.json()
	
	if return_data['success'] == True:
		record_uid = return_data['result']['id']
	else:
		print(return_data['errors'])
		record_uid = "FALSE"
	
	return record_uid


def delete_dns_record(zone_id,record_id,auth_token):
	success = False
	
	delete_record_url = "https://api.cloudflare.com/client/v4/zones/"+zone_id+"/dns_records/"+record_id
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	
	resp=requests.delete(delete_record_url,headers=headers,timeout=CF_TIMEOUT)
	return_data = resp.json()
	
	if return_data['success'] == True:
		success = True
	else:
		print(return_data['errors'])
	
	return success
	
# Proxied = False & Priority = 10 by default
def update_dns_record(zone_id, record_id,auth_token,name,record_type,content,ttl):
	success = False
	
	create_record_url = "https://api.cloudflare.com/client/v4/zones/"+zone_id+"/dns_records/"+record_id
	headers={"Authorization": "Bearer "+auth_token, "Content-Type": "application/json"}
	data={"type":record_type,"name":name,"content":content,"ttl":ttl,"priority":10,"proxied":False}
	
	resp=requests.put(create_record_url,headers=headers,json=data,timeout=CF_TIMEOUT)
	return_data = resp.json()
	
	if return_data['success'] == True:
		success = True
	else:
		print(return_data['errors'])
	
	return success
	
