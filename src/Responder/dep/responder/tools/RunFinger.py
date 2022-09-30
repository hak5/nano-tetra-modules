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
import re,sys,socket,struct
import datetime
import multiprocessing
from socket import *
from odict import OrderedDict
import optparse
from RunFingerPackets import *

__version__ = "0.7"

parser = optparse.OptionParser(usage='python %prog -i 10.10.10.224\nor:\npython %prog -i 10.10.10.0/24', version=__version__, prog=sys.argv[0])

parser.add_option('-i','--ip', action="store", help="Target IP address or class C", dest="TARGET", metavar="10.10.10.224", default=None)
parser.add_option('-g','--grep', action="store_true", dest="Grep", default=False, help="Output in grepable format")
options, args = parser.parse_args()

if options.TARGET is None:
    print "\n-i Mandatory option is missing, please provide a target or target range.\n"
    parser.print_help()
    exit(-1)

Timeout = 2
Host = options.TARGET
Grep = options.Grep

class Packet():
    fields = OrderedDict([
    ])
    def __init__(self, **kw):
        self.fields = OrderedDict(self.__class__.fields)
        for k,v in kw.items():
            if callable(v):
                self.fields[k] = v(self.fields[k])
            else:
                self.fields[k] = v
    def __str__(self):
        return "".join(map(str, self.fields.values()))

def longueur(payload):
    length = struct.pack(">i", len(''.join(payload)))
    return length

def GetBootTime(data):
    Filetime = int(struct.unpack('<q',data)[0])
    t = divmod(Filetime - 116444736000000000, 10000000)
    time = datetime.datetime.fromtimestamp(t[0])
    return time, time.strftime('%Y-%m-%d %H:%M:%S')


#####################

def IsSigningEnabled(data): 
    if data[39] == "\x0f":
        return True
    else:
        return False

def atod(a): 
    return struct.unpack("!L",inet_aton(a))[0]

def dtoa(d): 
    return inet_ntoa(struct.pack("!L", d))

def OsNameClientVersion(data):
	try:
		length = struct.unpack('<H',data[43:45])[0]
                if length > 255:
		   OsVersion, ClientVersion = tuple([e.replace('\x00','') for e in data[48+length:].split('\x00\x00\x00')[:2]])
		   return OsVersion, ClientVersion
                if length <= 255:
		   OsVersion, ClientVersion = tuple([e.replace('\x00','') for e in data[47+length:].split('\x00\x00\x00')[:2]])
		   return OsVersion, ClientVersion
	except:
	 	return "Could not fingerprint Os version.", "Could not fingerprint LanManager Client version"
def GetHostnameAndDomainName(data):
	try:
		DomainJoined, Hostname = tuple([e.replace('\x00','') for e in data[81:].split('\x00\x00\x00')[:2]])
                Time = GetBootTime(data[60:68])
                #If max length domain name, there won't be a \x00\x00\x00 delineator to split on
		if Hostname == '':
			DomainJoined = data[81:110].replace('\x00','')
			Hostname = data[113:].replace('\x00','')
		return Hostname, DomainJoined, Time
	except:
	 	return "Could not get Hostname.", "Could not get Domain joined"

def DomainGrab(Host):
    s = socket(AF_INET, SOCK_STREAM)
    try:
       s.settimeout(Timeout)
       s.connect(Host)
    except:
       pass
    try:
       h = SMBHeaderLanMan(cmd="\x72",mid="\x01\x00",flag1="\x00", flag2="\x00\x00")
       n = SMBNegoDataLanMan()
       packet0 = str(h)+str(n)
       buffer0 = longueur(packet0)+packet0
       s.send(buffer0)
       data = s.recv(2048)
       s.close()
       if data[8:10] == "\x72\x00":
          return GetHostnameAndDomainName(data)
    except:
       pass 

def SmbFinger(Host):
    s = socket(AF_INET, SOCK_STREAM)
    try:
       s.settimeout(Timeout)
       s.connect(Host)
    except:
       pass
    try:     
       h = SMBHeader(cmd="\x72",flag1="\x18",flag2="\x53\xc8")
       n = SMBNego(Data = SMBNegoData())
       n.calculate()
       packet0 = str(h)+str(n)
       buffer0 = longueur(packet0)+packet0
       s.send(buffer0)
       data = s.recv(2048)
       signing = IsSigningEnabled(data)
       if data[8:10] == "\x72\x00":
          head = SMBHeader(cmd="\x73",flag1="\x18",flag2="\x17\xc8",uid="\x00\x00")
          t = SMBSessionFingerData()
          packet0 = str(head)+str(t)
          buffer1 = longueur(packet0)+packet0  
          s.send(buffer1) 
          data = s.recv(2048)
       if data[8:10] == "\x73\x16":
          OsVersion, ClientVersion = OsNameClientVersion(data)
          return signing, OsVersion, ClientVersion
    except:
       pass


