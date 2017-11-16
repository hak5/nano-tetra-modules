#!/bin/sh

testZip=$(opkg list-installed | grep 'zip')

if [ -z "$testZip" ]; then
	echo "Not Installed";
else
	echo "Installed";
fi
