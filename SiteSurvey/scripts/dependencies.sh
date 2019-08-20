#!/bin/sh
#2015 - Whistle Master

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

elif [ "$1" = "remove" ]; then
    rm -rf /etc/config/sitesurvey
fi

rm /tmp/SiteSurvey.progress
