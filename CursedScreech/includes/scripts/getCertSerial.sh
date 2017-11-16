#!/bin/sh

if [[ $# -lt 1 ]]; then
	echo "Usage: $0 <path to cert>";
	exit;
fi

if ! [[ -e $1 ]]; then
	echo "File does not exist"
	exit;
fi

print=$(echo $(openssl x509 -noout -in $1 -serial) | sed 's/://g')
echo $print | tr "=" " " | awk '{print $2}'
