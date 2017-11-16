#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get dnsspoof.autostart.interface`
HOSTSFILE="/etc/pineapple/spoofhost"

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set dnsspoof.run.interface=${MYINTERFACE}
uci commit dnsspoof.run.interface

dnsspoof -i ${MYINTERFACE} -f ${HOSTSFILE} > /dev/null 2> /pineapple/modules/DNSspoof/log/output_${MYTIME}.log
