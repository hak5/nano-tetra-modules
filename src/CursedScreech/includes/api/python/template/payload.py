from PineappleModules import CursedScreech

cs = CursedScreech("Network Client")
cs.startMulticaster("IPAddress", mcastport, hbinterval)
cs.setRemoteCertificateSerial("serial")
cs.startSecureServerThread("privateKey", "publicKey", "kuroKey")
