import random, struct
from socket import *
from time import sleep
from odict import OrderedDict

def longueur(payload):
    length = struct.pack(">i", len(''.join(payload)))
    return length

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

class SMBHeader(Packet):
    fields = OrderedDict([
        ("proto",      "\xff\x53\x4d\x42"),
        ("cmd",        "\x72"),
        ("error-code", "\x00\x00\x00\x00" ),
        ("flag1",      "\x00"),
        ("flag2",      "\x00\x00"),
        ("pidhigh",    "\x00\x00"),
        ("signature",  "\x00\x00\x00\x00\x00\x00\x00\x00"),
        ("reserved",   "\x00\x00"),
        ("tid",        "\x00\x00"),
        ("pid",        "\x00\x00"),
        ("uid",        "\x00\x00"),
        ("mid",        "\x00\x00"),
    ])

class SMBNego(Packet):
    fields = OrderedDict([
        ("Wordcount", "\x00"),
        ("Bcc", "\x62\x00"),
        ("Data", "")
    ])
    
    def calculate(self):
        self.fields["Bcc"] = struct.pack("<h",len(str(self.fields["Data"])))

class SMBNegoData(Packet):
    fields = OrderedDict([
        ("BuffType","\x02"),
        ("Dialect", "NT LM 0.12\x00"),
    ])

class SMBSessionFingerData(Packet):
    fields = OrderedDict([
        ("wordcount", "\x0c"),
        ("AndXCommand", "\xff"),
        ("reserved","\x00" ),
        ("andxoffset", "\x00\x00"),
        ("maxbuff","\x04\x11"),
        ("maxmpx", "\x32\x00"),
        ("vcnum","\x00\x00"),
        ("sessionkey", "\x00\x00\x00\x00"),
        ("securitybloblength","\x4a\x00"),
        ("reserved2","\x00\x00\x00\x00"),
        ("capabilities", "\xd4\x00\x00\xa0"),
        ("bcc1","\xb1\x00"), #hardcoded len here and hardcoded packet below, no calculation, faster.
        ("Data","\x60\x48\x06\x06\x2b\x06\x01\x05\x05\x02\xa0\x3e\x30\x3c\xa0\x0e\x30\x0c\x06\x0a\x2b\x06\x01\x04\x01\x82\x37\x02\x02\x0a\xa2\x2a\x04\x28\x4e\x54\x4c\x4d\x53\x53\x50\x00\x01\x00\x00\x00\x07\x82\x08\xa2\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x05\x01\x28\x0a\x00\x00\x00\x0f\x00\x57\x00\x69\x00\x6e\x00\x64\x00\x6f\x00\x77\x00\x73\x00\x20\x00\x32\x00\x30\x00\x30\x00\x32\x00\x20\x00\x53\x00\x65\x00\x72\x00\x76\x00\x69\x00\x63\x00\x65\x00\x20\x00\x50\x00\x61\x00\x63\x00\x6b\x00\x20\x00\x33\x00\x20\x00\x32\x00\x36\x00\x30\x00\x30\x00\x00\x00\x57\x00\x69\x00\x6e\x00\x64\x00\x6f\x00\x77\x00\x73\x00\x20\x00\x32\x00\x30\x00\x30\x00\x32\x00\x20\x00\x35\x00\x2e\x00\x31\x00\x00\x00\x00\x00"),
    ])

##Now Lanman
class SMBHeaderLanMan(Packet):
    fields = OrderedDict([
        ("proto", "\xff\x53\x4d\x42"),
        ("cmd", "\x72"),
        ("error-code", "\x00\x00\x00\x00" ),
        ("flag1", "\x08"),
        ("flag2", "\x01\xc8"),
        ("pidhigh", "\x00\x00"),
        ("signature", "\x00\x00\x00\x00\x00\x00\x00\x00"),
        ("reserved", "\x00\x00"),
        ("tid", "\x00\x00"),
        ("pid", "\x3c\x1b"),
        ("uid", "\x00\x00"),
        ("mid", "\x00\x00"),
    ])

