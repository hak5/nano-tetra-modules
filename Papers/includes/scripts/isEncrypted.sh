#!/bin/sh

SSL_STORE="/pineapple/modules/Papers/includes/ssl/";
SSH_STORE="/pineapple/modules/Papers/includes/ssh/";

help() {
  echo "Usage: ./testEncrypt.sh <opts>";
  echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $SSL_STORE";
  echo "Current SSH store is at $SSH_STORE";
  echo '';
  echo 'Parameters:';
  echo '';
  echo -e '\t-k:\tName of key to test.';
  echo -e '\t-t:\tType of key: RSA|SSH.';
  echo -e "\t-s:\tKey store to use other than default."
  echo '';
}

if  [ "$#" -lt 2 ]; then
  help;
  exit;
fi

KEYDIR=''

# Get arguments
while [ "$#" -gt 0 ]; do

  if [[ "$1" == "-k" ]]; then
    KEY="$2"
  fi
  if [[ "$1" == "-s" ]]; then
    KEYDIR="$2"
  fi
  if [[ "$1" == "-t" ]]; then
    TYPE="$2"
  fi

  shift
done;

# If the type selected is SSH...
if [[ "$TYPE" == "SSH" ]]; then

  if [[ "$KEYDIR" == "" ]]; then
    KEYDIR=$SSH_STORE
  fi

  # Pull the header from the key file
  HEADER=$(sed '1d;$d' $KEYDIR/$KEY | base64 -d | head -c 32)
  FORMAT=$(echo $HEADER | cut -c 0-14)
  ENC=$(echo $HEADER | cut -c 16-19)

  # Ensure the key is in OpenSSH private key format
  if [[ "$FORMAT" == "openssh-key-v1" ]]; then

    # Check if the key is encrypted
    if [[ "$ENC" == "none" ]]; then
      echo "false"
    else
      echo "true"
    fi

  else
    # This should never happen...
    echo "Invalid OpenSSH key"
  fi
else
  if [[ "$TYPE" == "RSA" ]]; then

    if [[ "$KEYDIR" == "" ]]; then
      KEYDIR=$SSL_STORE
    fi

    # Check if the RSA key is encrypted
    RES=$(openssl rsa -in $KEYDIR/$KEY -passin pass:_ 2>&1 > /dev/null)
    
    if [[ "$?" == "1" ]]; then
      echo "true"
    else
      echo "false"
    fi
  else
    # This should never happen when called from the module.
    echo "Invalid option: $TYPE"
  fi
fi
