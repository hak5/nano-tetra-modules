#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYINTERFACE=`uci get dnsspoof.run.interface`
HOSTSFILE="/etc/pineapple/spoofhost"

if [ "$1" = "start" ]; then
	dnsspoof -i ${MYINTERFACE} -f ${HOSTSFILE} > /dev/null 2> /pineapple/modules/DNSspoof/log/output_${MYTIME}.log
elif [ "$1" = "stop" ]; then
  killall dnsspoof
fi
