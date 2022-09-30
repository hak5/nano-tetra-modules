#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYINTERFACE=`uci get dnsspoof.autostart.interface`
HOSTSFILE="/etc/pineapple/spoofhost"

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set dnsspoof.run.interface=${MYINTERFACE}
uci commit dnsspoof.run.interface

dnsspoof -i ${MYINTERFACE} -f ${HOSTSFILE} > /dev/null 2> /pineapple/modules/DNSspoof/log/output_${MYTIME}.log
