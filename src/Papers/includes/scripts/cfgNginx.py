#!/usr/bin/python

#  Author: sud0nick
#  Date:   Jan 2016

import sys
import argparse
from cfgHelper import ConfigHelper

parser = argparse.ArgumentParser(description='Nginx Configuration Tool')
parser.add_argument('-k', action='store', dest='keyName', help='Name of the keys to use for SSL configuration')
group = parser.add_mutually_exclusive_group(required=True)
group.add_argument('--add', action='store_true', dest='addSSL', help='Configure Nginx to use SSL.  Requires -k to be set.')
group.add_argument('--replace', action='store_true', dest='replaceSSL', help='Replace current SSL certificates.  Requires -k to be set.')
group.add_argument('--remove', action='store_true', dest='removeSSL', help='Remove SSL configuration from Nginx.')
group.add_argument('--getSSLCerts', action='store_true', dest='getSSLCerts', help="Get the current certs being used for SSL in Nginx.")
args = parser.parse_args()

if (args.addSSL and not args.keyName) or (args.replaceSSL and not args.keyName):
	parser.error("The option selected requires the -k option be provided as well.")

# Create a new instance of ConfigHelper that points to the
# nginx SSL store (default is /etc/nginx/ssl/)
helper = ConfigHelper()

# Add the configuration to the nginx config file
if args.addSSL:
	if not helper.checkSSLCertsExist():
		print "SSL certs must first be generated"
		quit()
		
	if not helper.addSSLConfig(args.keyName):
		print "An error has occurred while attempting to configure SSL"
	else:
		print "Complete"
		
elif args.replaceSSL:
	helper.replaceSSLConfig(args.keyName)
	print "Complete"
	
elif args.removeSSL:
	helper.removeSSLConfig()
	print "Complete"
	
elif args.getSSLCerts:
	if len(helper.currentSSLCerts) > 0:
		print "\n".join(helper.currentSSLCerts)