#!/usr/bin/python3
#
# privacy_bot
#
#	Privacy bot interprets WireGuard output and generates a list of connected clients
# That haven't performed a handshake in the last 2 minutes. Privacy bot then re-peers these
# clients with the WireGuard server to remove the known IP Address from the server's memory.

import privacybot as pb
import wgm_db
import wgmcontroller

import psycopg2.extras
import os
import subprocess

NETWORKS = [ 1 ]
wg_output_path = '/home/wgm/www/wgm.skylaski.com/wgm/cli/tmp/wg_checkpeer_output.out'

# Get Network Servers

for net in NETWORKS:
	# Get client, make sure it's legit, die otherwise
	db_conn = wgm_db.connect()
	cur1 = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	cur2 = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	get_network_servers_sql = "SELECT * FROM gw_servers gw JOIN rel_net_loc_gw rel ON rel.gw_id=gw.id WHERE rel.net_id="+str(net)
	cur1.execute(get_network_servers_sql)
	server = cur1.fetchone()

	while server:
		print("-----------------------")
		print("| SERVER "+str(server['id'])+": "+server['name'].upper()+" : "+server['pub_ipv4_addr']+" |")
		print("-----------------------")
		cmd = os.system("ssh root@%s \"sudo wg\" > %s" % (server['pub_ipv4_addr'], wg_output_path))
		repeer_list = pb.get_repeer_list(wg_output_path)

		for peer in repeer_list:
			print(peer)
			# Find peer id
			find_client_sql = "SELECT client_id FROM ipv4_client_leases WHERE gw_id="+str(server['id'])+" AND address='"+peer+"'"
			cur2.execute(find_client_sql)
			client = cur2.fetchone()
			print("Repeering Client ID: "+str(client['client_id']))
			wgmcontroller.detach_client(str(client['client_id']))
			wgmcontroller.attach_client(str(client['client_id']))

		# Next Server
		server = cur1.fetchone()

# remove wg output file
os.remove(wg_output_path)

# Close DB
db_conn.close()
