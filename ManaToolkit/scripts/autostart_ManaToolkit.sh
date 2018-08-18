#!/bin/sh
#2018 - Zylla / adde88@gmail.com

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get ManaToolkit.autostart.interface`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="wlan1"
fi

uci set ManaToolkit.run.interface=${MYINTERFACE}
uci commit ManaToolkit.run.interface

launch-mana ${MYINTERFACE} > /pineapple/modules/ManaToolkit/log/output_${MYTIME}.log 2>&1 &
