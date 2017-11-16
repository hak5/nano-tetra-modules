#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYCMD=`cat /tmp/ngrep.run`

if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/ngrep.run
elif [ "$1" = "stop" ]; then
  killall ngrep
	rm -rf /tmp/ngrep.run
fi
