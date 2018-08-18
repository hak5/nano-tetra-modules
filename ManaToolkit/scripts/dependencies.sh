#!/bin/sh
#2018 - Zylla / adde88@gmail.com

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/ManaToolkit.progress ]] && {
  exit 0
}

touch /tmp/ManaToolkit.progress
mkdir -p /tmp/ManaToolkit

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    if [ -d /sd ]; then
      exit 0
    fi
	wget https://github.com/adde88/hostapd-mana-openwrt/raw/master/bin/ar71xx/packages/base/asleap_2.2-1_ar71xx.ipk -P /tmp/ManaToolkit
	wget https://github.com/adde88/hostapd-mana-openwrt/raw/master/bin/ar71xx/packages/base/hostapd-mana_2.6-13_ar71xx.ipk -P /tmp/ManaToolkit
    opkg update
    opkg install /tmp/ManaToolkit/*.ipk sslsplit --force-overwrite
    #opkg install hostapd-mana sslsplit
  elif [ "$2" = "sd" ]; then
	wget https://github.com/adde88/hostapd-mana-openwrt/raw/master/bin/ar71xx/packages/base/asleap_2.2-1_ar71xx.ipk -P /tmp/ManaToolkit
	wget https://github.com/adde88/hostapd-mana-openwrt/raw/master/bin/ar71xx/packages/base/hostapd-mana_2.6-13_ar71xx.ipk -P /tmp/ManaToolkit
    opkg update
    opkg install /tmp/ManaToolkit/*.ipk sslsplit --dest sd --force-overwrite
    #opkg install hostapd-mana sslsplit --dest sd
    ln -s /sd/etc/mana-toolkit /etc/mana-toolkit
  fi

  cp /etc/mana-toolkit/hostapd-mana.conf /etc/mana-toolkit/hostapd-mana.default.conf
  touch /etc/config/ManaToolkit
  echo "config ManaToolkit 'module'" > /etc/config/ManaToolkit
  echo "config ManaToolkit 'run'" >> /etc/config/ManaToolkit
  echo "config ManaToolkit 'autostart'" >> /etc/config/ManaToolkit

  uci set ManaToolkit.module.installed=1
  uci set ManaToolkit.autostart.interface=wlan1
  uci set ManaToolkit.autostart.upstream=br-lan
  uci set ManaToolkit.run.upstream=br-lan
  uci set ManaToolkit.run.interface=wlan1
  uci commit ManaToolkit

  /etc/init.d/stunnel stop
  /etc/init.d/stunnel disable

elif [ "$1" = "remove" ]; then
    opkg remove hostapd-mana asleap
    rm -rf /etc/config/ManaToolkit
fi

rm /tmp/ManaToolkit.progress
rm -rf /tmp/ManaToolkit

install-mana-depends
