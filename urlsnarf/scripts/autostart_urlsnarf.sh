#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get urlsnarf.autostart.interface`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set urlsnarf.run.interface=${MYINTERFACE}
uci commit urlsnarf.run.interface

urlsnarf -i ${MYINTERFACE} > /pineapple/modules/urlsnarf/log/output_${MYTIME}.log &
