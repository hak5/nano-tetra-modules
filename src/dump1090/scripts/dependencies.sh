#!/bin/sh
#2015 - Whistle Master

logger "== DUMP1090 INSTALL SCRIPT"

[[ -f /tmp/dump1090.progress ]] && {
  exit 0
}

touch /tmp/dump1090.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	opkg update
    opkg install dump1090
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install dump1090 --dest sd

    ln -s /sd/usr/share/dump1090/ /usr/share/dump1090
  fi

  touch /etc/config/dump1090
  echo "config dump1090 'settings'" > /etc/config/dump1090
  echo "config dump1090 'module'" >> /etc/config/dump1090

  uci set dump1090.module.installed=1
  uci commit dump1090.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove dump1090
  rm -rf /etc/config/dump1090
fi

rm /tmp/dump1090.progress
