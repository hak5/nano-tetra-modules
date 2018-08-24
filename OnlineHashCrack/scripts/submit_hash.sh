#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

[[ -f /tmp/OnlineHashCrack.progress ]] && {
  exit 0
}

LOG=/tmp/onlinehashcrack.log
EMAIL=`uci get onlinehashcrack.settings.email`
HASH=$1

touch /tmp/OnlineHashCrack.progress

rm -rf ${LOG}

if [ -n "$EMAIL" ]; then
  echo -e "Sent to www.onlinehashcrack.com web service successfully. Notification will be sent to ${EMAIL}. The following has been sent:" > ${LOG}
  echo -e "${HASH}" >> ${LOG}

  echo -e "" >> ${LOG}

  curl -s -v -d emailHashes="${EMAIL}" -d textareaHashes="${HASH}" https://www.onlinehashcrack.com/hash-cracking.php > /dev/null 2>> ${LOG}
else
  echo -e "Notification email not set in settings." > ${LOG}
fi

rm /tmp/OnlineHashCrack.progress
