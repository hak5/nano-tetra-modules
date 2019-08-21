#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/SSLsplit_certificate.progress ]] && {
  exit 0
}

touch /tmp/SSLsplit_certificate.progress

# Generate the SSL certificate authority and key for SSLsplit to use
openssl genrsa -out /pineapple/modules/SSLsplit/cert/certificate.key 1024
openssl req -new -nodes -x509 -sha1 -out /pineapple/modules/SSLsplit/cert/certificate.crt -key /pineapple/modules/SSLsplit/cert/certificate.key -config /pineapple/modules/SSLsplit/cert/openssl.cnf -extensions v3_ca -subj '/O=SSLsplit Root CA/CN=SSLsplit Root CA/' -set_serial 0 -days 3650

rm /tmp/SSLsplit_certificate.progress
