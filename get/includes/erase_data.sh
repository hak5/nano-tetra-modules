#!/bin/sh

DIRECTORY="/usb/get"
if [ -d "${DIRECTORY}" ]; then
	rm -rf ${DIRECTORY}/*
fi

DIRECTORY="/sd/get"
if [ -d "$DIRECTORY" ]; then
	rm -rf $DIRECTORY/*
fi

DIRECTORY="/pineapple/components/infusions/get/includes/comments"
if [ -d "$DIRECTORY" ]; then
	rm -rf $DIRECTORY/*
fi

FILENAME="/pineapple/components/infusions/get/includes/get.database"
if [ -e "$FILENAME" ]; then
	rm -rf $FILENAME*
fi

FILENAME="/pineapple/components/infusions/get/includes/get.database~"
if [ -e "$FILENAME" ]; then
	rm -rf $FILENAME*
fi