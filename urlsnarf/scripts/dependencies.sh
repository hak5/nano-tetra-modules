#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/urlsnarf.progress ]] && {
  exit 0
}

touch /tmp/urlsnarf.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
     opkg update
     opkg install urlsnarf
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install urlsnarf --dest sd
  fi

  touch /etc/config/urlsnarf
  echo "config urlsnarf 'run'" > /etc/config/urlsnarf
  echo "config urlsnarf 'autostart'" >> /etc/config/urlsnarf
  echo "config urlsnarf 'module'" >> /etc/config/urlsnarf

  uci set urlsnarf.module.installed=1
  uci commit urlsnarf.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove urlsnarf
  rm -rf /etc/config/urlsnarf
fi

rm /tmp/urlsnarf.progress
