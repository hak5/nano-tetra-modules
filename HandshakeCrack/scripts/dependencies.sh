#!/bin/sh

[[ -f /tmp/HandshakeCrack.progress ]] && {
  exit 0
}

touch /tmp/HandshakeCrack.progress

if [[ "$1" = "install" ]]; then
  opkg update
  opkg install curl

  touch /etc/config/handshakecrack
  echo "config handshakecrack 'settings'" > /etc/config/handshakecrack
  echo "config handshakecrack 'module'" >> /etc/config/handshakecrack

  uci set handshakecrack.module.installed=1
  uci commit handshakecrack.module.installed

elif [[ "$1" = "remove" ]]; then
  opkg remove curl
  rm -rf /etc/config/handshakecrack
fi

rm /tmp/HandshakeCrack.progress
