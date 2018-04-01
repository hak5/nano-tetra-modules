#!/bin/sh
# author: catatonicprime
# date: March 2018

touch /tmp/tor.progress

if [ "$1" = "install" ]; then
  opkg update
  if [ "$2" = "internal" ]; then
    opkg install tor
  elif [ "$2" = "sd" ]; then
    opkg install tor --dest sd
  fi
  mkdir -p /etc/config/tor/
  cp /pineapple/modules/tor/files/torrc /etc/config/tor
  mkdir -p /var/lib/tor/services
  chown tor:tor /var/lib/tor/services
elif [ "$1" = "remove" ]; then
    opkg remove tor
	sed -i '/tor\/scripts\/autostart_tor.sh/d' /etc/rc.local
	rm -rf /etc/config/tor
fi

rm /tmp/tor.progress
