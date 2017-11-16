import WebSocketsHandler
import UDSHandler
import SocketServer

def main():
    websockets = []
    running = True

    udsHandler = UDSHandler.UDSHandler(args=(websockets, ))
    udsHandler.setDaemon(True)
    
    SocketServer.ThreadingTCPServer.allow_reuse_address = 1
    server = SocketServer.ThreadingTCPServer(("", 9999), WebSocketsHandler.WebSocketsHandler)
    server.running = running
    server.websockets = websockets

    try:
        udsHandler.start()
        server.serve_forever()
        udsHandler.join()
    except KeyboardInterrupt:
        server.running = False
        server.server_close()

if __name__ == '__main__':
    main()