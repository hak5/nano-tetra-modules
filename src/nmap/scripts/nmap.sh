#!/bin/sh
#2015 - Whistle Master

logger "== STARTING NMAP SCRIPT"

MYTIME=`date +%s`
MYCMD=`cat /tmp/nmap.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	mv /tmp/nmap.scan /pineapple/modules/nmap/scan/scan_${MYTIME}
	rm -rf /tmp/nmap.run
elif [ "$1" = "stop" ]; then
  	killall -9 nmap
	rm -rf /tmp/nmap.run
	rm -rf /tmp/nmap.scan
fi
