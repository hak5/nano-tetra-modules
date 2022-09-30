#!/bin/sh

#  Author: sud0nick
#  Date:   Feb 2016

help() {
        echo "Usage: ./revokeSSHKey.sh <keydir> <opts>";
        echo '';
        echo 'Parameters:';
        echo '';
        echo -e '\t-k:\tName of key to be revoked';
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
	KEY=$(cat "$SSH_STORE$2.pub");
fi

shift
done

# Revoke the key from /root/.ssh/authorized_keys
grep -v "$KEY" /root/.ssh/authorized_keys > /root/.ssh/authorized_keys.new; mv /root/.ssh/authorized_keys.new /root/.ssh/authorized_keys
