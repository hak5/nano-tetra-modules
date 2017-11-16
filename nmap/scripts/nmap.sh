#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYCMD=`cat /tmp/nmap.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	mv /tmp/nmap.scan /pineapple/modules/nmap/scan/scan_${MYTIME}
	rm -rf /tmp/nmap.run
elif [ "$1" = "stop" ]; then
  killall nmap
	rm -rf /tmp/nmap.run
	rm -rf /tmp/nmap.scan
fi
