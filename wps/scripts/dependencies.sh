#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/wps.progress ]] && {
  exit 0
}

touch /tmp/wps.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	   opkg update

     opkg install reaver
     opkg install bully

  elif [ "$2" = "sd" ]; then
    opkg update

    opkg install reaver --dest sd
    opkg install bully --dest sd

  fi

  touch /etc/config/wps
  echo "config wps 'module'" > /etc/config/wps

  uci set wps.module.installed=1
  uci commit wps.module.installed

elif [ "$1" = "remove" ]; then
    rm -rf /etc/config/wps
fi

rm /tmp/wps.progress
