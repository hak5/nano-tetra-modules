#!/bin/sh
#2018 - Whistle Master + Small fix by Zylla

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/ngrep.progress ]] && {
  exit 0
}

touch /tmp/ngrep.progress
mkdir -p /tmp/ngrep
wget https://github.com/adde88/openwrt-useful-tools/tree/master -P /tmp/ngrep
NGREP=`grep -F "ngrep_" /tmp/ngrep/master | awk {'print $5'} | awk -F'"' {'print $2'}`

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	 opkg update
     opkg install "$NGREP"
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install "$NGREP" --dest sd
  fi

  if [ ! -f /usr/lib/libpcap.so ] && [ -f /usr/lib/libpcap.so.1.3 ]; then
  	ln -s /usr/lib/libpcap.so /usr/lib/libpcap.so.1.3
  fi

  touch /etc/config/ngrep
  echo "config ngrep 'module'" > /etc/config/ngrep

  uci set ngrep.module.installed=1
  uci commit ngrep.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove ngrep
    rm -rf /etc/config/ngrep
fi

rm /tmp/ngrep.progress
rm -rf /tmp/ngrep
