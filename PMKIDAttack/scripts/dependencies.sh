#!/bin/bash

export PATH=$PATH:/sd/bin:/sd/sbin:/sd/usr/sbin:/sd/usr/bin
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib

TIMESTAMP=`date "+[%Y-%m-%d %H:%M:%S]"`
LOGFILE="/pineapple/modules/PMKIDAttack/log/module.log"

# V6.0
# Dependencies from https://github.com/adde88/openwrt-useful-tools/tree/packages-19.07_mkvi
# https://github.com/adde88/openwrt-useful-tools/blob/a47fffeca89106bab9563c4a01d21871eb6b74f9/hcxtools-custom_6.0.3-5_mips_24kc.ipk
# https://github.com/adde88/openwrt-useful-tools/blob/a47fffeca89106bab9563c4a01d21871eb6b74f9/hcxdumptool-custom_6.0.7-6_mips_24kc.ipk
# HCXTOOLS_IPK="/pineapple/modules/PMKIDAttack/scripts/hcxtools-custom_6.0.3-5_mips_24kc.ipk"
# HCXDUMPTOOL_IPK="/pineapple/modules/PMKIDAttack/scripts/hcxdumptool-custom_6.0.7-6_mips_24kc.ipk"
HCXTOOLS_IPK="hcxtools"
HCXDUMPTOOL_IPK="hcxdumptool"

function add_log {
    echo $TIMESTAMP $1 >> $LOGFILE
}

if [[ "$1" == "" ]]; then
    add_log "Argument to script missing! Run with \"dependencies.sh [install|remove]\""
    exit 1
fi

[[ -f /tmp/PMKIDAttack.progress ]] && {
    exit 0
}

add_log "Starting dependencies script with argument: $1"
touch /tmp/PMKIDAttack.progress

if [[ "$1" = "install" ]]; then
    opkg update

    if [[ -e /sd ]]; then
        add_log "Installing on sd"

        opkg --dest sd install $HCXTOOLS_IPK >> $LOGFILE
        if [[ $? -ne 0 ]]; then
            add_log "ERROR: opkg --dest sd install $HCXTOOLS_IPK failed"
            exit 1
        fi

        opkg --dest sd install $HCXDUMPTOOL_IPK >> $LOGFILE
        if [[ $? -ne 0 ]]; then
            add_log "ERROR: opkg --dest sd install $HCXDUMPTOOL_IPK failed"
            exit 1
        fi
    else
        add_log "Installing on disk"

        opkg install $HCXTOOLS_IPK >> $LOGFILE
        if [[ $? -ne 0 ]]; then
            add_log "ERROR: opkg install $HCXTOOLS_IPK failed"
            exit 1
        fi

        opkg install $HCXDUMPTOOL_IPK >> $LOGFILE
        if [[ $? -ne 0 ]]; then
            add_log "ERROR: opkg install $HCXDUMPTOOL_IPK failed"
            exit 1
        fi
    fi

    touch /etc/config/pmkidattack
    uci add pmkidattack config
    uci set pmkidattack.@config[0].installed=1
    uci set pmkidattack.@config[0].attack=0
    uci set pmkidattack.@config[0].ssid=''
    uci set pmkidattack.@config[0].bssid=''
    uci commit pmkidattack

    add_log "Installation complete!"
fi

if [[ "$1" = "remove" ]]; then
    add_log "Removing dependencies"

    rm -rf /etc/config/PMKIDAttack

    opkg remove hcxtools-custom
    opkg remove hcxdumptool-custom

    uci set pmkidattack.@config[0].installed=0
    uci commit pmkidattack

    echo "execute this for manual check! opkg list-installed | grep hcx"
    add_log "Removing complete!"
fi

rm /tmp/PMKIDAttack.progress
