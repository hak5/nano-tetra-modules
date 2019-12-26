#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
LOG=/tmp/SiteSurvey.log
LOCK=/tmp/SiteSurvey_capture.lock

MYMONITOR=''
MYINTERFACE=$2
BSSID=$3
CHANNEL=$4

if [ "$1" = "start" ]; then
  killall -9 airodump-ng
  rm ${LOG}
  rm ${LOCK}

	echo -e "Starting Capture..." > ${LOG}

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

  echo -e "Monitor : ${MYMONITOR}" >> ${LOG}
  echo -e "BSSID : ${BSSID}" >> ${LOG}
  echo -e "Channel : ${CHANNEL}" >> ${LOG}

  echo ${BSSID} > ${LOCK}

  airodump-ng -c ${CHANNEL} --bssid ${BSSID} -w /pineapple/modules/SiteSurvey/capture/capture_${MYTIME} ${MYMONITOR} &> /dev/null &

  echo -e "Capture is running..." >> ${LOG}

elif [ "$1" = "stop" ]; then
  killall -9 airodump-ng
  rm ${LOG}
  rm ${LOCK}
fi
