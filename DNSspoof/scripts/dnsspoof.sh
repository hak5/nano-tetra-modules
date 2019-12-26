#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYINTERFACE=`uci get dnsspoof.run.interface`
HOSTSFILE="/etc/pineapple/spoofhost"

if [ "$1" = "start" ]; then
	dnsspoof -i ${MYINTERFACE} -f ${HOSTSFILE} > /dev/null 2> /pineapple/modules/DNSspoof/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  	killall -9 dnsspoof
fi
