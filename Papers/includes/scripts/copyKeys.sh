#!/bin/bash

#  Author: sud0nick
#  Date:   Jan 2016

if ! cp $1.pem /etc/nginx/ssl/; then
	echo "Failed to copy $1.pem to /etc/nginx/ssl/";
fi

if ! cp $1.cer /etc/nginx/ssl/; then
	echo "Failed to copy $1.cer to /etc/nginx/ssl/";
fi
