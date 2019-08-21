#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get p0f.run.interface`

if [ "$1" = "start" ]; then
  p0f -i ${MYINTERFACE} -o /pineapple/modules/p0f/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  killall -9 p0f
fi
