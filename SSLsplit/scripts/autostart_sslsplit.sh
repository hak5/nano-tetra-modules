#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

MYTIME=`date +%s`

killall sslsplit

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
echo "If this file exists, it means that the script succesfully detected the log directory! > /pineapple/modules/SSLsplit/log/islogthere || mkdir /pineapple/modules/SSLsplit/log/ && echo "Directory succesfully created!" > /pineapple/modules/SSLsplit/log/islogthere && printf "WRN: Log directory at '/pineapple/modules/SSLsplit/' was not found! We just created it for you! Your welcome. :) \n" > /pineapple/modules/SSLsplit/output_${MYTIME}.log
sslsplit -D -l /pineapple/modules/SSLsplit/connections.log -L /pineapple/modules/SSLsplit/log/output_${MYTIME}.log -k /pineapple/modules/SSLsplit/cert/certificate.key -c /pineapple/modules/SSLsplit/cert/certificate.crt ssl 0.0.0.0 8443 tcp 0.0.0.0 8080
