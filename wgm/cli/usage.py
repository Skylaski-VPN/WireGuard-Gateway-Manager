#!/usr/bin/python3
#
# Usage Script
#
#	This script runs 'wg' on every server on predefined networks,
#	The number of connected peers by location is recorded for performance monitoring
#

import wgm_db
import psycopg2.extras
import os
import subprocess
import re

NETWORKS = [ 1 ]
wg_output_path = '/home/wgm/www/wgm.skylaski.com/wgm/cli/tmp/wg_usage_output.out'
wg_usage_table = []


# Function to parse 'sudo wg' output
def parsewg( net, location, server, filepath, wg_usage_table ):

	# Get command output
	with open(filepath) as f:
		content = f.readlines()

	# loop through output, count peers.
	peer_count = 0
	p = re.compile('peer:')
	for line in content:
		result = p.match(line)
		if result:
			peer_count += 1

#	print(server)
	print("peer count: "+str(peer_count))

	p2 = re.compile('.*latest handshake: (\S*) (\S*)')
	has_connected_count = 0
	connected_count = 0
	for line in content:
		result = p2.match(line)
		#print(line)
		if result:
			has_connected_count += 1
			#print(line)
			#print(result.group(2))
			if result.group(2) == "seconds" or result.group(2) == "second":
				connected_count += 1
			elif result.group(2) == "minute":
				connected_count += 1
			elif result.group(2) == "minute,":
				connected_count += 1
			elif result.group(2) == "minutes,":
				if int(result.group(1)) <= 4:
					connected_count += 1

	print('has connected: ' + str(has_connected_count))
	print('still connected: ' + str(connected_count))

	new_list = [net,location,server,peer_count,has_connected_count,connected_count]
	wg_usage_table.append(new_list)

	return


# Get Network Servers
for net in NETWORKS:
	# Get client, make sure it's legit, die otherwise
	db_conn = wgm_db.connect()
	cur1 = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	# cur2 = db_conn.cursor(cursor_factory = psycopg2.extras.DictCursor)
	get_network_servers_sql = "SELECT * FROM gw_servers gw JOIN rel_net_loc_gw rel ON rel.gw_id=gw.id JOIN locations loc ON rel.loc_id=loc.id WHERE rel.net_id="+str(net)
	cur1.execute(get_network_servers_sql)
	server = cur1.fetchone()

	while server:
		print("-----------------------")
		print("| SERVER "+str(server['id'])+": "+server['name'].upper()+" : "+server['pub_ipv4_addr']+" |")
		print("-----------------------")
		cmd = os.system("ssh root@%s \"wg\" > %s" % (server['pub_ipv4_addr'], wg_output_path))
		parsewg(str(net),str(server['geo_name']),str(server['pub_ipv4_addr']),wg_output_path,wg_usage_table)

		# Next Server
		server = cur1.fetchone()

for row in wg_usage_table:
	print("------------------")
	print("Network: "+str(row[0]))
	print("Location: "+row[1])
	print("Server: "+row[2])
	print("Peers: "+str(row[3]))
	print("has_connected: "+str(row[4]))
	print("still_connected: "+str(row[5]))
	update_usage_sql = "INSERT INTO usage (network_id,server,location,peers,has_connected,still_connected) VALUES (%s,'%s','%s',%s,%s,%s)" % (row[0],row[2],row[1],row[3],row[4],row[5])
	cur1.execute(update_usage_sql)

db_conn.commit()

# remove wg output file
os.remove(wg_output_path)

# Close DB
db_conn.close()
