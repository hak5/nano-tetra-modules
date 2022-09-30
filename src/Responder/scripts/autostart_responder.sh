#!/bin/sh
#2015 - Whistle Master

MYTIME=`date +%s`
MYINTERFACE=`uci get responder.autostart.interface`

OPTIONS=''
BASIC=`uci get responder.settings.basic`
WREDIR=`uci get responder.settings.wredir`
NBTNS=`uci get responder.settings.NBTNS`
FINGERPRINT=`uci get responder.settings.fingerprint`
WPAD=`uci get responder.settings.wpad`
FORCEWPADAUTH=`uci get responder.settings.forceWpadAuth`
PROXYAUTH=`uci get responder.settings.proxyAuth`
FORCELMDOWNGRADE=`uci get responder.settings.forceLmDowngrade`
VERBOSE=`uci get responder.settings.verbose`
ANALYSE=`uci get responder.settings.analyse`

if [ -z "$MYINTERFACE" ]; then
    MYINTERFACE="br-lan"
fi

uci set responder.run.interface=${MYINTERFACE}
uci commit responder.run.interface

cd /pineapple/modules/Responder/dep/responder/

if [ "$BASIC" -ne 0 ]; then OPTIONS="${OPTIONS} --basic"; fi
if [ "$WREDIR" -ne 0 ]; then OPTIONS="${OPTIONS} --wredir"; fi
if [ "$NBTNS" -ne 0 ]; then OPTIONS="${OPTIONS} --NBTNSdomain"; fi
if [ "$FINGERPRINT" -ne 0 ]; then OPTIONS="${OPTIONS} --fingerprint"; fi
if [ "$WPAD" -ne 0 ]; then OPTIONS="${OPTIONS} --wpad"; fi
if [ "$FORCEWPADAUTH" -ne 0 ]; then OPTIONS="${OPTIONS} --ForceWpadAuth"; fi
if [ "$PROXYAUTH" -ne 0 ]; then OPTIONS="${OPTIONS} --ProxyAuth"; fi
if [ "$FORCELMDOWNGRADE" -ne 0 ]; then OPTIONS="${OPTIONS} --lm"; fi
if [ "$VERBOSE" -ne 0 ]; then OPTIONS="${OPTIONS} --verbose"; fi
if [ "$ANALYSE" -ne 0 ]; then OPTIONS="${OPTIONS} --analyze"; fi

./Responder.py -I ${MYINTERFACE} ${OPTIONS} > /dev/null 2 &
