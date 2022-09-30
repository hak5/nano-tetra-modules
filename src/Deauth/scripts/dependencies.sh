#!/bin/sh
#2015 - Whistle Master

#export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
#export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/Deauth.progress ]] && {
  exit 0
}

touch /tmp/Deauth.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	opkg update
    opkg install mdk3
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install mdk3 --dest sd
  fi

  touch /etc/config/deauth
  echo "config deauth 'run'" > /etc/config/deauth
  echo "config deauth 'settings'" >> /etc/config/deauth
  echo "config deauth 'autostart'" >> /etc/config/deauth
  echo "config deauth 'module'" >> /etc/config/deauth

  uci set deauth.settings.mode='normal'
  uci commit deauth.settings.mode

  uci set deauth.module.installed=1
  uci commit deauth.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove mdk3
    rm -rf /etc/config/deauth
fi

rm /tmp/Deauth.progress
