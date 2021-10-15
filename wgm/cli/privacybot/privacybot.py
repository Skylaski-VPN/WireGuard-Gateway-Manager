#!/usr/bin/python3

# PrivacyBot WG output Interpreter


# This gets a list of peers that need to be re-peered for privacy sake
def get_repeer_list(wg_output_path):

	import re

	# This is the maximum number of minutes a peer can go without a handshake before getting re-peered
	max_disconnect_time = 3

	# These are the regex statements for matching lines in WireGuard output
	peer_line_pattern = re.compile("^peer: ")
	endpoint_line_pattern = re.compile(".*endpoint: (\d+\.\d+\.\d+\.\d+):")
	allowedips_line_pattern = re.compile(".*allowed ips: (\d+\.\d+\.\d+\.\d+)\/32")
	latesthandshake_line_pattern = re.compile(".*latest handshake: ")
	latesthandshake_days_pattern = re.compile(".*latest handshake: \d+ days")
	latesthandshake_hours_pattern = re.compile(".*latest handshake: \d+ hours")
	latesthandshake_minutes_pattern = re.compile(".*latest handshake: (\d+) minutes")

	# Get WireGuard output
	wg_output_file = open(wg_output_path,"r")
	wg_out_line = wg_output_file.readline()
	#print(wg_output_file.readline())

	# Triggers for when we're looping through the WireGuard output
	found_peer = False
	found_connected_peer = False
	found_connected_peer_allowedips = False
	found_allowed_ip = ""
	found_latest_handshake_line = False
	peer_count = 0
	peer_connected_count = 0

	# This is where we'll store the list of client 'Allowed IPs' that need to be repeered for privacy sake
	repeer_allowed_ips = []

	# Loop through the output and collect a list of 'Allowed IPs' that need to be repeered
	while wg_out_line:
		#print("READING LINE: \""+wg_out_line.rstrip()+"\"")

		if peer_line_pattern.search(wg_out_line.rstrip()):
			#print("PEER LINE")
			peer_count+=1
			found_peer = True
			#print("PEER COUNT: %s" % (peer_count))
		elif found_peer == True and found_connected_peer == False and found_connected_peer_allowedips == False and wg_out_line.rstrip() != "":
			results = endpoint_line_pattern.search(wg_out_line.rstrip())
			if results:
				#print("ENDPOINT: %s" % (results.group(1)))
				found_connected_peer = True
				peer_connected_count += 1
		elif found_peer == True and found_connected_peer == True and found_connected_peer_allowedips == False and wg_out_line.rstrip() != "":
			results = allowedips_line_pattern.search(wg_out_line.rstrip())
			if results:
				#print("ALLOWEDIPS: %s" %(results.group(1)))
				found_connected_peer_allowedips = True
				found_allowed_ip = results.group(1)
		elif found_peer == True and found_connected_peer == True and found_connected_peer_allowedips == True and found_latest_handshake_line == False and wg_out_line.rstrip() != "":
			results = latesthandshake_line_pattern.search(wg_out_line.rstrip())
			#print("LOOKING FOR LATEST HANDSHAKE")
			if results:
				#print ("FOUND LATEST HANDSHAKE")
				found_latest_handshake_line = True
				daysm = latesthandshake_days_pattern.search(wg_out_line.rstrip())
				hoursm = latesthandshake_hours_pattern.search(wg_out_line.rstrip())
				minutesm = latesthandshake_minutes_pattern.search(wg_out_line.rstrip())
				if daysm:
					#print("------ADD THIS PEER TO REPEER LIST----------")
					repeer_allowed_ips.append(found_allowed_ip)
				elif hoursm:
					#print("------ADD THIS PEER TO REPEER LIST----------")
					repeer_allowed_ips.append(found_allowed_ip)
				elif minutesm:
					if int(minutesm.group(1)) >= max_disconnect_time:
						#print("------ADD THIS PEER TO REPEER LIST----------")
						repeer_allowed_ips.append(found_allowed_ip)
		else:
			found_peer = False
			found_connected_peer = False
			found_connected_peer_allowedips = False
			found_allowed_ip = ""
			found_latest_handshake_line = False

		wg_out_line = wg_output_file.readline()

	# Close the file that contains the WireGuard output
	wg_output_file.close()

	return repeer_allowed_ips

