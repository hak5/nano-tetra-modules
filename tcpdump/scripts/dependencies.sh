#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/tcpdump.progress ]] && {
  exit 0
}

touch /tmp/tcpdump.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	 opkg update
     opkg install tcpdump
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install tcpdump --dest sd
  fi
  
  if [ ! -f /usr/lib/libpcap.so ] && [ -f /usr/lib/libpcap.so.1.3 ]; then
  	ln -s /usr/lib/libpcap.so /usr/lib/libpcap.so.1.3
  fi

  touch /etc/config/tcpdump
  echo "config tcpdump 'module'" > /etc/config/tcpdump

  uci set tcpdump.module.installed=1
  uci commit tcpdump.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove tcpdump
    rm -rf /etc/config/tcpdump
fi

rm /tmp/tcpdump.progress
