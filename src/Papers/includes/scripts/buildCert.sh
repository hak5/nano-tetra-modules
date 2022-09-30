#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

help() {
	echo "Usage: ./buildCert.sh <opts>";
	echo '';
	echo 'Required Parameters:';
	echo -e '\t-k,--keyName:\tName of exported key files';
	echo '';
	echo 'Optional Parameters:';
	echo '';
	echo -e '\t-b,--bitSize:\tBitsize of keys (Default: 2048)';
	echo -e '\t-d,--days:\tNumber days keys will be valid (Default: 365)';
	echo -e '\t-sa,--sigAlgo:\tSignature algorithm (Default: SHA-256)';
	echo '';
	echo 'Distinguished Name Options:';
	echo '';
	echo -e '\t-c,--country:\t\t\tCountry Code';
	echo -e '\t-st,--state:\t\t\tState or Province';
	echo -e '\t-l,--locality:\t\t\tCity or Locality';
	echo -e '\t-o,--orgnaization:\t\tOrganization';
	echo -e '\t-ou,--organizationalUnit:\tOrganizational Unit';
	echo -e '\t-cn,--commonName:\t\tCommon Name';
	echo -e '\t--config:\t\t\tOpenSSL config file';
	echo '';
}

if [ "$#" -lt 1 ]; then
	help;
        exit;
fi

# Defaults
SIGALGO="sha256";
BITSIZE=2048;
DAYS=365;

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-d" || "$1" == "--days" ]]; then
	DAYS="$2";
fi
if [[ "$1" == "-b" || "$1" == "--bitSize" ]]; then
	BITSIZE="$2";
fi
if [[ "$1" == "-k" || "$1" == "--keyName" ]]; then
	KEYNAME="$2";
fi
if [[ "$1" == "-sa" || "$1" == "--sigAlgo" ]]; then
	SIGALGO="$2";
fi
if [[ "$1" == "-c" || "$1" == "--country" ]]; then
	COUNTRY="$2"
fi
if [[ "$1" == "-st" || "$1" == "--state" ]]; then
	STATE="$2"
fi
if [[ "$1" == "-l" || "$1" == "--locality" ]]; then
	LOCALITY="$2"
fi
if [[ "$1" == "-o" || "$1" == "--organization" ]]; then
	ORGANIZATION="$2"
fi
if [[ "$1" == "-ou" || "$1" == "--organizationalUnit" ]]; then
	OU="$2"
fi
if [[ "$1" == "-cn" || "$1" == "--commonName" ]]; then
	CN="$2"
fi
if [[ "$1" == "--config" ]]; then
	CONF="$2"
fi

shift
done

if [ -z "$DAYS" ] || [ -z "$BITSIZE" ] || [ -z "$KEYNAME" ]; then
	echo "[-] You must enter at least key name, bitsize, and days valid parameters.";
	help;
	exit;
fi

subj="";
ssl_store="/pineapple/modules/Papers/includes/ssl/";

if [ -n "$COUNTRY" ]; then
	subj="$subj/C=$COUNTRY";
fi
if [ -n "$STATE" ]; then
        subj="$subj/ST=$STATE";
fi
if [ -n "$LOCALITY" ]; then
        subj="$subj/L=$LOCALITY";
fi
if [ -n "$ORGANIZATION" ]; then
	subj=$subj"/O=$ORGANIZATION";
fi
if [ -n "$OU" ]; then
        subj="$subj/OU=$OU";
fi
if [ -n "$CN" ]; then
	subj="$subj/CN=$CN";
fi

if [ -n "$subj" ]; then
	openssl req -x509 -nodes -batch -days $DAYS -newkey rsa:$BITSIZE -$SIGALGO -keyout $ssl_store$KEYNAME.key -out $ssl_store$KEYNAME.cer -subj "$subj";
else
	openssl req -x509 -nodes -batch -days $DAYS -newkey rsa:$BITSIZE -$SIGALGO -keyout $ssl_store$KEYNAME.key -out $ssl_store$KEYNAME.cer -config $CONF;
fi

echo "Complete";
