#!/bin/bash

#  Author: sud0nick
#  Date:   Sept 2016

help() {
	echo "Usage: ./unpackKeyArchive.sh -f <fileName>";
	echo '';
	echo 'Parameters:';
	echo '';
	echo -e '\t-f:\tFile name without extension';
	echo '';
}

if  [ "$#" -lt 2 ]; then
	help;
	exit;
fi

# Define and clear out the download directory
DL_DIR="/pineapple/modules/Papers/includes/upload/";

FILE='';
export IFS=" ";

while [ "$#" -gt 0 ]
do

if [[ "$1" == "-f" ]]; then
	FILE="$DL_DIR$2"
fi

shift
done;

output=$(unzip $FILE.zip -d $DL_DIR);

# If the archive contained a .pub these
# keys are destined for the SSH directory
if [[ $output == *".pub"* ]]; then
	mv $FILE.pub /pineapple/modules/Papers/includes/ssh/
	mv $FILE.key /pineapple/modules/Papers/includes/ssh/
fi

# If the archive contained a .cer these
# keys are destined for the SSL directory
if [[ $output == *".cer"* ]]; then
	mv $FILE.cer /pineapple/modules/Papers/includes/ssl/
	mv $FILE.key /pineapple/modules/Papers/includes/ssl/
fi

# Clear the download directory
rm -rf $DL_DIR*
