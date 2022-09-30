#!/bin/sh
#2015 - Whistle Master

logger "== KeyManager Install Script"

[[ -f /tmp/KeyManager.progress ]] && {
  exit 0
}

touch /tmp/KeyManager.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    opkg update
  	opkg install openssh-client-utils
    opkg install sshpass
  elif [ "$2" = "sd" ]; then
    opkg update
  	opkg install openssh-client-utils --dest sd
    opkg install sshpass --dest sd
  fi

  if [ ! -f /root/.ssh/known_hosts ]; then
    touch /root/.ssh/known_hosts
  fi

  touch /etc/config/keymanager
  echo "config keymanager 'module'" > /etc/config/keymanager
  echo "config keymanager 'settings'" >> /etc/config/keymanager

  uci set keymanager.module.installed=1
  uci commit keymanager.module.installed

elif [ "$1" = "remove" ]; then
	opkg remove openssh-client-utils
  opkg remove sshpass

	rm -rf /etc/config/keymanager
fi

rm /tmp/KeyManager.progress
