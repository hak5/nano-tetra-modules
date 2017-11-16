#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

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
