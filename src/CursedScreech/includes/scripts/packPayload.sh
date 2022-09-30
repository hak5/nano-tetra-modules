#!/bin/sh

#  Author: sud0nick
#  Date:   Jan 2016

help() {
	echo "Usage: $0 <dir> <opts>";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\tdir:\tDirectory where the files reside';
	echo -e '\t-f:\tFile names as string value';
	echo -e '\t-o:\tName of output file';
	echo '';
}

if  [ "$#" -lt 1 ]; then
	help;
	exit;
fi

# Define and clear out the download directory
DL_DIR="/pineapple/modules/CursedScreech/includes/api/downloads/";
rm -rf $DL_DIR*

# Get the key directory and shift it out of the argument vectors
API_DIR="$1";
shift;

FILES='';
OUTPUT='';
export IFS=" ";

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-f" ]]; then
	for word in $2; do
		FILES="$FILES $API_DIR$word";
	done
fi
if [[ "$1" == "-o" ]]; then
	OUTPUT="$2";
fi

shift
done;

zip -j $DL_DIR$OUTPUT $FILES > /dev/null;