#We grab the domain and hostname from the negotiate protocol answer, since it is in a Lanman dialect format.
class SMBNegoDataLanMan(Packet):
    fields = OrderedDict([
        ("Wordcount", "\x00"),
        ("Bcc", "\x0c\x00"),#hardcoded len here and hardcoded packet below, no calculation, faster.
        ("BuffType","\x02"),
        ("Dialect", "NT LM 0.12\x00"),

    ])

class SMBSessionData(Packet):
    fields = OrderedDict([
        ("wordcount", "\x0c"),
        ("AndXCommand", "\xff"),
        ("reserved","\x00" ),
        ("andxoffset", "\xec\x00"),              
        ("maxbuff","\x04\x11"),
        ("maxmpx", "\x32\x00"),
        ("vcnum","\x00\x00"),
        ("sessionkey", "\x00\x00\x00\x00"),
        ("securitybloblength","\x4a\x00"),
        ("reserved2","\x00\x00\x00\x00"),
        ("capabilities", "\xd4\x00\x00\xa0"),
        ("bcc1","\xb1\x00"),
        ("ApplicationHeaderTag","\x60"),
        ("ApplicationHeaderLen","\x48"),
        ("AsnSecMechType","\x06"),
        ("AsnSecMechLen","\x06"),
        ("AsnSecMechStr","\x2b\x06\x01\x05\x05\x02"),
        ("ChoosedTag","\xa0"),
        ("ChoosedTagStrLen","\x3e"),
        ("NegTokenInitSeqHeadTag","\x30"),
        ("NegTokenInitSeqHeadLen","\x3c"),
        ("NegTokenInitSeqHeadTag1","\xA0"),
        ("NegTokenInitSeqHeadLen1","\x0e"),
        ("NegTokenInitSeqNLMPTag","\x30"),
        ("NegTokenInitSeqNLMPLen","\x0c"),
        ("NegTokenInitSeqNLMPTag1","\x06"),
        ("NegTokenInitSeqNLMPTag1Len","\x0a"),
        ("NegTokenInitSeqNLMPTag1Str","\x2b\x06\x01\x04\x01\x82\x37\x02\x02\x0a"),
        ("NegTokenInitSeqNLMPTag2","\xa2"),
        ("NegTokenInitSeqNLMPTag2Len","\x2a"),
        ("NegTokenInitSeqNLMPTag2Octet","\x04"),
        ("NegTokenInitSeqNLMPTag2OctetLen","\x28"),
        ("NegTokenInitSeqMechSignature","\x4E\x54\x4c\x4d\x53\x53\x50\x00"),
        ("NegTokenInitSeqMechMessageType","\x01\x00\x00\x00"), 
        ("NegTokenInitSeqMechMessageFlags","\x07\x82\x08\xa2"), 
        ("NegTokenInitSeqMechMessageDomainNameLen","\x00\x00"),
        ("NegTokenInitSeqMechMessageDomainNameMaxLen","\x00\x00"),
        ("NegTokenInitSeqMechMessageDomainNameBuffOffset","\x00\x00\x00\x00"),
        ("NegTokenInitSeqMechMessageWorkstationNameLen","\x00\x00"),
        ("NegTokenInitSeqMechMessageWorkstationNameMaxLen","\x00\x00"),
        ("NegTokenInitSeqMechMessageWorkstationNameBuffOffset","\x00\x00\x00\x00"),
        ("NegTokenInitSeqMechMessageVersionHigh","\x05"),
        ("NegTokenInitSeqMechMessageVersionLow","\x01"),
        ("NegTokenInitSeqMechMessageVersionBuilt","\x28\x0a"),
        ("NegTokenInitSeqMechMessageVersionReserved","\x00\x00\x00"),
        ("NegTokenInitSeqMechMessageVersionNTLMType","\x0f"),
        ("NegTokenInitSeqMechMessageVersionTerminator","\x00"),
        ("nativeOs","Windows 2002 Service Pack 3 2600".encode('utf-16le')),
        ("nativeOsterminator","\x00\x00"),
        ("nativelan","Windows 2002 5.1".encode('utf-16le')),
        ("nativelanterminator","\x00\x00\x00\x00"),

    ])
    def calculate(self): 

        data1 = str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["AsnSecMechStr"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagStrLen"])+str(self.fields["NegTokenInitSeqHeadTag"])+str(self.fields["NegTokenInitSeqHeadLen"])+str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data2 = str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["AsnSecMechStr"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagStrLen"])+str(self.fields["NegTokenInitSeqHeadTag"])+str(self.fields["NegTokenInitSeqHeadLen"])+str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data3 = str(self.fields["NegTokenInitSeqHeadTag"])+str(self.fields["NegTokenInitSeqHeadLen"])+str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data4 = str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data5 = str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["AsnSecMechStr"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagStrLen"])+str(self.fields["NegTokenInitSeqHeadTag"])+str(self.fields["NegTokenInitSeqHeadLen"])+str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NegTokenInitSeqMechMessageVersionTerminator"])+str(self.fields["nativeOs"])+str(self.fields["nativeOsterminator"])+str(self.fields["nativelan"])+str(self.fields["nativelanterminator"])

        data6 = str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data7 = str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        data9 = str(self.fields["wordcount"])+str(self.fields["AndXCommand"])+str(self.fields["reserved"])+str(self.fields["andxoffset"])+str(self.fields["maxbuff"])+str(self.fields["maxmpx"])+str(self.fields["vcnum"])+str(self.fields["sessionkey"])+str(self.fields["securitybloblength"])+str(self.fields["reserved2"])+str(self.fields["capabilities"])+str(self.fields["bcc1"])+str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["AsnSecMechStr"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagStrLen"])+str(self.fields["NegTokenInitSeqHeadTag"])+str(self.fields["NegTokenInitSeqHeadLen"])+str(self.fields["NegTokenInitSeqHeadTag1"])+str(self.fields["NegTokenInitSeqHeadLen1"])+str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])+str(self.fields["NegTokenInitSeqNLMPTag2"])+str(self.fields["NegTokenInitSeqNLMPTag2Len"])+str(self.fields["NegTokenInitSeqNLMPTag2Octet"])+str(self.fields["NegTokenInitSeqNLMPTag2OctetLen"])+str(self.fields["NegTokenInitSeqMechSignature"])+str(self.fields["NegTokenInitSeqMechMessageType"])+str(self.fields["NegTokenInitSeqMechMessageFlags"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageDomainNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameMaxLen"])+str(self.fields["NegTokenInitSeqMechMessageWorkstationNameBuffOffset"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NegTokenInitSeqMechMessageVersionTerminator"])+str(self.fields["nativeOs"])+str(self.fields["nativeOsterminator"])+str(self.fields["nativelan"])+str(self.fields["nativelanterminator"])

        data10 = str(self.fields["NegTokenInitSeqNLMPTag"])+str(self.fields["NegTokenInitSeqNLMPLen"])+str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])
       
        data11 = str(self.fields["NegTokenInitSeqNLMPTag1"])+str(self.fields["NegTokenInitSeqNLMPTag1Len"])+str(self.fields["NegTokenInitSeqNLMPTag1Str"])

        ## Packet len
        self.fields["andxoffset"] = struct.pack("<H", len(data9)+32)
        ##Buff Len
        self.fields["securitybloblength"] = struct.pack("<H", len(data1))
        ##Complete Buff Len
        self.fields["bcc1"] = struct.pack("<H", len(data5))
        ##App Header
        self.fields["ApplicationHeaderLen"] = struct.pack("<B", len(data2))
        ##Asn Field 1
        self.fields["AsnSecMechLen"] = struct.pack("<B", len(str(self.fields["AsnSecMechStr"])))
        ##Asn Field 1
        self.fields["ChoosedTagStrLen"] = struct.pack("<B", len(data3))
        ##SpNegoTokenLen
        self.fields["NegTokenInitSeqHeadLen"] = struct.pack("<B", len(data4))
        ##NegoTokenInit
        self.fields["NegTokenInitSeqHeadLen1"] = struct.pack("<B", len(data10)) 
        ## Tag0 Len
        self.fields["NegTokenInitSeqNLMPLen"] = struct.pack("<B", len(data11)) 
        ## Tag0 Str Len
        self.fields["NegTokenInitSeqNLMPTag1Len"] = struct.pack("<B", len(str(self.fields["NegTokenInitSeqNLMPTag1Str"])))
        ## Tag2 Len
        self.fields["NegTokenInitSeqNLMPTag2Len"] = struct.pack("<B", len(data6))
        ## Tag3 Len
        self.fields["NegTokenInitSeqNLMPTag2OctetLen"] = struct.pack("<B", len(data7))


