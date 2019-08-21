#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/SSLsplit.progress ]] && {
  exit 0
}

touch /tmp/SSLsplit.progress

if [ "$1" = "install" ]; then
  if [ "$2" = "internal" ]; then
    opkg update
    opkg install sslsplit
  	opkg install openssl-util
  	opkg install libevent2
  	opkg install libevent2-core
  	opkg install libevent2-extra
  	opkg install libevent2-openssl
  	opkg install libevent2-pthreads
  elif [ "$2" = "sd" ]; then
    opkg update
    opkg install sslsplit --dest sd
  	opkg install openssl-util --dest sd
  	opkg install libevent2 --dest sd
  	opkg install libevent2-core --dest sd
  	opkg install libevent2-extra --dest sd
  	opkg install libevent2-openssl --dest sd
  	opkg install libevent2-pthreads --dest sd
  fi

	openssl genrsa -out /pineapple/modules/SSLsplit/cert/certificate.key 1024
	openssl req -new -nodes -x509 -sha1 -out /pineapple/modules/SSLsplit/cert/certificate.crt -key /pineapple/modules/SSLsplit/cert/certificate.key -config /pineapple/modules/SSLsplit/cert/openssl.cnf -extensions v3_ca -subj '/O=SSLsplit Root CA/CN=SSLsplit Root CA/' -set_serial 0 -days 3650

  touch /etc/config/sslsplit
  echo "config sslsplit 'module'" > /etc/config/sslsplit

  uci set sslsplit.module.installed=1
  uci commit sslsplit.module.installed

elif [ "$1" = "remove" ]; then
  opkg remove sslsplit
	opkg remove openssl-util
	opkg remove libevent2
	opkg remove libevent2-core
	opkg remove libevent2-extra
	opkg remove libevent2-openssl
	opkg remove libevent2-pthreads

	rm -rf /etc/config/sslsplit
fi

rm /tmp/SSLsplit.progress
