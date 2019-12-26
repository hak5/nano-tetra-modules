#!/bin/sh
#2015 - Whistle Master



MYTIME=`date +%s`

killall sslsplit

if [ "$1" = "start" ]; then

	echo '1' > /proc/sys/net/ipv4/ip_forward
	iptables-save > /pineapple/modules/SSLsplit/rules/saved
	iptables -X
	iptables -F
	iptables -t nat -F
	iptables -P INPUT ACCEPT
	iptables -P FORWARD ACCEPT
	iptables -P OUTPUT ACCEPT

	sh /pineapple/modules/SSLsplit/rules/iptables

	iptables -t nat -A POSTROUTING -j MASQUERADE

	sslsplit -D -l /pineapple/modules/SSLsplit/connections.log -L /pineapple/modules/SSLsplit/log/output_${MYTIME}.log -k /pineapple/modules/SSLsplit/cert/certificate.key -c /pineapple/modules/SSLsplit/cert/certificate.crt ssl 0.0.0.0 8443 tcp 0.0.0.0 8080

elif [ "$1" = "stop" ]; then

	rm -rf /pineapple/modules/SSLsplit/connections.log

	iptables -F
	iptables -X
	iptables -t nat -F
	iptables -t nat -X
	iptables -t mangle -F
	iptables -t mangle -X
	iptables -P INPUT ACCEPT
	iptables -P FORWARD ACCEPT
	iptables -P OUTPUT ACCEPT
	
	iptables-restore < /pineapple/modules/SSLsplit/rules/saved
fi
