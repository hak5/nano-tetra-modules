#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/KeyManager_key.progress ]] && {
  exit 0
}

touch /tmp/KeyManager_key.progress

ssh-keygen -N "" -f /root/.ssh/id_rsa

rm /tmp/KeyManager_key.progress
