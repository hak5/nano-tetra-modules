#!/bin/sh
# author: catatonicprime
# date: March 2018

pkg=tor

touch /tmp/$pkg.progress

if [ "$1" = "install" ]; then
  opkg update
  if [ "$2" = "internal" ]; then
    opkg install $pkg
  elif [ "$2" = "sd" ]; then
    opkg install $pkg --dest sd
  fi
elif [ "$1" = "remove" ]; then
    opkg remove $pkg
	sed -i '/tor\/scripts\/autostart_tor.sh/d' /etc/rc.local
fi

rm /tmp/$pkg.progress
