#!/usr/bin/python3

# WGM Controller
#	attach_gw_server
#	detach_gw_server
#	attach_client
#	detach_client
import sys
import wgm_db
import psycopg2.extras
import re
import subprocess
import os
#import ipaddress

# Default Port for GW Servers
GW_PORT = "51820"
SSH_OPTS = "-o StrictHostKeyChecking=no"
SSH_USER = "root"
DEFAULT_CONFIG_TEMPLATE = "default_gw"
DEFAULT_CLIENT_CONFIG_TEMPLATE = "default_client"
TEMP_CONFIG_FILE = "temp.txt"

def detach_client(client_id):
	status={'command':'detach_gw','status':False,'message':"Fail", "client":client_id}
	
	# Get client, make sure it's legit, die otherwise
	db_conn = wgm_db.connect()
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	get_client_sql = "SELECT * FROM clients WHERE id="+client_id
	cur.execute(get_client_sql)
	get_client_ret = cur.fetchone()
	if get_client_ret is None:
		status['message']="Failed to fetch client"
		return status
		
	detach_cmd = 'ssh '+SSH_USER+'@'+get_client_ret['gw_addr']+' "wg set wg0 peer '+get_client_ret['pub_key']+' remove"'
	print(detach_cmd)
	p = subprocess.Popen(detach_cmd,shell=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
	detach_out = p.communicate()[1]
	wg_detach_message = str(detach_out)[2:-1]
	
	status['message']=wg_detach_message
	status['status']=True

	
	return status

def attach_client(client_id):
	status={'command':'attach_gw','status':False,'message':"Fail", "client":client_id}
	
	# Get client, make sure it's legit, die otherwise
	db_conn = wgm_db.connect()
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	get_client_sql = "SELECT * FROM clients WHERE id="+client_id
	cur.execute(get_client_sql)
	get_client_ret = cur.fetchone()
	if get_client_ret is None:
		status['message']="Failed to fetch client"
		return status
		
	# Client is legit. Let's attach it to the server
	attach_cmd = 'ssh '+SSH_USER+'@'+get_client_ret['gw_addr']+' "wg set wg0 peer '+get_client_ret['pub_key']+' allowed-ips '+get_client_ret['ipv4_addr']+'/32,'+get_client_ret['ipv6_addr']+'/128"'
	#print(attach_cmd)
	p = subprocess.Popen(attach_cmd,shell=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
	attach_out = p.communicate()[1]
	wg_attach_message = str(attach_out)[2:-1]
	
	# Client is attached, let's setup it's config. For now we wont touch the <PRIVATEKEY> section 
	# the client should already know it's <PRIVATEKEY>
	get_config_sql = "SELECT * FROM templates WHERE name='"+DEFAULT_CLIENT_CONFIG_TEMPLATE+"'"
	cur.execute(get_config_sql)
	get_config_ret = cur.fetchone()
	if get_config_ret is None:
		status['message']="Failed to get default config"
		return status
	
	# Now let's fill the config with our variables
	#print(get_config_ret['data'])
	step1 = get_config_ret['data'].replace('<IPV4>',get_client_ret['ipv4_addr'])
	step2 = step1.replace('<V4MASK>','/32')
	step3 = step2.replace('<IPV6>',get_client_ret['ipv6_addr'])
	step4 = step3.replace('<V6MASK>','/128')
	step5 = step4.replace('<DNS>',get_client_ret['dns_ip'])
	step6 = step5.replace('<GWKEY>',get_client_ret['gw_key'])
	step7 = step6.replace('<GWADDR>',get_client_ret['gw_addr'])
	step8 = step7.replace('<GWPORT>',get_client_ret['gw_port'])
	# Insert Config in DB
	insert_config_sql = "UPDATE clients set client_config='"+step8+"' WHERE id="+client_id
	cur.execute(insert_config_sql)
	db_conn.commit()
	
	status['message']=wg_attach_message
	status['status']=True
	
	return status

def detach_gw(gw,loc):
	status={'command':'attach_gw','status':False,'message':"Fail","gw":gw,"loc":loc}
	
	# First make sure this is a valid gateway and location
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_gw_sql = "SELECT * FROM gw_servers WHERE id="+gw
	cur.execute(get_gw_sql)
	get_gw_ret = cur.fetchone()
	# make sure we have a legit gw
	if get_gw_ret is None:
		status['message']="GW Does Not Exist"
		return status
		
	get_loc_sql = "SELECT * FROM locations WHERE id="+loc
	cur.execute(get_loc_sql)
	get_loc_ret = cur.fetchone()
	if get_loc_ret is None:
		status['message']="Location Does Not Exist"
		return status
		
	# Stop WireGuard, delete config
	stop_wg_cmd = 'ssh %s %s@%s "wg-quick down wg0"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(stop_wg_cmd,shell=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
	stop_wg_out = p.communicate()[1]
	delete_config_cmd = 'ssh %s %s@%s "rm -rf /etc/wireguard/wg0.conf"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(delete_config_cmd,shell=True,stdout=subprocess.PIPE)
	delete_config_out = p.communicate()[0]
	#print(gen_key_out)
	
	status['message']=str(stop_wg_out)[2:-1]
	status['status']=True
	
	return status

def attach_gw(gw,loc):
	status={'command':'attach_gw','status':False,'message':"Fail","gw":gw,"pub_key":""}
	
	#print("attaching gw")
	# First make sure this is a valid gateway and location
	db_conn = wgm_db.connect()
	#print("DB Connection Open")
	cur = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	
	#print("Provider id: %s" % provider_id)
	get_gw_sql = "SELECT * FROM gw_servers WHERE id="+gw
	cur.execute(get_gw_sql)
	get_gw_ret = cur.fetchone()
	# make sure we have a legit gw
	if get_gw_ret is None:
		status['message']="GW Does Not Exist"
		return status
		
	get_loc_sql = "SELECT * FROM locations WHERE id="+loc
	cur.execute(get_loc_sql)
	get_loc_ret = cur.fetchone()
	if get_loc_ret is None:
		status['message']="Location Does Not Exist"
		return status
		
	# Location and GW exist. Get IP Addresses for GW
	get_net_sql = "SELECT * FROM networks WHERE id="+str(get_loc_ret['network_id'])
	cur.execute(get_net_sql)
	get_net_ret = cur.fetchone()
	if get_net_ret is None:
		status['message']="Failed to find network"
		return status
	
	#mask_pattern = re.compile("(/\d{1,3})")
	ipv4_address = get_net_ret['ipv4_gateway']
	v4_network = get_net_ret['ipv4_netmask']
	m = re.search(r'(/\d{1,3})',v4_network)
	v4_mask = m.groups()[0]

	ipv6_address = get_net_ret['ipv6_gateway']
	v6_network = get_net_ret['ipv6_netmask']
	m = re.search(r'(/\d{1,3})',v6_network)
	v6_mask = m.groups()[0]	
	
	# Should have all the network info we need. Now let's generate the initial wireguard keys
	gen_keys = 'ssh %s %s@%s "wg genkey | tee private.key | wg pubkey > public.key"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(gen_keys,shell=True,stdout=subprocess.PIPE)
	gen_key_out = p.communicate()[0]
	#print(gen_key_out)
	get_priv_key = 'ssh %s %s@%s "cat /root/private.key"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(get_priv_key,shell=True,stdout=subprocess.PIPE)
	get_priv_key_out = p.communicate()[0]
	priv_key = str(get_priv_key_out)[2:-3]
	#print("Private: "+str(get_priv_key_out))
	#print("Private: "+priv_key)
	
	get_pub_key = 'ssh %s %s@%s "cat /root/public.key"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(get_pub_key,shell=True,stdout=subprocess.PIPE)
	get_pub_key_out = p.communicate()[0]
	pub_key = str(get_pub_key_out)[2:-3]
	#print("Public: "+str(get_pub_key_out))
	#print("Public: "+pub_key)
	
	# Now get the wg_gw_config_template from the DB
	get_config_sql = "SELECT * FROM templates WHERE name='"+DEFAULT_CONFIG_TEMPLATE+"'"
	cur.execute(get_config_sql)
	get_config_ret = cur.fetchone()
	if get_config_ret is None:
		status['message']="Failed to get default config"
		return status
	
	# Now let's fill the config with our variables
	#print(get_config_ret['data'])
	step1 = get_config_ret['data'].replace('<IPV4>',ipv4_address)
	step2 = step1.replace('<V4MASK>',v4_mask)
	step3 = step2.replace('<IPV6>',ipv6_address)
	step4 = step3.replace('<V6MASK>',v6_mask)
	step5 = step4.replace('<LISTENPORT>',GW_PORT)
	step6 = step5.replace('<PRIVATEKEY>',priv_key)
	
	#print(step6)
	temp = open(TEMP_CONFIG_FILE,"w")
	temp.write(step6)
	temp.write('\r\n')
	temp.close()

	# Write config file and start the interface
	write_config_cmd = 'scp %s %s@%s:/etc/wireguard/wg0.conf' % (TEMP_CONFIG_FILE,SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(write_config_cmd,shell=True,stdout=subprocess.PIPE)
	write_out = p.communicate()[0]
	#print(str(write_out))
	start_wg_cmd = 'ssh %s %s@%s "wg-quick up wg0"' % (SSH_OPTS, SSH_USER,get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(start_wg_cmd,shell=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
	start_out = p.communicate()[1]
	wg_start_message = str(start_out)[2:-1]
	#print(wg_start_message)
	os.remove(TEMP_CONFIG_FILE)
	
	# Make sure WireGuard starts at boot
	systemd_cmd = 'ssh %s %s@%s "systemctl enable wg-quick@wg0"' % (SSH_OPTS, SSH_USER, get_gw_ret['pub_ipv4_addr'])
	p = subprocess.Popen(systemd_cmd,shell=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
	systemd_out = p.communicate()[1]

	# Update GW in database with port, ipv4, ipv6, and public key
	update_gw_sql = "UPDATE gw_servers SET port='"+GW_PORT+"', ipv4_addr='"+ipv4_address+"', ipv6_addr='"+ipv6_address+"', pub_key='"+pub_key+"' WHERE id="+gw
	cur.execute(update_gw_sql)
	db_conn.commit()
	
	status['status']=True
	status['message']=wg_start_message
	status['pub_key']=pub_key
	
	return status
	
