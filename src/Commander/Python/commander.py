#!/usr/bin/python

"""
Commander.py - Python Backend for the WiFi Pineapple Commander module.
Version 2 Codename: Electric Boogaloo

Thanks to: sebkinne & tesla

Foxtrot (C) 2016 <foxtrotnull@gmail.com>
"""

import os
import ConfigParser
import sys
import socket
import time
import string
import select
import errno

class Commander(object):
	print "[*] WiFi Pineapple Commander Module"
	print "[*] peace to: sebkinne & tesla"
	
	def run(self):
		while True:
			self.fillBuffer()
			self.parseCommands()

	def parseConfig(self):
		if os.path.exists('commander.conf'):
			self.config = ConfigParser.RawConfigParser()
			self.config.read('commander.conf')
			if self.config.has_section('Network') and self.config.has_section('Security') and self.config.has_section('Commands') and self.config.has_section('Other'):
				print "[*] Valid configuration file found!"
				print ""
			else:
				print "[!] No valid configuration file found... Exiting!"
				sys.exit(1)

		self.server = self.config.get('Network', 'Server')
		self.port = self.config.getint('Network', 'Port')
		self.nick = self.config.get('Network', 'Nickname')
		self.channel = self.config.get('Network', 'Channel')
		self.master = self.config.get('Security', 'Master')
		self.trigger = self.config.get('Security', 'Trigger')
		self.commands = self.config.options('Commands')
		self.debugmode = self.config.get('Other', 'Debug')

	def printConfig(self):
		print "[*] Using the following connection settings:"
		print "    %s" % self.server
		print "    %d" % self.port
		print "    %s" % self.nick
		print "    %s" % self.channel
		print ""

		print "[*] Using the following security settings:"
		print "    Master: %s" % self.master
		print "    Trigger: %s\n" % self.trigger

		print "[*] Listing commands:"
		for command in self.commands:
			print "    %s%s" % (self.trigger, command)
		print ""

	def connect(self):
		self.sock = socket.socket()
		print "[*] Connecting!"
		self.sock.connect((self.server, self.port))
		print "[*] Sending nick and user information"
		self.sock.send('NICK %s\r\n' % self.nick)
		self.sock.send('USER %s 8 * :%s\r\n' % (self.nick, self.nick))
		time.sleep(2)
		self.sock.send('JOIN %s\r\n' % self.channel)
		self.sock.send('PRIVMSG %s :Connected.\r\n' % self.channel)
		print "[*] Connected!\n"

	def fillBuffer(self):
		self.buff = ""
		self.sock.setblocking(0)

		readable, _, _ = select.select([self.sock], [], [])
		
		if self.sock in readable:
			self.buff = ""
			cont = True
			while cont:
				try:
					self.buff += self.sock.recv(1024)
				except socket.error,e:
					if e.errno != errno.EWOULDBLOCK:
						sys.exit(1)
					cont = False

	def parseCommands(self):
		for line in self.buff.split('\r\n'):
			if self.debugmode.lower() == "on":
				print line
				
			line = line.split()

			if 'PING' in line:
				print "[*] Replying to ping\n"
				self.sock.send('PONG ' + line.split()[1] + '\r\n')

			for command in self.commands:
				if line and line[0].lower().startswith(":" + self.master.lower() + "!"):
					if ":" + self.trigger + command in line:
						print "[*] Found command %s%s\n" % (self.trigger, command)
						self.sock.send('PRIVMSG %s :Executing command %s\r\n' % (self.channel, command))
						cmd = self.config.get('Commands', command)
						os.system(cmd)



if __name__ == '__main__':
	commander = Commander()
	commander.parseConfig()
	commander.printConfig()
	commander.connect()
	commander.run()
