#!/bin/sh
#2015 - Whistle Master

if [ "$1" = "start" ]; then
	echo "no-dhcp-interface=" >> /etc/dnsmasq.conf
	echo "server=8.8.8.8" >> /etc/dnsmasq.conf
	echo "no-hosts" >> /etc/dnsmasq.conf
	echo "addn-hosts=/pineapple/modules/DNSMasqSpoof/hosts/dnsmasq.hosts" >> /etc/dnsmasq.conf

  /etc/init.d/dnsmasq stop && /etc/init.d/dnsmasq start
elif [ "$1" = "stop" ]; then
	sed -i '/no-dhcp-interface=/d' /etc/dnsmasq.conf
	sed -i '/server=8.8.8.8/d' /etc/dnsmasq.conf
	sed -i '/no-hosts/d' /etc/dnsmasq.conf
	sed -i '/addn-hosts=\/pineapple\/modules\/DNSMasqSpoof\/hosts\/dnsmasq.hosts/d' /etc/dnsmasq.conf
	
  /etc/init.d/dnsmasq stop && /etc/init.d/dnsmasq start
fi
