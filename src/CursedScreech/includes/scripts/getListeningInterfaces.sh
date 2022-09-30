#!/bin/bash

IFCS=$(ifconfig | cut -d " " -f1 | awk 'NF==1{print $1}')
for iface in ${IFCS[@]}; do
	if [[ $iface == "lo" ]]; then
		continue
	fi
	if [[ $(ifconfig $iface | grep inet) != "" ]]; then
		echo $iface
	fi
done
