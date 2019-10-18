#!/bin/sh

#  Author: sud0nick & adde88
#  Date:   18.10.2019

opkg update > /dev/null;
/etc/init.d/nginx stop > /dev/null;
opkg remove nginx > /dev/null;
opkg install zip unzip nginx-ssl > /dev/null;
/etc/init.d/nginx restart > /dev/null;
echo "Complete"
