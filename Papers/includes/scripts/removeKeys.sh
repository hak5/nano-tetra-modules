#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

SSL_DIR="/etc/nginx/ssl/";

while [[ $# -gt 0 ]]; do
	rm -rf $SSL_DIR$1;
	shift;
done
