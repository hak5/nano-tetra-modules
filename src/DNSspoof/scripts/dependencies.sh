#!/bin/sh
#2015 - Whistle Master

logger "== DNSSpoof Dependencies Installer"

[[ -f /tmp/DNSspoof.progress ]] && {
  exit 0
}

touch /tmp/DNSspoof.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    opkg update
    opkg install dnsspoof
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install dnsspoof --dest sd
  fi

  touch /etc/config/dnsspoof
  echo "config dnsspoof 'run'" > /etc/config/dnsspoof
  echo "config dnsspoof 'autostart'" >> /etc/config/dnsspoof
  echo "config dnsspoof 'module'" >> /etc/config/dnsspoof

  uci set dnsspoof.module.installed=1
  uci commit dnsspoof.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove dnsspoof
  rm -rf /etc/config/dnsspoof
fi

rm /tmp/DNSspoof.progress
