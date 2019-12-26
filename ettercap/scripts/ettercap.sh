#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`
MYCMD=`cat /tmp/ettercap.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/ettercap.run
elif [ "$1" = "stop" ]; then
  	killall -9 ettercap
	rm -rf /tmp/ettercap.run
fi
