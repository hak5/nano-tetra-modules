#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

# Location of SSL keys
SSL_STORE="/pineapple/modules/Papers/includes/ssl/";

help() {
	echo "Encryption/Export script for OpenSSL certificates";
	echo "Usage: ./encryptRSAKeys.sh <opts>";
	echo "Use './encryptRSAKeys.sh --examples' to see example commands";
	echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $SSL_STORE";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tFile name of key to be encrypted';
	echo '';
	echo 'Encryption Options:';
	echo '';
	echo -e '\t--encrypt:\tMust be supplied to encrypt keys';
	echo -e '\t-a:\t\tAlgorithm to use for key encryption (aes256, 3des, camellia256, etc)';
	echo '';
	echo 'Container Options:';
	echo '';
	echo -e '\t-c:\t\tContainer type (pkcs12, pkcs8)';
  echo -e '\t--pubkey:\tFile name of public key. Must be in selected key store.';
	echo '';
}

examples() {
	echo '';
  echo 'Examples:';
  echo 'Encrypt private key:';
  echo 'echo $pass | ./encryptRSAKeys.sh -k keyName.key --encrypt -a aes256';
  echo '';
  echo 'Export keys to PKCS#12 container:';
  echo 'echo $pass | ./encryptRSAKeys.sh -k keyName.key -c pkcs12 -a aes256';
  echo '';
  echo 'Encrypt private key and export to PKCS#12 container using same algo and pass:';
  echo './encryptRSAKeys.sh -k keyName.key --encrypt -a aes256 -c pkcs12';
	echo '';
}

if [ "$#" -lt 1 ]; then
  help;
  exit;
fi

ENCRYPT_KEYS=false;
KEYDIR=$SSL_STORE;
read PASS

while [ "$#" -gt 0 ]; do

  if [[ "$1" == "--examples" ]]; then
    examples;
    exit;
  fi
  if [[ "$1" == "--encrypt" ]]; then
    ENCRYPT_KEYS=true;
  fi
  if [[ "$1" == "-a" ]]; then
    ALGO="$2";
  fi
  if [[ "$1" == "-k" ]]; then
    KEY="$2";
  fi
  if [[ "$1" == "-c" ]]; then
    CONTAINER="$2";
  fi
  if [[ "$1" == "-s" ]]; then
    KEYDIR="$2"
  fi
  if [[ "$1" == "--pubkey" ]]; then
    PUBKEY="$2"
  fi

  shift
done;

# Generate a password on the private key
if [ $ENCRYPT_KEYS = true ]; then
	openssl rsa -$ALGO -in $KEYDIR/$KEY -out $KEYDIR/$KEY -passout pass:"$PASS" 2>&1 > /dev/null;
fi

# If a container type is present but not an algo or pass then use
# the same algo and pass from the private key
if [ -n "$CONTAINER" ]; then

	# Generate a container for the public and private keys
	openssl $CONTAINER -$ALGO -export -nodes -out $KEYDIR/${KEY%%.*}.pfx -inkey $KEYDIR/$KEY -in $KEYDIR/$PUBKEY -passin pass:"$PASS" -passout pass:"$PASS" 2>&1 > /dev/null;
fi

echo "Complete"
