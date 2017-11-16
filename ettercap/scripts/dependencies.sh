#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/ettercap.progress ]] && {
  exit 0
}

touch /tmp/ettercap.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    opkg install ettercap

     sed -i "/redir_command_on = \"iptables/ s/# *//" /etc/ettercap/etter.conf
     sed -i "/redir_command_off = \"iptables/ s/# *//" /etc/ettercap/etter.conf

     sed -i 's/^\(ec_uid = \).*/\10/' /etc/ettercap/etter.conf
     sed -i 's/^\(ec_gid = \).*/\10/' /etc/ettercap/etter.conf

     echo 1 > /proc/sys/net/ipv4/ip_forward

  elif [ "$2" = "sd" ]; then
    opkg install ettercap --dest sd

    sed -i "/redir_command_on = \"iptables/ s/# *//" /etc/ettercap/etter.conf
    sed -i "/redir_command_off = \"iptables/ s/# *//" /etc/ettercap/etter.conf

    sed -i 's/^\(ec_uid = \).*/\10/' /etc/ettercap/etter.conf
    sed -i 's/^\(ec_gid = \).*/\10/' /etc/ettercap/etter.conf

    echo 1 > /proc/sys/net/ipv4/ip_forward

  fi

  touch /etc/config/ettercap
  echo "config ettercap 'module'" > /etc/config/ettercap

  uci set ettercap.module.installed=1
  uci commit ettercap.module.installed

elif [ "$1" = "remove" ]; then
    opkg remove ettercap
    rm -rf /etc/config/ettercap
fi

rm /tmp/ettercap.progress
