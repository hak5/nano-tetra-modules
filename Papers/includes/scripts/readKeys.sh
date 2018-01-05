#!/bin/bash

# Author: sud0nick
#   Date: April 6, 2016

IN_SERVER_BLOCK=false;

while read p; do
	if [[ $IN_SERVER_BLOCK == false ]]; then
		if [[ $p == *"listen"* && $p == *"1471"* ]]; then
			IN_SERVER_BLOCK=true;
		fi
	else
		if [[ $p == *".cer;" || $p == *".key;" ]]; then
			echo $p | cut -d '/' -f 5 | tr -d ';';
		fi
	fi
done < /etc/nginx/nginx.conf
