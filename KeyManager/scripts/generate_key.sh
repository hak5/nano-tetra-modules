#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/KeyManager_key.progress ]] && {
  exit 0
}

touch /tmp/KeyManager_key.progress

ssh-keygen -N "" -f /root/.ssh/id_rsa

rm /tmp/KeyManager_key.progress