#########################################################################################################
class SMBSession2(Packet):
    fields = OrderedDict([
        ("wordcount", "\x0c"),
        ("AndXCommand", "\xff"),
        ("reserved","\x00"),
        ("andxoffset", "\xfa\x00"),
        ("maxbuff","\x04\x11"),
        ("maxmpx", "\x32\x00"),
        ("vcnum","\x01\x00"),
        ("sessionkey", "\x00\x00\x00\x00"),
        ("securitybloblength","\x59\x00"),
        ("reserved2","\x00\x00\x00\x00"),
        ("capabilities", "\xd4\x00\x00\xa0"),
        ("bcc1","\xbf\x00"),
        ("ApplicationHeaderTag","\xa1"), 
        ("ApplicationHeaderLen","\x57"), 
        ("AsnSecMechType","\x30"),       
        ("AsnSecMechLen","\x55"),   
        ("ChoosedTag","\xa2"),
        ("ChoosedTagLen","\x53"),
        ("ChoosedTag1","\x04"),
        ("ChoosedTag1StrLen","\x51"),
        ("NLMPAuthMsgSignature",  "\x4E\x54\x4c\x4d\x53\x53\x50\x00"),
        ("NLMPAuthMsgMessageType","\x03\x00\x00\x00"),
        ("NLMPAuthMsgLMChallengeLen","\x01\x00"),
        ("NLMPAuthMsgLMChallengeMaxLen","\x01\x00"),
        ("NLMPAuthMsgLMChallengeBuffOffset","\x50\x00\x00\x00"),
        ("NLMPAuthMsgNtChallengeResponseLen","\x00\x00"), 
        ("NLMPAuthMsgNtChallengeResponseMaxLen","\x00\x00"), 
        ("NLMPAuthMsgNtChallengeResponseBuffOffset","\x51\x00\x00\x00"),
        ("NLMPAuthMsgNtDomainNameLen","\x00\x00"),
        ("NLMPAuthMsgNtDomainNameMaxLen","\x00\x00"),
        ("NLMPAuthMsgNtDomainNameBuffOffset","\x48\x00\x00\x00"),
        ("NLMPAuthMsgNtUserNameLen","\x00\x00"), 
        ("NLMPAuthMsgNtUserNameMaxLen","\x00\x00"), 
        ("NLMPAuthMsgNtUserNameBuffOffset","\x48\x00\x00\x00"),
        ("NLMPAuthMsgNtWorkstationLen","\x08\x00"),
        ("NLMPAuthMsgNtWorkstationMaxLen","\x08\x00"),
        ("NLMPAuthMsgNtWorkstationBuffOffset","\x48\x00\x00\x00"),
        ("NLMPAuthMsgRandomSessionKeyMessageLen","\x00\x00"),
        ("NLMPAuthMsgRandomSessionKeyMessageMaxLen","\x00\x00"),
        ("NLMPAuthMsgRandomSessionKeyMessageBuffOffset","\x55\x00\x00\x00"),
        ("NLMPAuthMsgNtNegotiateFlags","\x05\x8A\x88\xa2"),
        ("NegTokenInitSeqMechMessageVersionHigh","\x05"),
        ("NegTokenInitSeqMechMessageVersionLow","\x01"),
        ("NegTokenInitSeqMechMessageVersionBuilt","\x28\x0a"),
        ("NegTokenInitSeqMechMessageVersionReserved","\x00\x00\x00"),
        ("NegTokenInitSeqMechMessageVersionNTLMType","\x0f"),
        ("NLMPAuthMsgNtDomainName",""),
        ("NLMPAuthMsgNtUserName",""),
        ("NLMPAuthMsgNtWorkstationName",""),
        ("NLMPAuthLMChallengeStr", "\x00"),
        ("NLMPAuthMsgNTLMV1ChallengeResponseStruct",""),
        ("NLMPAuthMsgNTerminator",""),
        ("nativeOs","Windows 2002 Service Pack 3 2600"),
        ("nativeOsterminator","\x00\x00"),
        ("nativelan","Windows 2002 5.1"),
        ("nativelanterminator","\x00\x00\x00\x00"),

        ])

    def calculate(self): 

        self.fields["NLMPAuthMsgNtUserName"] = self.fields["NLMPAuthMsgNtUserName"].encode('utf-16le')
        self.fields["NLMPAuthMsgNtDomainName"] = self.fields["NLMPAuthMsgNtDomainName"].encode('utf-16le')
        self.fields["NLMPAuthMsgNtWorkstationName"] = self.fields["NLMPAuthMsgNtWorkstationName"].encode('utf-16le')

        self.fields["nativeOs"] = self.fields["nativeOs"].encode('utf-16le')
        self.fields["nativelan"] = self.fields["nativelan"].encode('utf-16le')

        CompletePacketLen = str(self.fields["wordcount"])+str(self.fields["AndXCommand"])+str(self.fields["reserved"])+str(self.fields["andxoffset"])+str(self.fields["maxbuff"])+str(self.fields["maxmpx"])+str(self.fields["vcnum"])+str(self.fields["sessionkey"])+str(self.fields["securitybloblength"])+str(self.fields["reserved2"])+str(self.fields["capabilities"])+str(self.fields["bcc1"])+str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagLen"])+str(self.fields["ChoosedTag1"])+str(self.fields["ChoosedTag1StrLen"])+str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])+str(self.fields["NLMPAuthLMChallengeStr"])+str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])+str(self.fields["NLMPAuthMsgNTerminator"])+str(self.fields["nativeOs"])+str(self.fields["nativeOsterminator"])+str(self.fields["nativelan"])+str(self.fields["nativelanterminator"])

        SecurityBlobLen = str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagLen"])+str(self.fields["ChoosedTag1"])+str(self.fields["ChoosedTag1StrLen"])+str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])+str(self.fields["NLMPAuthLMChallengeStr"])+str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])

        SecurityBlobLen2 = str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagLen"])+str(self.fields["ChoosedTag1"])+str(self.fields["ChoosedTag1StrLen"])+str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])+str(self.fields["NLMPAuthLMChallengeStr"])+str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])

        SecurityBlobBCC = str(self.fields["ApplicationHeaderTag"])+str(self.fields["ApplicationHeaderLen"])+str(self.fields["AsnSecMechType"])+str(self.fields["AsnSecMechLen"])+str(self.fields["ChoosedTag"])+str(self.fields["ChoosedTagLen"])+str(self.fields["ChoosedTag1"])+str(self.fields["ChoosedTag1StrLen"])+str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])+str(self.fields["NLMPAuthLMChallengeStr"])+str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])+str(self.fields["NLMPAuthMsgNTerminator"])+str(self.fields["nativeOs"])+str(self.fields["nativeOsterminator"])+str(self.fields["nativelan"])+str(self.fields["nativelanterminator"])

        CalculateUserOffset = str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])


        CalculateDomainOffset = str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])

        CalculateWorkstationOffset = str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])

        CalculateLMChallengeOffset = str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])

        CalculateNTChallengeOffset = str(self.fields["NLMPAuthMsgSignature"])+str(self.fields["NLMPAuthMsgMessageType"])+str(self.fields["NLMPAuthMsgLMChallengeLen"])+str(self.fields["NLMPAuthMsgLMChallengeMaxLen"])+str(self.fields["NLMPAuthMsgLMChallengeBuffOffset"])+str(self.fields["NLMPAuthMsgNtChallengeResponseLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"])+str(self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"])+str(self.fields["NLMPAuthMsgNtDomainNameLen"])+str(self.fields["NLMPAuthMsgNtDomainNameMaxLen"])+str(self.fields["NLMPAuthMsgNtDomainNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtUserNameLen"])+str(self.fields["NLMPAuthMsgNtUserNameMaxLen"])+str(self.fields["NLMPAuthMsgNtUserNameBuffOffset"])+str(self.fields["NLMPAuthMsgNtWorkstationLen"])+str(self.fields["NLMPAuthMsgNtWorkstationMaxLen"])+str(self.fields["NLMPAuthMsgNtWorkstationBuffOffset"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageMaxLen"])+str(self.fields["NLMPAuthMsgRandomSessionKeyMessageBuffOffset"])+str(self.fields["NLMPAuthMsgNtNegotiateFlags"])+str(self.fields["NegTokenInitSeqMechMessageVersionHigh"])+str(self.fields["NegTokenInitSeqMechMessageVersionLow"])+str(self.fields["NegTokenInitSeqMechMessageVersionBuilt"])+str(self.fields["NegTokenInitSeqMechMessageVersionReserved"])+str(self.fields["NegTokenInitSeqMechMessageVersionNTLMType"])+str(self.fields["NLMPAuthMsgNtDomainName"])+str(self.fields["NLMPAuthMsgNtUserName"])+str(self.fields["NLMPAuthMsgNtWorkstationName"])+str(self.fields["NLMPAuthLMChallengeStr"])

        ## Packet len
        self.fields["andxoffset"] = struct.pack("<i", len(CompletePacketLen)+32)[:2]
        ##Buff Len
        self.fields["securitybloblength"] = struct.pack("<i", len(SecurityBlobLen))[:2]
        ##Complete Buff Len
        self.fields["bcc1"] = struct.pack("<i", len(SecurityBlobBCC))[:2]
        ## Guest len check
        self.fields["ApplicationHeaderLen"] = struct.pack("<i", len(SecurityBlobLen)-2)[:1]
        self.fields["AsnSecMechLen"]        = struct.pack("<i", len(SecurityBlobLen)-4)[:1]
        self.fields["ChoosedTagLen"]        = struct.pack("<i", len(SecurityBlobLen)-6)[:1]
        self.fields["ChoosedTag1StrLen"]        = struct.pack("<i", len(SecurityBlobLen)-8)[:1]


        ##### Username Offset Calculation..######
        self.fields["NLMPAuthMsgNtUserNameBuffOffset"] = struct.pack("<i", len(CalculateUserOffset))
        self.fields["NLMPAuthMsgNtUserNameLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtUserName"])))[:2]
        self.fields["NLMPAuthMsgNtUserNameMaxLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtUserName"])))[:2]
        ##### Domain Offset Calculation..######
        self.fields["NLMPAuthMsgNtDomainNameBuffOffset"] = struct.pack("<i", len(CalculateDomainOffset))
        self.fields["NLMPAuthMsgNtDomainNameLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtDomainName"])))[:2]
        self.fields["NLMPAuthMsgNtDomainNameMaxLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtDomainName"])))[:2]
        ##### Workstation Offset Calculation..######
        self.fields["NLMPAuthMsgNtWorkstationBuffOffset"] = struct.pack("<i", len(CalculateWorkstationOffset))
        self.fields["NLMPAuthMsgNtWorkstationLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtWorkstationName"])))[:2]
        self.fields["NLMPAuthMsgNtWorkstationMaxLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNtWorkstationName"])))[:2]

        ##### NT Challenge Offset Calculation..######
        self.fields["NLMPAuthMsgNtChallengeResponseBuffOffset"] = struct.pack("<i", len(CalculateNTChallengeOffset))
        self.fields["NLMPAuthMsgNtChallengeResponseLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])))[:2]
        self.fields["NLMPAuthMsgNtChallengeResponseMaxLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthMsgNTLMV1ChallengeResponseStruct"])))[:2]
        ##### LM Challenge Offset Calculation..######
        self.fields["NLMPAuthMsgLMChallengeBuffOffset"] = struct.pack("<i", len(CalculateLMChallengeOffset))
        self.fields["NLMPAuthMsgLMChallengeLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthLMChallengeStr"])))[:2]
        self.fields["NLMPAuthMsgLMChallengeMaxLen"] = struct.pack("<i", len(str(self.fields["NLMPAuthLMChallengeStr"])))[:2]

