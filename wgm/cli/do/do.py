#!/usr/bin/python3

# Script for managing Digital Ocean IaaS deployments.
#
#
#######################################################

# dependencies
import digitalocean
import re
import time

def get_do_droplets(provider_id, auth_token):
	
	# Token got, now lets establish a connection with digitalocean
	manager = digitalocean.Manager(token=auth_token)
	# Print droplets
	my_droplets = manager.get_all_droplets()
	print("Listing Droplets\n")
	#print(type(my_droplets))
	
	for drop in my_droplets:
		print(str(drop.id)+": "+drop.name)

	return True


def get_do_regions(provider_id, auth_token):
	
	manager = digitalocean.Manager(token=auth_token)

	try:
		all_regions = manager.get_all_regions()
	except:
		return False
		
	available_regions = list()
	
	for region in all_regions:
		if region.available == True:
			available_regions.append(region)
		
	return available_regions
	

def get_do_images(provider_id, auth_token):
	manager = digitalocean.Manager(token=auth_token)
	my_images = list()
	
	all_images = manager.get_all_images()
	
	# Since Digital Ocean doesn't support tagging droplet images we search for images identified with 'wgm_vm_image' in the name
	pattern = re.compile("wgm_vm_image")
	
	for image in all_images:
		if pattern.search(image.name):
			my_images.append(image)
			
	return my_images


# Backups = False by default
def create_do_droplet(config):
	status={"command":"create_do_droplet","status":False,"message":"FALSE","droplet":None}
	# Time it takes to build a Droplet
	time_to_build = 80
	manager = digitalocean.Manager(token=config['token'])
	
	droplet = digitalocean.Droplet(token=config['token'],
										name=config['name'],
										region=config['region'],
										image=config['image'],
										size_slug=config['size'],
										backups=config['backups'],
										ipv6=config['ipv6'],
										ssh_keys=config['ssh_keys'],
										tags=config['tags']
										)
	
	try:
		droplet.create()
	except digitalocean.DataReadError as e:
		status['message']=e
		return status
	except digitalocean.Error as e:
		status['message']=e
		return status
	
	# Sleep for time_to_build
	time.sleep(time_to_build)
	
	newDroplet = manager.get_droplet(droplet.id)
	status['status']=True
	status['message']="Droplet Created"
	status['droplet']=newDroplet
	
	return status
	

def destroy_do_droplet(droplet_id,token):
	status = False
	
	manager = digitalocean.Manager(token=token)
	
	droplet = manager.get_droplet(droplet_id)
	droplet.destroy()
	
	
	status = True
	return status
