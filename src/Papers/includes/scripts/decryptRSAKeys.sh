#!/bin/sh

#  Author: sud0nick
#  Date:   Dec 2018

# Location of SSL keys
SSL_STORE="/pineapple/modules/Papers/includes/ssl/";

help() {
	echo "Decryption script for OpenSSL keys";
	echo "Usage: ./decryptRSAKeys.sh <opts>";
	echo "Use './decryptRSAKeys.sh --examples' to see example commands";
	echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $SSL_STORE";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tFile name of key to be decrypted';
	echo -e '\t-p:\tPassword to use to unlock the key';
  echo -e '\t-s:\tKey store to use other than default.'
	echo -e '\t--help:\tDisplays this help info';
	echo '';
}

examples() {
	echo '';
  echo 'Examples:';
  echo 'Decrypt private key:';
  echo './decryptRSAKeys.sh -k keyName -p password';
  echo '';
	echo '';
}

if [ "$#" -lt 1 ]; then
  help;
  exit;
fi

KEYDIR=$SSL_STORE
read PASS

while [ "$#" -gt 0 ]; do

  if [[ "$1" == "--examples" ]]; then
    examples;
    exit;
  fi
  if [[ "$1" == "--help" ]]; then
    help;
    exit;
  fi
  if [[ "$1" == "-k" ]]; then
    KEY="$2";
  fi
  if [[ "$1" == "-p" ]]; then
    PASS="$2";
  fi
  if [[ "$1" == "-s" ]]; then
    KEYDIR="$2"
  fi

  shift
done;

# Generate a password on the private key
openssl rsa -in $KEYDIR/$KEY -out $KEYDIR/$KEY -passin pass:"$PASS" 2>&1 > /dev/null;
if [[ $? != 0 ]]; then
	echo "Bad Password";
	exit;
fi

echo "Complete"
