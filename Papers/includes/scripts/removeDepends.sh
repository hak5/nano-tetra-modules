#!/bin/sh

#  Author: sud0nick & adde88
#  Date:   July 17, 2020

opkg update > /dev/null;
opkg remove zip unzip coreutils-base64 nginx-ssl > /dev/null;
opkg install nginx > /dev/null;
