#!/usr/bin/python3

# WGM IAAS CMD LINE
import sys
import iaas
import dns
import json

if len(sys.argv) > 1:
	if sys.argv[1] == "dns_record":
		if len(sys.argv) >= 3:
			if sys.argv[2] == "add":
				#add_dns_record(zone_id,name,rtype,content,ttl)
				if len(sys.argv) != 8:
					print("Not enough arguments provided to add DNS record")
					exit()

				zone_id = sys.argv[3]
				name = sys.argv[4]
				rtype = sys.argv[5]
				content = sys.argv[6]
				ttl = sys.argv[7]
				
				print("Adding DNS Record")
				result = dns.add_dns_record(zone_id,name,rtype,content,ttl)
				print(result)
				
			elif sys.argv[2] == "delete":
				if len(sys.argv) == 5:
					#delete_dns_record(zone_id,record_id)
					
					zone_id = sys.argv[3]
					record_id = sys.argv[4]
					
					print("Deleting DNS Record")
					result = dns.delete_dns_record(zone_id,record_id)
					print(result)
				
				else:
					print("Not enough arguments provided to delete DNS record")
					exit()
					
			elif sys.argv[2] == "update":
				#update_dns_record(zone_id,name,rtype,content,ttl)
				if len(sys.argv) != 9:
					print("Not enough arguments provided to update DNS record")
					exit()
					
				zone_id = sys.argv[3]
				record_id = sys.argv[4]
				name = sys.argv[5]
				rtype = sys.argv[6]
				content = sys.argv[7]
				ttl = sys.argv[8]
				
				print("Updating DNS Record")
				result = dns.update_dns_record(zone_id,record_id,name,rtype,content,ttl)
				print(result)
							
			else:
				print("Unknown command for dns_record")
				exit()
		
		else:
			print("Not enough arguments provided for dns_record")
			exit()
			
	elif sys.argv[1] == "dns_provider":
		if len(sys.argv) >= 3:
			if sys.argv[2] == "setup_zones":
				if len(sys.argv) != 4:
					print("Provider ID Required")
					exit()
				
				provider_id = sys.argv[3]
				
				print("Setting Up Zones")
				result = dns.setup_dns_zones(provider_id)
				print(result)
			
			elif sys.argv[2] == "setup_records":
				if len(sys.argv) != 4:
					print("Provider ID Required")
					exit()
					
				provider_id = sys.argv[3]
					
				print("Setting Up Records")
				result = dns.setup_dns_records(provider_id)
				print(result)
			
			elif sys.argv[2] == "test":
				if len(sys.argv) == 5:
					
					#print("Testing DNS Auth Config")
					provider_type = sys.argv[3]
					auth_token = sys.argv[4]
					
					result = dns.test_dns_auth(provider_type,auth_token)
					
					print(json.dumps(result))
				
				else:
					print("Not Enough parameters to test DNS Auth Config")
					exit()
			
			else:
				print("Unknown command for dns_provider")
				exit()
		else:
			print("Not enough parameters for command: dns_provider")
			exit()
	
	elif sys.argv[1] == "iaas_provider":
		if len(sys.argv) >= 4:
			if sys.argv[2] == "setup_zones":
				provider_id = sys.argv[3]
				print("Setting up Zones for Provider "+provider_id)
				result = iaas.setup_iaas_zones(provider_id)
				print(result)
			
			elif sys.argv[2] == "setup_images":
				provider_id = sys.argv[3]
				print("Setting up Images for Provider "+provider_id)
				result = iaas.setup_iaas_images(provider_id)
				print(result)
				
			elif sys.argv[2] == "test":
				if len(sys.argv) == 5:
					
					provider_id = sys.argv[3]
					auth_token = sys.argv[4]
					
					result = iaas.test_iaas_auth(provider_id,auth_token)
					print(json.dumps(result))
				
				else:
					print("Not enough parameters to test IaaS Auth Config")
					exit()
			
			else:
				print("Unknown command for iaas_provider")
				exit()
		
		else:
			print("Not enough parameters for iaas_provider")
			exit()

	elif sys.argv[1] == "dns_server":
		if len(sys.argv) > 3:
			if sys.argv[2] == "create":
				# name, provider, provider_image, provider_size, provider_zone
				if len(sys.argv) == 8:
					
					name = sys.argv[3]
					dns_type = sys.argv[4]
					provider_id = sys.argv[5]
					provider_image = sys.argv[6]
					provider_zone = sys.argv[7]
					#provider_size = sys.argv[8]
					
					print("Creating DNS Server")
					server = iaas.create_dns_server(name,dns_type,provider_id,provider_image,provider_zone)
					print(server)
					
				
				else:
					print("Not enough options to create dns_server")
					exit()
				
			elif sys.argv[2] == "destroy":
				if len(sys.argv) == 4:
					
					dns_server_id = sys.argv[3]
					
					print("Destroying DNS Server")
					result = iaas.destroy_dns_server(dns_server_id)
					print(result)
				else:
					print("Not enough options to destroy dns_server")
					exit()
				
			else:
				print("Unkown command for dns_server")
				exit()
		else:
			print("Not enough parameters for dns_server")
			exit()
	
	elif sys.argv[1] == "gw_server":
		if len(sys.argv) > 3:
			if sys.argv[2] == "create":
				# name, dns_zone, provider, provider_image, provider_zone
				if len(sys.argv) == 8:
										
					name = sys.argv[3]
					dns_zone = sys.argv[4]
					provider = sys.argv[5]
					image = sys.argv[6]
					zone = sys.argv[7]
					#size = sys.argv[6]
					
					print("Creating GW Server")
					result = iaas.create_gw_server(name,dns_zone,provider,image,zone)
					print(result)
					
				
				else:
					print("Not enough parameters to creat gw_server")
					exit()
			
			elif sys.argv[2] == "destroy":
				# gw_server_id
				if len(sys.argv) == 4:
					print("Destroying GW Server")
					result = iaas.destroy_gw_server(sys.argv[3])
					print(result)
					
				else:
					print("Not enough parameters to destroy gw_server")
					exit()
			else:
				print("Unknown command for gw_server")
				exit()
		else:
			print("Not enough parameters for gw_server")
			exit()
				
	else:
		print("Unkown Command")
		exit()

else:
	print("Command Required")
	exit()
	
	
	