def SmbNullSession(Host):
    s = socket(AF_INET, SOCK_STREAM)
    try:
       s.settimeout(Timeout)
       s.connect(Host)
    except:
       pass 

    try:     
       h = SMBHeader(cmd="\x72",flag1="\x18", flag2="\x53\xc8")
       n = SMBNego(Data = SMBNegoData())
       n.calculate()
       packet0 = str(h)+str(n)
       buffer0 = longueur(packet0)+packet0
       s.send(buffer0)
       data = s.recv(2048)
       try:
           if data[8:10] == "\x72\x00":
              head = SMBHeader(cmd="\x73",flag1="\x18", flag2="\x53\xc8")
              t = SMBSessionData()
              t.calculate()
              final = t 
              packet1 = str(head)+str(final)
              buffer1 = longueur(packet1)+packet1
              s.send(buffer1)
              data = s.recv(2048)

           if data[8:10] == "\x73\x16":
              head = SMBHeader(cmd="\x73",flag1="\x18", flag2="\x17\xc8",uid=data[32:34],mid="\x80\x00")
              t = SMBSession2()
              t.calculate()
              final = t 
              packet1 = str(head)+str(final)
              buffer1 = longueur(packet1)+packet1
              s.send(buffer1)
              data = s.recv(2048)

           if data[8:10] == "\x73\x00":
              head = SMBHeader(cmd="\x75",flag1="\x18", flag2="\x07\xc8",uid=data[32:34],mid="\xc0\x00")
              t = SMBTreeConnectData(Path="\\\\"+Host[0]+"\\IPC$")
              t.calculate()
              packet1 = str(head)+str(t)
              buffer1 = longueur(packet1)+packet1  
              s.send(buffer1)
              data = s.recv(2048)

           if data[8:10] == "\x75\x00":
              global Guest
              Guest = True
              head = SMBHeader(cmd="\x25",flag1="\x18", flag2="\x07\xc8",uid=data[32:34],tid=data[28:30],mid="\xc0\x00")
              t = SMBTransRAPData()
              t.calculate()
              packet1 = str(head)+str(t)
              buffer1 = longueur(packet1)+packet1  
              s.send(buffer1)
              data = s.recv(2048)
              if data[9:13] == "\x05\x02\x00\xc0":
                 return Guest, True
              else:
                 return Guest, False
           else:
              return False, False
       except Exception:
           pass

    except:
       pass

##################
#run it
def ShowResults(Host):
    try:
       Hostname, DomainJoined, Time = DomainGrab(Host)
       Signing, OsVer, LanManClient = SmbFinger(Host)
       NullSess, Ms17010 = SmbNullSession(Host)
       print "Retrieving information for %s..."%Host[0]
       print "SMB signing:", Signing
       print "Null Sessions Allowed:", NullSess
       print "Vulnerable to MS17-010:", Ms17010
       print "Server Time:", Time[1]
       print "OS version: '%s'\nLanman Client: '%s'"%(OsVer, LanManClient)
       print "Machine Hostname: '%s'\nThis machine is part of the '%s' domain\n"%(Hostname, DomainJoined)
    except:
       pass

def ShowSmallResults(Host):
    s = socket(AF_INET, SOCK_STREAM)
    try:
       s.settimeout(Timeout)
       s.connect(Host)
    except:
       return False

    try:
       Hostname, DomainJoined, Time = DomainGrab(Host)
       Signing, OsVer, LanManClient = SmbFinger(Host)
       NullSess, Ms17010 = SmbNullSession(Host)
       Message = "['%s', Os:'%s', Domain:'%s', Signing:'%s', Time:'%s', Null Session: %s, MS17-010: %s]"%(Host[0], OsVer, DomainJoined, Signing, Time[1],NullSess, Ms17010)
       print Message
    except:
       raise
       pass

def IsGrepable():
    if options.Grep:
       return True
    else:
       return False

def RunFinger(Host):
    m = re.search("/", str(Host))
    if m :
       net,_,mask = Host.partition('/')
       mask = int(mask)
       net = atod(net)
       threads = []
       for host in (dtoa(net+n) for n in range(0, 1<<32-mask)):
           if IsGrepable():
              p = multiprocessing.Process(target=ShowSmallResults, args=((host,445),))
              threads.append(p)
              p.start()
           else:
              p = multiprocessing.Process(target=ShowResults, args=((host,445),))
              threads.append(p)
              p.start()
    else:
      if IsGrepable():
          ShowSmallResults((Host,445))
      else:
          ShowResults((Host,445))

RunFinger(Host)

