#!/bin/sh

#  Author: sud0nick & adde88
#  Date:   18.10.2019

/etc/init.d/nginx stop > /dev/null;
opkg remove zip unzip nginx-ssl > /dev/null;
opkg install nginx > /dev/null;
