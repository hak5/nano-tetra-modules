#!/bin/sh
#2015 - Whistle Master

logger "== SITESURVEY INSTALL SCRIPT"

[[ -f /tmp/SiteSurvey.progress ]] && {
  exit 0
}

touch /tmp/SiteSurvey.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
	   opkg update
	   opkg install mdk3
  elif [ "$2" = "sd" ]; then
        opkg update
        opkg install mdk3 --dest=sd
  fi

  touch /etc/config/sitesurvey
  echo "config sitesurvey 'module'" > /etc/config/sitesurvey

  uci set sitesurvey.module.installed=1
  uci commit sitesurvey.module.installed

elif [ "$1" = "remove" ]; then
    rm -rf /etc/config/sitesurvey
fi

rm /tmp/SiteSurvey.progress
