#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYCMD=`cat /tmp/ngrep.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/ngrep.run
elif [ "$1" = "stop" ]; then
  	killall -9 ngrep
	rm -rf /tmp/ngrep.run
fi
