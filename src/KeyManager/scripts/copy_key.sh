#!/bin/sh
#2015 - Whistle Master



[[ -f /tmp/KeyManager.progress ]] && {
  exit 0
}

LOG=/tmp/keymanager.log
HOST=`uci get keymanager.settings.host`
PORT=`uci get keymanager.settings.port`
USER=`uci get keymanager.settings.user`
PASSWORD=$1

touch /tmp/KeyManager.progress

rm -rf ${LOG}

if ! grep -q ${HOST} /root/.ssh/known_hosts; then
    echo -e "Cannot find ${HOST} in known_hosts. Adding it now." > ${LOG}

    ssh-keyscan -p ${PORT} ${HOST} > /tmp/tmp_hosts
    cat /tmp/tmp_hosts >> /root/.ssh/known_hosts

    echo -e "Added the following to /root/.ssh/known_hosts:" >> ${LOG}
    cat /tmp/tmp_hosts >> ${LOG}
fi

sshpass -p ${PASSWORD} /pineapple/modules/KeyManager/scripts/ssh-copy-id.sh -i /root/.ssh/id_rsa.pub -p ${PORT} ${USER}@${HOST}
echo -e "Copied local public key to remote host." >> ${LOG}

rm /tmp/KeyManager.progress
