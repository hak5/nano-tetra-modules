#!/bin/sh

#  Author: sud0nick
#  Date:   Dec 2018

# Location of SSL keys
SSL_STORE="/pineapple/modules/Papers/includes/ssl/";

help() {
	echo "Get certificate properties via OpenSSL";
	echo "Usage: ./getCertInfo.sh <opts>";
	echo '';
	echo 'NOTE:';
	echo "Current SSL store is at $SSL_STORE";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-k:\tKey from which to retrieve properties';
	echo '';
}

if [ "$#" -lt 1 ]; then
        help;
        exit;
fi

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-k" ]]; then
	KEY="$SSL_STORE/$2";
fi

shift
done;

ISSUER=$(openssl x509 -in $KEY -noout -issuer | sed 's/^[^=]*=//g');
FINGERPRINT=$(openssl x509 -in $KEY -noout -fingerprint | sed 's/^[^=]*=//g');
SUBJECT=$(openssl x509 -in $KEY -noout -subject | sed 's/^[^=]*=//g');
START_DATE=$(openssl x509 -in $KEY -noout -startdate | sed 's/^[^=]*=//g');
END_DATE=$(openssl x509 -in $KEY -noout -enddate | sed 's/^[^=]*=//g');
SERIAL=$(openssl x509 -in $KEY -noout -serial | sed 's/^[^=]*=//g');
ALT_NAMES=$(openssl x509 -in $KEY -noout -text | grep DNS | sed 's/^[^:]*://g');

echo "issuer=$ISSUER";
echo "fingerprint=$FINGERPRINT";
echo "subject=$SUBJECT";
echo "start=$START_DATE";
echo "end=$END_DATE";
echo "serial=$SERIAL";
echo "dns=$ALT_NAMES";
