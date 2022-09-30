#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Usage: $0 <serial>";
	exit;
fi

orig=$1
serial=""

i=${#orig}

while [ $i -gt 0 ]
do
	i=$(($i-2));
	serial="$serial${orig:$i:2}-"
done


echo $serial"00"
