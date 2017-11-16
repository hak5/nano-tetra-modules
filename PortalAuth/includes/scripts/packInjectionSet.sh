#!/bin/sh

tar -czf /pineapple/modules/PortalAuth/includes/downloads/$1.tar.gz -C /pineapple/modules/PortalAuth/includes/scripts/injects/ $1/
echo "Complete"
