#!/bin/sh

testZip=$(opkg list-installed | grep -w 'zip')
testUnzip=$(opkg list-installed | grep -w 'unzip')
testNginxssl=$(opkg list-installed | grep -w 'nginx-ssl')

if [ -z "$testZip" -a -z "$testNginxssl" ]; then
	echo "Not Installed";
else
	if [ -z "$testUnzip" ]; then
		echo "Not Installed";
	else
		echo "Installed";
	fi
fi
