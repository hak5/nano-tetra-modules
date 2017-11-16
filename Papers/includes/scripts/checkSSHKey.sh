#!/bin/sh

#  Author: sud0nick
#  Date:   Feb 2016

help() {
        echo "Usage: ./checkSSHKey.sh <keydir> <opts>";
        echo '';
        echo 'Parameters:';
        echo '';
        echo -e '\t-k:\tName of key to be checked';
        echo '';
}

if  [ "$#" -lt 1 ]; then
        help;
        exit;
fi

SSH_STORE='/pineapple/modules/Papers/includes/ssh/';
KEY='';

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-k" ]]; then
	if [ -e "$SSH_STORE$2.pub" ]; then
	        KEY=$(cat "$SSH_STORE$2.pub");
	else
		exit;
	fi
fi

shift
done

RES=$(cat /root/.ssh/authorized_keys | grep "$KEY")
if [[ -z "$RES" ]]; then
	echo "FALSE";
else
	echo "TRUE";
fi
