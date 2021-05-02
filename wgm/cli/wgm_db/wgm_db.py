#!/usr/bin/python3

import psycopg2
import random
from configparser import ConfigParser
import os

def config(filename='./config.ini', section='postgresql'):
    # create a parser
    parser = ConfigParser()
    # read config file
    parser.read(filename)

    # get section, default to postgresql
    db = {}
    if parser.has_section(section):
        params = parser.items(section)
        for param in params:
            db[param[0]] = param[1]
    else:
        raise Exception('Section {0} not found in the {1} file'.format(section, filename))

    return db

def connect():
	""" Connect to the PostgreSQL database server """
	conn = None
	cur_path = os.path.dirname(os.path.realpath(__file__))
	try:
		# read connection parameters
		params = config(cur_path+"/config.ini")
		# connect to the PostgreSQL server
		#print('Connecting to the PostgreSQL database...')
		conn = psycopg2.connect(**params)

	except (Exception, psycopg2.DatabaseError) as error:
		print(error)
	finally:
		if conn is not None:
			#print("WGM_DB is CONNECTED")
			return conn



def get_unique_id(length,table,db):
	chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_-=+"
	size = length
	found_unique_id = 0
	random_string = ''
	
	while found_unique_id == 0:
		random_string = ''.join(random.choice(chars) for x in range(size))
		cur = db.cursor()
		cur.execute('SELECT id FROM '+table+' WHERE unique_id = \''+random_string+'\'')
		row_id = cur.fetchone()
		if row_id != None:
			found_unique_id = 0
			random_string = ''.join(random.choice(chars) for x in range(size))
		else:
			found_unique_id = 1
	
	return random_string


