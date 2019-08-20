#!/bin/sh
#2015 - Whistle Master

[[ -f /tmp/Occupineapple.progress ]] && {
  exit 0
}

touch /tmp/Occupineapple.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	   opkg update
     opkg install mdk3
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install mdk3 --dest sd
  fi

  touch /etc/config/occupineapple
  echo "config occupineapple 'run'" > /etc/config/occupineapple
  echo "config occupineapple 'settings'" >> /etc/config/occupineapple
  echo "config occupineapple 'autostart'" >> /etc/config/occupineapple
  echo "config occupineapple 'module'" >> /etc/config/occupineapple

  uci set occupineapple.module.installed=1
  uci commit occupineapple.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove mdk3
  rm -rf /etc/config/occupineapple
fi

rm /tmp/Occupineapple.progress
