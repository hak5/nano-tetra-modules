#!/bin/sh
#2015 - Whistle Master

[[ -f /tmp/OnlineHashCrack.progress ]] && {
  exit 0
}

LOG=/tmp/onlinehashcrack.log
EMAIL=`uci get onlinehashcrack.settings.email`
FILE=$1

touch /tmp/OnlineHashCrack.progress

rm -rf ${LOG}

if [ -n "$EMAIL" ]; then
  if [ ! -f ${FILE} ]; then
    echo -e "File ${FILE} does not exist." > ${LOG}
  else
    echo -e "Sent to onlinehashcrack.com API service successfully. Notification will be sent to ${EMAIL}. The following has been sent:" > ${LOG}
    echo -e "${FILE}" >> ${LOG}
    echo -e "" >> ${LOG}

    curl -s -v -X POST -F email="${EMAIL}" -F file=@${FILE} https://api.onlinehashcrack.com > /dev/null 2>> ${LOG}
  fi
else
  echo -e "Notification email not set in settings." > ${LOG}
fi

rm /tmp/OnlineHashCrack.progress
