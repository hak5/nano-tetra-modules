#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get p0f.autostart.interface`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set p0f.run.interface=${MYINTERFACE}
uci commit p0f.run.interface

p0f -i ${MYINTERFACE} -o /pineapple/modules/p0f/log/output_${MYTIME}.log 2>&1 &
