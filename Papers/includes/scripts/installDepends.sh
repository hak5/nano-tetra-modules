#!/bin/sh

#  Author: sud0nick & adde88
#  Date:   July 17, 2020

opkg update > /dev/null;
opkg remove nginx > /dev/null;
opkg install zip unzip coreutils-base64 nginx-ssl > /dev/null;
echo "Complete"
