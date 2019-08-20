#!/bin/sh
#2015 - Whistle Master

[[ -f /tmp/OnlineHashCrack.progress ]] && {
  exit 0
}

touch /tmp/OnlineHashCrack.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    opkg update
  	opkg install curl
  elif [ "$2" = "sd" ]; then
    opkg update
  	opkg install curl --dest sd
  fi

  touch /etc/config/onlinehashcrack
  echo "config onlinehashcrack 'settings'" > /etc/config/onlinehashcrack
  echo "config onlinehashcrack 'module'" >> /etc/config/onlinehashcrack

  uci set onlinehashcrack.module.installed=1
  uci commit onlinehashcrack.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove curl
  rm -rf /etc/config/onlinehashcrack
fi

rm /tmp/OnlineHashCrack.progress
