#!/bin/sh

testZip=$(opkg list-installed | grep -w 'zip')
testUnzip=$(opkg list-installed | grep -w 'unzip')
testBase64=$(opkg list-installed | grep -w 'coreutils-base64')
testNginxssl=$(opkg list-installed | grep -w 'nginx-ssl')

if [ -z "$testBase64" ]; then
  echo "Not Installed";
else
  if [ -z "$testZip" -a -z "$testNginxssl" ]; then
    echo "Not Installed";
  else
    if [ -z "$testUnzip" ]; then
      echo "Not Installed";
    else
      echo "Installed";
    fi
  fi
fi
