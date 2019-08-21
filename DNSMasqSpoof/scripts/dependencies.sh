#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/DNSMasqSpoof.progress ]] && {
  exit 0
}

touch /tmp/DNSMasqSpoof.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    echo '' > /dev/null
  elif [ "$2" = "sd" ]; then
    echo '' > /dev/null
  fi

  touch /etc/config/dnsmasqspoof
  echo "config dnsmasqspoof 'module'" > /etc/config/dnsmasqspoof

  uci set dnsmasqspoof.module.installed=1
  uci commit dnsmasqspoof.module.installed

elif [ "$1" = "remove" ]; then
    rm -rf /etc/config/dnsmasqspoof
fi

rm /tmp/DNSMasqSpoof.progress
