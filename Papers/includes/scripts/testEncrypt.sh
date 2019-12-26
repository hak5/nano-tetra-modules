#!/bin/sh


help() {
        echo "Usage: ./testEncrypt.sh <opts>";
        echo '';
        echo 'Parameters:';
        echo '';
	echo -e '\t-d:\tDirectory where key resides';
        echo -e '\t-k:\tName of key to test';
        echo '';
}

if  [ "$#" -lt 2 ]; then
        help;
        exit;
fi

KEY=''
KEYDIR=''

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-k" ]]; then
	KEY="$2.key"
fi
if [[ "$1" == "-d" ]]; then
        KEYDIR="$2"
fi

shift
done;

openssl rsa -in $KEYDIR$KEY -passin pass:_ | awk 'NR==0;'
