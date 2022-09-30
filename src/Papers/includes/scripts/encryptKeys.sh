#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

# Location of SSL keys
ssl_store="/pineapple/modules/Papers/includes/ssl/";
ssh_store="/pineapple/modules/Papers/includes/ssh/";

help() {
	echo "Encryption/Export script for OpenSSL certificates";
	echo "Usage: ./encryptKeys.sh <opts>";
	echo "Use './encryptKeys.sh --examples' to see example commands";
	echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $ssl_store";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tName of key to be encrypted';
	echo '';
	echo 'Encryption Options:';
	echo '';
	echo -e '\t--encrypt:\tMust be supplied to encrypt keys';
	echo -e '\t--ssh:\tThe key to encrypt is in the SSH store';
	echo -e '\t-a:\t\tAlgorithm to use for key encryption (aes256, 3des, camellia256, etc)';
	echo -e '\t-p:\t\tPassword to use for encryption';
	echo '';
	echo 'Container Options:';
	echo '';
	echo -e '\t-c:\tContainer type (pkcs12, pkcs8)';
	echo -e '\t-calgo:\tEncyrption algorithm for container. (Default is the value supplied for -a)';
	echo -e '\t-cpass:\tPassword for container. (Default is the password supplied for -p)';
	echo '';
}

examples() {
	echo '';
        echo 'Examples:';
        echo 'Encrypt private key:';
        echo './encryptKeys.sh -k keyName --encrypt -a aes256 -p password';
        echo '';
        echo 'Export keys to PKCS#12 container:';
        echo './encryptKeys.sh -k keyName -c pkcs12 -calgo aes256 -cpass password';
        echo '';
        echo 'Encrypt private key and export to PKCS#12 container using same algo and pass:';
        echo './encryptKeys.sh -k keyName --encrypt -a aes256 -p password -c pkcs12';
        echo '';
        echo 'Encrypt private key and export to PKCS#12 container using different algo and pass:';
        echo './encryptKeys.sh -k keyName --encrypt -a aes256 -p password -c pkcs12 -calgo camellia256 -cpass diffpass';
	echo '';
}

if [ "$#" -lt 1 ]; then
        help;
        exit;
fi

ENCRYPT_KEYS=false;

while [ "$#" -gt 0 ]
do

if [[ "$1" == "--examples" ]]; then
	examples;
	exit;
fi
if [[ "$1" == "--encrypt" ]]; then
	ENCRYPT_KEYS=true;
fi
if [[ "$1" == "--ssh" ]]; then
	ssl_store=$ssh_store;
fi
if [[ "$1" == "-a" ]]; then
	ALGO="$2";
fi
if [[ "$1" == "-k" ]]; then
	KEY="$2";
fi
if [[ "$1" == "-p" ]]; then
	PASS="$2";
fi
if [[ "$1" == "-c" ]]; then
	CONTAINER="$2";
fi
if [[ "$1" == "-calgo" ]]; then
	CALGO="$2";
fi
if [[ "$1" == "-cpass" ]]; then
	CPASS="$2";
fi

shift
done;

# Generate a password on the private key
if [ $ENCRYPT_KEYS = true ]; then
	openssl rsa -$ALGO -in $ssl_store$KEY.key -out $ssl_store$KEY.key -passout pass:"$PASS";
fi

# If a container type is present but not an algo or pass then use
# the same algo and pass from the private key
if [ -n "$CONTAINER" ]; then
	if [ -z "$CALGO" ]; then
		CALGO="$ALGO";
	fi
	if [ -z "$CPASS" ]; then
		CPASS="$PASS";
	fi

	# Generate a container for the public and private keys
	openssl $CONTAINER -$CALGO -export -nodes -out $ssl_store$KEY.pfx -inkey $ssl_store$KEY.key -in $ssl_store$KEY.cer -passin pass:"$PASS" -passout pass:"$CPASS";
fi

echo "Complete"
