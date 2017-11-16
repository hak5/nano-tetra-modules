#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

help() {
	echo "Usage: ./packKeys.sh <keydir> <opts>";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\tkeydir:\tDirectory where the key resides';
	echo -e '\t-f:\tFile names as string value';
	echo -e '\t-o:\tName of output file';
	echo '';
}

if  [ "$#" -lt 1 ]; then
	help;
	exit;
fi

# Define and clear out the download directory
DL_DIR="/pineapple/modules/Papers/includes/download/";
rm -rf $DL_DIR*

# Get the key directory and shift it out of the argument vectors
KEY_DIR="$1";
shift;

FILES='';
OUTPUT='';
export IFS=" ";

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-f" ]]; then
	for word in $2; do
		FILES="$FILES $KEY_DIR$word";
	done
fi
if [[ "$1" == "-o" ]]; then
	OUTPUT="$2";
fi

shift
done;

zip -j $DL_DIR$OUTPUT $FILES > /dev/null;
