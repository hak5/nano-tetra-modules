#!/bin/sh
#2015 - Whistle Master

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/sd/lib:/sd/usr/lib
export PATH=$PATH:/sd/usr/bin:/sd/usr/sbin

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
    echo -e "Sent to www.onlinehashcrack.com web service successfully. Notification will be sent to ${EMAIL}. The following has been sent:" > ${LOG}
    echo -e "${FILE}" >> ${LOG}

    echo -e "" >> ${LOG}

    curl -s -v -F submit="Submit" -F emailWpa="${EMAIL}" -F wpaFile=@${FILE} https://www.onlinehashcrack.com/wifi-wpa-rsna-psk-crack.php > /dev/null 2>> ${LOG}

  fi
else
  echo -e "Notification email not set in settings." > ${LOG}
fi

rm /tmp/OnlineHashCrack.progress
