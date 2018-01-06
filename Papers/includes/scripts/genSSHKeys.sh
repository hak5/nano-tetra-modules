#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

help() {
        echo "Usage: ./genSSHKeys.sh <opts>";
        echo '';
        echo 'Required Parameters:';
        echo -e '\t-k,--keyName:\tName of exported key files';
        echo '';
        echo 'Optional Parameters:';
        echo '';
        echo -e '\t-b,--bitSize:\tBitsize of keys (Default: 2048)';
	echo -e '\t-p,--pass:\tPassword for private key';
	echo -e '\t-c,--comment:\tInclude a comment in the public key (Default: root@Pineapple)';
	echo '';
}

if [ "$#" -lt 1 ]; then
        help;
        exit;
fi

# Defaults
BITSIZE=2048;
PASSWORD='';
SSH_STORE="/pineapple/modules/Papers/includes/ssh/";
COMMENT='root@Pineapple';

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-k" || "$1" == "--keyName" ]]; then
        KEYNAME="$2";
fi

if [[ "$1" == "-b" || "$1" == "--bitSize" ]]; then
        BITSIZE="$2";
fi

if [[ "$1" == "-p" || "$1" == "--pass" ]]; then
        PASSWORD="$2";
fi

if [[ "$1" == "-c" || "$1" == "--comment" ]]; then
	COMMENT="$2"
fi

shift
done

if [[ -z $KEYNAME ]]; then
        help;
        exit;
fi

ssh-keygen -q -b $BITSIZE -t rsa -N "$PASSWORD" -f $SSH_STORE$KEYNAME.key -C $COMMENT
mv $SSH_STORE$KEYNAME.key.pub $SSH_STORE$KEYNAME.pub
