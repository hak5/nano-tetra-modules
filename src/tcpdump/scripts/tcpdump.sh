#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`
MYCMD=`cat /tmp/tcpdump.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/tcpdump.run
elif [ "$1" = "stop" ]; then
  	killall -9 tcpdump
	rm -rf /tmp/tcpdump.run
fi