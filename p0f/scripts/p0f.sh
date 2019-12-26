#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYINTERFACE=`uci get p0f.run.interface`

if [ "$1" = "start" ]; then
  p0f -i ${MYINTERFACE} -o /pineapple/modules/p0f/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  killall -9 p0f
fi
