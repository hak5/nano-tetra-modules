#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/SiteSurvey.progress ]] && {
  exit 0
}

touch /tmp/SiteSurvey.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	   opkg update
  elif [ "$2" = "sd" ]; then
    opkg update
  fi

  touch /etc/config/sitesurvey
  echo "config sitesurvey 'module'" > /etc/config/sitesurvey

  uci set sitesurvey.module.installed=1
  uci commit sitesurvey.module.installed

  mkdir /pineapple/modules/SiteSurvey/capture

elif [ "$1" = "remove" ]; then
    rm -rf /etc/config/sitesurvey
fi

rm /tmp/SiteSurvey.progress
