#!/usr/bin/python3

# WGM Controller CLI
import sys
import iaas
import dns
import json
import wgmcontroller

if len(sys.argv) > 1:
	#print("taking commands")
	if sys.argv[1] == "attach_gw":
		if len(sys.argv) == 4:
			print("attaching gw")
			
			gw = sys.argv[2]
			loc = sys.argv[3]
			
			result = wgmcontroller.attach_gw(gw,loc)
			print(result)
			
		else:
			print("Not enough arguments to attach_gw")
			exit()
			
	elif sys.argv[1] == "detach_gw":
		if len(sys.argv) ==4:
			print("detaching gw")
			
			gw = sys.argv[2]
			loc = sys.argv[3]
			
			result = wgmcontroller.detach_gw(gw,loc)
			print(result)
		
		else:
			print("Not enough arguments to detach_gw")
			exit()
		
	elif sys.argv[1] == "attach_client":
		if len(sys.argv) == 3:	# By this point the client should have everything configured to update the related gw
			print("attaching client")
			
			client_id = sys.argv[2]
			result = wgmcontroller.attach_client(client_id)
			print(result)
		else:
			print("Not enough arguments to attach client")
			exit()
		
	elif sys.argv[1] == "detach_client":
		if len(sys.argv) == 3:
			print("detaching client")
			
			client_id = sys.argv[2]
			result = wgmcontroller.detach_client(client_id)
			print(result)
		
		else:
			print("Not enough arguments to detach client")
			exit()
		
	else:
		print("Invalid controller command")
		exit()
	
else:
	print("Not enough arguments")
	exit()