######################################################################################################

class SMBTreeConnectData(Packet):
    fields = OrderedDict([
        ("Wordcount", "\x04"),
        ("AndXCommand", "\xff"),
        ("Reserved","\x00" ),
        ("Andxoffset", "\x5a\x00"), 
        ("Flags","\x08\x00"),
        ("PasswdLen", "\x01\x00"),
        ("Bcc","\x2f\x00"),
        ("Passwd", "\x00"),
        ("Path",""),
        ("PathTerminator","\x00\x00"),
        ("Service","?????"),
        ("Terminator", "\x00"),

    ])
    def calculate(self): 
         
        ##Convert Path to Unicode first before any Len calc.
        self.fields["Path"] = self.fields["Path"].encode('utf-16le')

        ##Passwd Len
        self.fields["PasswdLen"] = struct.pack("<i", len(str(self.fields["Passwd"])))[:2]

        ##Packet len
        CompletePacket = str(self.fields["Wordcount"])+str(self.fields["AndXCommand"])+str(self.fields["Reserved"])+str(self.fields["Andxoffset"])+str(self.fields["Flags"])+str(self.fields["PasswdLen"])+str(self.fields["Bcc"])+str(self.fields["Passwd"])+str(self.fields["Path"])+str(self.fields["PathTerminator"])+str(self.fields["Service"])+str(self.fields["Terminator"])

        self.fields["Andxoffset"] = struct.pack("<i", len(CompletePacket)+32)[:2]

        ##Bcc Buff Len
        BccComplete    = str(self.fields["Passwd"])+str(self.fields["Path"])+str(self.fields["PathTerminator"])+str(self.fields["Service"])+str(self.fields["Terminator"])
        self.fields["Bcc"] = struct.pack("<i", len(BccComplete))[:2]


