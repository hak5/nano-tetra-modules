#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`
MYINTERFACE=`uci get urlsnarf.run.interface`

if [ "$1" = "start" ]; then
  urlsnarf -i ${MYINTERFACE} > /pineapple/modules/urlsnarf/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  killall urlsnarf
fi
