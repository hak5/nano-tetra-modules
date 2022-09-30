#!/bin/sh
#2015 - Whistle Master

if grep addn-hosts /etc/dnsmasq.conf &> /dev/null; then
    /etc/init.d/dnsmasq stop && /etc/init.d/dnsmasq start
  else
    echo "no-dhcp-interface=" >> /etc/dnsmasq.conf
    echo "server=8.8.8.8" >> /etc/dnsmasq.conf
    echo "no-hosts" >> /etc/dnsmasq.conf
    echo "addn-hosts=/pineapple/modules/DNSMasqSpoof/hosts/dnsmasq.hosts" >> /etc/dnsmasq.conf

    /etc/init.d/dnsmasq stop && /etc/init.d/dnsmasq start
fi
