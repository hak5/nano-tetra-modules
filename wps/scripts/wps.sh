#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`
MYCMD=`cat /tmp/wps.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/wps.run
elif [ "$1" = "stop" ]; then
 	killall -9 reaver
	killall -9 bully

	rm -rf /tmp/wps.run
fi