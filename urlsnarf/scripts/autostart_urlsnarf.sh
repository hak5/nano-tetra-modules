#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`
MYINTERFACE=`uci get urlsnarf.autostart.interface`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set urlsnarf.run.interface=${MYINTERFACE}
uci commit urlsnarf.run.interface

urlsnarf -i ${MYINTERFACE} > /pineapple/modules/urlsnarf/log/output_${MYTIME}.log &
