#  Author: sud0nick
#  Date:   Apr 2016

from subprocess import call
import os

class ConfigHelper:

	def __init__(self, sslDir = "/etc/nginx/ssl/"):
		self.nginxConf = "/etc/nginx/nginx.conf"
		self.lines = [f for f in open(self.nginxConf)]
		self.ssl_dir = sslDir
		self.serverBlockIndex = self.getServerBlockIndex()
		self.currentSSLCerts = self.getCurrentSSLCerts()
		

	def checkSSLCertsExist(self):
		flags = [".key", ".cer"]
		if os.path.isdir(self.ssl_dir):
			for file in os.listdir(self.ssl_dir):
				for flag in flags:
					if flag in file:
						flags.remove(flag)
		if flags:
			return False
		else:
			return True
			
	def getCurrentSSLCerts(self):
		certs = []
		index = self.serverBlockIndex
		for line in self.lines[index:]:
			if "ssl_certificate" in line:
				i = line.rfind("/")
				certs.append(line[i+1:].strip(";\n"))

		return certs
		
		
	def getServerBlockIndex(self):
		index = 0
		for line in self.lines:
			if ("listen" in line) and not ("80" in line or "443" in line):
					return index
			index = index + 1

		return False

	
	def checkSSLConfigStatus(self):
		index = self.serverBlockIndex
		for line in self.lines[index:]:
			if "1471 ssl;" in line:
				return True

		return False
		
		
	def addSSLConfig(self, keyName):

		# Check if SSL has already been configured for port 1471
		if self.checkSSLConfigStatus():
			return True

		index = 0
		cert = keyName + ".cer"
		key = keyName + ".key"

		with open(self.nginxConf, "w") as out:
			for line in self.lines:
				if index == self.serverBlockIndex:
					line = "\t\tlisten\t1471 ssl;\n"
				
				if index > self.serverBlockIndex:
					if "root   /pineapple/;" in line:
						self.lines.insert(index + 1, "\t\tssl_certificate /etc/nginx/ssl/" + cert + ";\n"
													"\t\tssl_certificate_key /etc/nginx/ssl/" + key + ";\n"
													"\t\tssl_protocols TLSv1 TLSv1.1 TLSv1.2;\n")
				index = index + 1
				out.write(line)
		call(["/etc/init.d/nginx", "reload"])
		
		return True
	
	def replaceSSLConfig(self, newKey):
		cert = newKey + ".cer"
		key = newKey + ".key"
		currentKey = self.currentSSLCerts[0].rsplit(".")[0]
		index = 0

		with open(self.nginxConf, "w") as out:
			for line in self.lines:
				if index > self.serverBlockIndex:
					if (currentKey + ".cer") in line:
						line = "\t\tssl_certificate /etc/nginx/ssl/" + cert + ";\n"
					
					if (currentKey + ".key") in line:
						line = "\t\tssl_certificate_key /etc/nginx/ssl/" + key + ";\n"
					
				index = index + 1
				out.write(line)
				
		call(["/etc/init.d/nginx", "reload"])
	
	
	def removeSSLConfig(self):
		index = 0
		with open(self.nginxConf, "w") as out:
			for line in self.lines:
				if index == self.serverBlockIndex:
					line = "\t\tlisten\t1471;\n"
				
				if index > self.serverBlockIndex:
					if "ssl_certificate" in line or "ssl_protocols" in line:
						continue
				
				index = index + 1
				out.write(line)
				
		call(["/etc/init.d/nginx", "reload"])
		