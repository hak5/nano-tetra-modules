#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`
MYCMD=`cat /tmp/wps.run`
MODULEDIR=/pineapple/modules/wps
LOGDIR=$MODULEDIR/log
 
# ensure log directory is created
if [ ! -d $LOGDIR ]; then
	mkdir $LOGDIR
fi

if [ "$1" = "start" ]; then
	eval ${MYCMD}
elif [ "$1" = "stop" ]; then
  killall -9 reaver
	killall -9 bully

	rm -rf /tmp/wps.run
fi
