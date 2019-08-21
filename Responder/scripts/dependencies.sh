#!/bin/sh
#2015 - Whistle Master

[[ -f /tmp/Responder.progress ]] && {
  exit 0
}

touch /tmp/Responder.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
     opkg update
     opkg install python-logging
     opkg install python-openssl
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install python-logging --dest sd
    opkg install python-openssl --dest sd
  fi

  sed -i 's/^HTTP .*/HTTP = Off/g' /pineapple/modules/Responder/dep/responder/Responder.conf
  sed -i 's/^HTTPS.*/HTTPS = Off/g' /pineapple/modules/Responder/dep/responder/Responder.conf
  sed -i 's/^DNS.*/DNS = Off/g' /pineapple/modules/Responder/dep/responder/Responder.conf

  touch /etc/config/responder
  echo "config responder 'module'" > /etc/config/responder
  echo "config responder 'run'" >> /etc/config/responder
  echo "config responder 'settings'" >> /etc/config/responder
  echo "config responder 'autostart'" >> /etc/config/responder

  uci set responder.settings.SQL=1
  uci set responder.settings.SMB=1
  uci set responder.settings.Kerberos=1
  uci set responder.settings.FTP=1
  uci set responder.settings.POP=1
  uci set responder.settings.SMTP=1
  uci set responder.settings.IMAP=1
  uci set responder.settings.HTTP=0
  uci set responder.settings.HTTPS=0
  uci set responder.settings.DNS=0
  uci set responder.settings.LDAP=1
  uci commit responder.settings

  uci set responder.module.installed=1
  uci commit responder.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove python-logging
  opkg remove python-openssl
  rm -rf /etc/config/responder
fi

rm /tmp/Responder.progress
