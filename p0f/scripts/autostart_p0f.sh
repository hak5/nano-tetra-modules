#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYINTERFACE=`uci get p0f.autostart.interface`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set p0f.run.interface=${MYINTERFACE}
uci commit p0f.run.interface

p0f -i ${MYINTERFACE} -o /pineapple/modules/p0f/log/output_${MYTIME}.log 2>&1 &
