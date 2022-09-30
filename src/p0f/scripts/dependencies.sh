#!/bin/sh
#2015 - Whistle Master

[[ -f /tmp/p0f.progress ]] && {
  exit 0
}

touch /tmp/p0f.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
     opkg update
     opkg install p0f
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install p0f --dest sd
  fi
  
  if [ ! -f /usr/lib/libpcap.so ] && [ -f /usr/lib/libpcap.so.1.3 ]; then
  	ln -s /usr/lib/libpcap.so /usr/lib/libpcap.so.1.3
  fi

  touch /etc/config/p0f
  echo "config p0f 'module'" > /etc/config/p0f
  echo "config p0f 'run'" >> /etc/config/p0f
  echo "config p0f 'autostart'" >> /etc/config/p0f

  uci set p0f.module.installed=1
  uci commit p0f.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove p0f
  rm -rf /etc/config/p0f
fi

rm /tmp/p0f.progress
