#!/bin/bash

if [ $# -lt 1 ]; then
        echo "Usage: $0 <iface>";
        exit;
fi


ifconfig $1 | grep inet | awk '{split($2,a,":"); print a[2]}' | tr -d '\n'
