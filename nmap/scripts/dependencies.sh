#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/nmap.progress ]] && {
  exit 0
}

touch /tmp/nmap.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	   opkg update
     opkg install nmap
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install nmap --dest sd
    ln -s /sd/usr/bin/nmap /usr/bin/nmap
  fi

  touch /etc/config/nmap
  echo "config nmap 'module'" > /etc/config/nmap

  uci set nmap.module.installed=1
  uci commit nmap.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove nmap
    rm -rf /etc/config/nmap
fi

rm /tmp/nmap.progress
