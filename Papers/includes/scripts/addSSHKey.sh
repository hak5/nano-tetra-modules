#!/bin/sh

#  Author: sud0nick
#  Date:   Feb 2016

help() {
        echo "Usage: ./addAuthKey.sh <keydir> <opts>";
        echo '';
        echo 'Parameters:';
        echo '';
        echo -e '\t-k:\tName of key to be used';
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
	KEY="$2";
fi

shift
done

cat $SSH_STORE$KEY.pub >> /root/.ssh/authorized_keys
