#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYCMD="tcpdump `cat /tmp/tcpdump.run`"
DUMPPATH='/pineapple/modules/tcpdump/dump/'

echo "Starting if"
if [ ! -d "$DUMPPATH" ]; then
  # Control will enter here if $DUMPPATH doesn't exist.
  # Recursively make the path
  mkdir -p "$DUMPPATH"
fi


if [ "$1" = "start" ]; then
	eval ${MYCMD}
	rm -rf /tmp/tcpdump.run
elif [ "$1" = "stop" ]; then
  killall tcpdump
	rm -rf /tmp/tcpdump.run
fi
