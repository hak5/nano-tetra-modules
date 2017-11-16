import threading
import sys
import os
import socket
import json
from mimetools import Message
from StringIO import StringIO

class UDSHandler(threading.Thread):
    serverAddress = "/var/run/dwall.sock"

    def __init__(self, group=None, target=None, name=None,
                 args=(), kwargs=None, verbose=None):
        threading.Thread.__init__(self, group=group, target=target, name=name,
                                  verbose=verbose)
        self.args = args
        self.kwargs = kwargs
        return

    def run(self):
        try:
            os.unlink(self.serverAddress)
        except:
            pass

        sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
        sock.bind(self.serverAddress)
        sock.listen(1)

        while True:
            connection, client_address = sock.accept()
            self.handleConnection(connection)

            

    def handleConnection(self, connection):
        data = ""
        try:
            while True:
                buff = connection.recv(1024)
                if buff:
                    data += buff
                else:
                    break
        finally:
            connection.close()

        try:
            parsedData = self.parseData(data)
            if parsedData:
                    for websocket in self.args[0]:
                        try:
                            websocket.send_message(json.dumps(parsedData))
                        except Exception, e:
                            pass
        except Exception, e:
            pass

    def parseData(self, data):
        data = data.split("|", 2)
        dataDict = {"from": data[0], "to": data[1]}

        path, headers = data[2].split('\r\n', 1)

        payload = Message(StringIO(headers))
        
        url = "http://" + payload['host'] + path.split(" ")[1]

        if url.lower().endswith(('.png', '.ico', '.jpeg', '.jpg', '.gif', '.svg')):
            dataDict['image'] = url
        else:
            dataDict['url'] = url

        if 'cookie' in payload:
            dataDict['cookie'] = payload['cookie']

        postData = data[2].split('\r\n\r\n')
        if len(postData) == 2:
            if postData[1].strip():
                dataDict['post'] = postData[1]

        return dataDict