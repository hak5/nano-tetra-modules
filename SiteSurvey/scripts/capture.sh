#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

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

  # make sure the folder exists
  if [ ! -d /pineapple/modules/SiteSurvey/capture ]; then
    mkdir /pineapple/modules/SiteSurvey/capture
  fi

  airodump-ng -c ${CHANNEL} --bssid ${BSSID} -w /pineapple/modules/SiteSurvey/capture/capture_${MYTIME} ${MYMONITOR} &> /dev/null &

  echo -e "Capture is running..." >> ${LOG}

elif [ "$1" = "stop" ]; then

  killall -9 airodump-ng
	rm ${LOG}
  rm ${LOCK}

fi
