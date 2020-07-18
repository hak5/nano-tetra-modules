#!/bin/sh

#  Author: sud0nick
#  Date:   July 2020

# Location of SSH keys
SSH_STORE="/pineapple/modules/Papers/includes/ssh/";

help() {
	echo "Encrypt OpenSSH private keys";
	echo "Usage: ./encryptSSHKey.sh <opts>";
	echo '';
	echo 'NOTE:';
	echo "Current SSH store is at $SSH_STORE";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tFile name of key to be encrypted';
	echo '';
	echo 'Options:';
	echo '';
  echo -e "\t-s:\t\tUse an SSH store other than the default."
	echo '';
}

if [ "$#" -lt 1 ]; then
  help;
  exit;
fi

# Read password from pipe input
read PASS

# Fetch arguments from command line
while [ "$#" -gt 0 ]; do

  if [[ "$1" == "-k" ]]; then
    KEY="$2";
  fi

  if [[ "$1" == "-s" ]]; then
    SSH_STORE="$2";
  fi

  shift
done;

# Encrypt the key
ssh-keygen -o -p -N "$PASS" -q -f $SSH_STORE/$KEY 2>&1 > /dev/null

if [[ "$?" == "0" ]]; then
  echo "Complete"
else
  echo "false"
fi