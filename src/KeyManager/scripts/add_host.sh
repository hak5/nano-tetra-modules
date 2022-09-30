#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/KeyManager.progress ]] && {
  exit 0
}

LOG=/tmp/keymanager.log
HOST=`uci get keymanager.settings.host`
PORT=`uci get keymanager.settings.port`

touch /tmp/KeyManager.progress

rm -rf ${LOG}

ssh-keyscan -p ${PORT} ${HOST} > /tmp/tmp_hosts
cat /tmp/tmp_hosts >> /root/.ssh/known_hosts

echo -e "Added the following to /root/.ssh/known_hosts:" > ${LOG}
cat /tmp/tmp_hosts >> ${LOG}

rm /tmp/KeyManager.progress
