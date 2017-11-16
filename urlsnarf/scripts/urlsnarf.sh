#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get urlsnarf.run.interface`

if [ "$1" = "start" ]; then
	urlsnarf -i ${MYINTERFACE} > /pineapple/modules/urlsnarf/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  killall urlsnarf
fi
