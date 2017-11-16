#!/bin/sh

testZip=$(opkg list-installed | grep -w 'zip')
testUnzip=$(opkg list-installed | grep -w 'unzip')

if [ -z "$testZip" ]; then
	echo "Not Installed";
else
	if [ -z "$testUnzip" ]; then
		echo "Not Installed";
	else
		echo "Installed";
	fi
fi
