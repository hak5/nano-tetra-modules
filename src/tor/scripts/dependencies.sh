#!/bin/sh
# author: catatonicprime
# date: March 2018



touch /tmp/tor.progress

if [ "$1" = "install" ]; then
  opkg update
  if [ "$2" = "internal" ]; then
    opkg install tor-geoip tor
  elif [ "$2" = "sd" ]; then
    opkg install tor-geoip tor --dest sd
  fi
  mkdir -p /etc/config/tor/
  cp /pineapple/modules/tor/files/torrc /etc/config/tor
  mkdir -p /etc/config/tor/services
  chown tor:tor /etc/config/tor/services
  chown root:tor /etc/tor/torrc
  chmod g+r /etc/tor/torrc
elif [ "$1" = "remove" ]; then
    opkg remove tor-geoip tor
    sed -i '/tor\/scripts\/autostart_tor.sh/d' /etc/rc.local
    rm -rf /etc/config/tor
fi

rm /tmp/tor.progress
