#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

LOG=/tmp/deauth.log
MYPATH='/pineapple/modules/Deauth/'

MYMONITOR=''
MYINTERFACE=`uci get deauth.run.interface`

SPEED=`uci get deauth.settings.speed`
CHANNEL=`uci get deauth.settings.channel`
MODE=`uci get deauth.settings.mode`

WHITELIST=${MYPATH}lists/whitelist.lst
TMPWHITELIST=${MYPATH}lists/whitelist.tmp
BLACKLIST=${MYPATH}lists/blacklist.lst
TMPBLACKLIST=${MYPATH}lists/blacklist.tmp

if [ "$1" = "start" ]; then

	killall -9 mdk3
	rm ${TMPBLACKLIST}
	rm ${TMPWHITELIST}
	rm ${LOG}

	echo -e "Starting Deauth..." > ${LOG}

	if [ -z "$MYINTERFACE" ]; then
		MYINTERFACE=`iwconfig 2> /dev/null | grep "Mode:Master" | awk '{print $1}' | head -1`
	else
		MYFLAG=`iwconfig 2> /dev/null | awk '{print $1}' | grep ${MYINTERFACE}`

		if [ -z "$MYFLAG" ]; then
		    MYINTERFACE=`iwconfig 2> /dev/null | grep "Mode:Master" | awk '{print $1}' | head -1`
		fi
	fi

	if [ -z "$MYMONITOR" ]; then
		MYMONITOR=`iwconfig 2> /dev/null | grep "Mode:Monitor" | awk '{print $1}' | grep ${MYINTERFACE}`

		MYFLAG=`iwconfig 2> /dev/null | awk '{print $1}' | grep ${MYMONITOR}`

		if [ -z "$MYFLAG" ]; then
		    airmon-ng start ${MYINTERFACE}
		    MYMONITOR=`iwconfig 2> /dev/null | grep "Mode:Monitor" | awk '{print $1}' | grep ${MYINTERFACE}`
		fi
	else
		MYFLAG=`iwconfig 2> /dev/null | awk '{print $1}' | grep ${MYMONITOR}`

		if [ -z "$MYFLAG" ]; then
		    airmon-ng start ${MYINTERFACE}
		    MYMONITOR=`iwconfig 2> /dev/null | grep "Mode:Monitor" | awk '{print $1}' | grep ${MYINTERFACE}`
		fi
	fi

	grep -hv -e ^# ${WHITELIST} -e ^$ > ${TMPWHITELIST}
	grep -hv -e ^# ${BLACKLIST} -e ^$ > ${TMPBLACKLIST}

	echo -e "Interface : ${MYINTERFACE}" >> ${LOG}
	echo -e "Monitor : ${MYMONITOR}" >> ${LOG}

	if [ -n "$SPEED" ]; then
		echo -e "Speed : ${SPEED}" >> ${LOG}
		SPEED="-s ${SPEED}"
	else
		echo -e "Speed : default" >> ${LOG}
		SPEED=
	fi

	if [ -n "$CHANNEL" ]; then
		echo -e "Channel : ${CHANNEL}" >> ${LOG}
		CHANNEL="-c ${CHANNEL}"
	else
		echo -e "Channel : default" >> ${LOG}
		CHANNEL=
	fi

	ifconfig ${MYINTERFACE} down
	ifconfig ${MYINTERFACE} up

	if [ ${MODE} == "whitelist" ]; then
	  echo -e "Mode : ${MODE}" >> ${LOG}
		MODE="-w ${TMPWHITELIST}"
	elif [ ${MODE} == "blacklist" ]; then
	  echo -e "Mode : ${MODE}" >> ${LOG}
		MODE="-b ${TMPBLACKLIST}"
	elif [ ${MODE} == "normal" ]; then
	  echo -e "Mode : ${MODE}" >> ${LOG}
		MODE=""
	else
	  echo -e "Mode : default" >> ${LOG}
		MODE=""
	fi

	uci set deauth.run.interface=${MYMONITOR}
	uci commit deauth.run.interface

	mdk3 ${MYMONITOR} d ${SPEED} ${CHANNEL} ${MODE} >> ${LOG} &

elif [ "$1" = "stop" ]; then
  killall -9 mdk3
	rm ${TMPBLACKLIST}
	rm ${TMPWHITELIST}
	rm ${LOG}
fi
