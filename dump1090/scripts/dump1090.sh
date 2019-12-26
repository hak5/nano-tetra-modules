#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`

OPTIONS=''
GAIN=`uci get dump1090.settings.gain`
FREQUENCY=`uci get dump1090.settings.frequency`
METRICS=`uci get dump1090.settings.metrics`
AGC=`uci get dump1090.settings.agc`
AGGRESSIVE=`uci get dump1090.settings.aggressive`

CSV=`uci get dump1090.settings.csv`

if [ "$1" = "start" ]; then

	if [ -n "$GAIN" ]; then
		OPTIONS="${OPTIONS} --gain ${GAIN}"
	fi

	if [ -n "$FREQUENCY" ]; then
		OPTIONS="${OPTIONS} --freq ${FREQUENCY}"
	fi

	if [ "$METRICS" -ne 0 ]; then OPTIONS="${OPTIONS} --metric"; fi
	if [ "$AGC" -ne 0 ]; then OPTIONS="${OPTIONS} --enable-agc"; fi
	if [ "$AGGRESSIVE" -ne 0 ]; then OPTIONS="${OPTIONS} --aggressive"; fi

	dump1090 --net --net-http-port 9090 ${OPTIONS} 1> /pineapple/modules/dump1090/log/output_${MYTIME}.log 2> /tmp/dump1090_capture.log &

	sleep 2

	if [ "$CSV" -ne 0 ]; then
		nc 127.0.0.1 30003 > /pineapple/modules/dump1090/log/output_${MYTIME}.csv &
	fi

elif [ "$1" = "stop" ]; then
  	killall -9 dump1090
	killall -9 nc
fi
