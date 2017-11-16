#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Usage: $0 <certificate>";
	exit;
fi

openssl x509 -in $1 -noout -fingerprint | awk '{print $2}'
