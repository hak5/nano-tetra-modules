#!/bin/sh
#2015 - Whistle Master

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
  
  if [ ! -f /usr/lib/libpcap.so ] && [ -f /usr/lib/libpcap.so.1.3 ]; then
  	ln -s /usr/lib/libpcap.so /usr/lib/libpcap.so.1.3
  fi

  touch /etc/config/wps
  echo "config wps 'module'" > /etc/config/wps

  uci set wps.module.installed=1
  uci commit wps.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove reaver
    opkg remove bully
    rm -rf /etc/config/wps
fi

rm /tmp/wps.progress