class SMBTransRAPData(Packet):
    fields = OrderedDict([
        ("Wordcount", "\x10"),
        ("TotalParamCount", "\x00\x00"),
        ("TotalDataCount","\x00\x00" ),
        ("MaxParamCount", "\xff\xff"),
        ("MaxDataCount","\xff\xff"),
        ("MaxSetupCount", "\x00"),
        ("Reserved","\x00\x00"),
        ("Flags", "\x00"),
        ("Timeout","\x00\x00\x00\x00"),
        ("Reserved1","\x00\x00"),
        ("ParamCount","\x00\x00"),
        ("ParamOffset", "\x5c\x00"),
        ("DataCount", "\x00\x00"),
        ("DataOffset", "\x54\x00"),
        ("SetupCount", "\x02"),
        ("Reserved2", "\x00"),
        ("PeekNamedPipe", "\x23\x00"),
        ("FID", "\x00\x00"),
        ("Bcc", "\x47\x00"),
        ("Terminator", "\x00"),
        ("PipeName", "\\PIPE\\"),
        ("PipeTerminator","\x00\x00"),
        ("Data", ""),

    ])
    def calculate(self):
        #Padding
        if len(str(self.fields["Data"]))%2==0:
           self.fields["PipeTerminator"] = "\x00\x00\x00\x00"
        else:
           self.fields["PipeTerminator"] = "\x00\x00\x00"
        ##Convert Path to Unicode first before any Len calc.
        self.fields["PipeName"] = self.fields["PipeName"].encode('utf-16le')

        ##Data Len
        self.fields["TotalParamCount"] = struct.pack("<i", len(str(self.fields["Data"])))[:2]
        self.fields["ParamCount"] = struct.pack("<i", len(str(self.fields["Data"])))[:2]

        ##Packet len
        FindRAPOffset = str(self.fields["Wordcount"])+str(self.fields["TotalParamCount"])+str(self.fields["TotalDataCount"])+str(self.fields["MaxParamCount"])+str(self.fields["MaxDataCount"])+str(self.fields["MaxSetupCount"])+str(self.fields["Reserved"])+str(self.fields["Flags"])+str(self.fields["Timeout"])+str(self.fields["Reserved1"])+str(self.fields["ParamCount"])+str(self.fields["ParamOffset"])+str(self.fields["DataCount"])+str(self.fields["DataOffset"])+str(self.fields["SetupCount"])+str(self.fields["Reserved2"])+str(self.fields["PeekNamedPipe"])+str(self.fields["FID"])+str(self.fields["Bcc"])+str(self.fields["Terminator"])+str(self.fields["PipeName"])+str(self.fields["PipeTerminator"])

        self.fields["ParamOffset"] = struct.pack("<i", len(FindRAPOffset)+32)[:2]
        ##Bcc Buff Len
        BccComplete    = str(self.fields["Terminator"])+str(self.fields["PipeName"])+str(self.fields["PipeTerminator"])+str(self.fields["Data"])
        self.fields["Bcc"] = struct.pack("<i", len(BccComplete))[:2]

