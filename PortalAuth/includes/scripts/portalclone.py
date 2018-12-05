import os
import sys
import argparse
from PortalCloner import PortalCloner

parser = argparse.ArgumentParser(description='Portal cloner for the WiFi Pineapple that conforms to the Evil Portal structure.')
parser.add_argument('--portalName', action='store', dest='portalName', help='The name of the cloned portal', required=True)
parser.add_argument('--portalArchive', action='store', dest='portalArchive', help='The directory in which to store the portal', required=True)
parser.add_argument('--url', action='store', dest='url', help='The URL a site to clone.  If a captive portal exists it will be cloned instead.', required=True)
parser.add_argument('--injectSet', action='store', dest='injectionSet', help='The name of an injection set to use', required=True)
parser.add_argument('--injectjs', action='store_true', dest='injectjs', help='Inject JavaScript from injectSet into the cloned portal', required=False)
parser.add_argument('--injectcss', action='store_true', dest='injectcss', help='Inject CSS from injectSet into the cloned portal', required=False)
parser.add_argument('--injecthtml', action='store_true', dest='injecthtml', help='Inject HTML from injectSet into the cloned portal', required=False)
parser.add_argument('--injectphp', action='store_true', dest='injectphp', help='Inject PHP from injectSet into the cloned portal', required=False)
parser.add_argument('--stripjs', action='store_true', dest='stripjs', help='Strip inline JavaScript from the cloned portal', required=False)
parser.add_argument('--stripcss', action='store_true', dest='stripcss', help='Strip inline CSS from the cloned portal', required=False)
parser.add_argument('--striplinks', action='store_true', dest='striplinks', help='Strip links from the cloned portal', required=False)
parser.add_argument('--stripforms', action='store_true', dest='stripforms', help='Strip form elements from the cloned portal', required=False)
parser.add_argument('--targeted', action='store_true', dest='targeted', help='Clone to a targeted portal', required=False)
args = parser.parse_args()


cloner = PortalCloner(args.portalName, args.portalArchive, args.injectionSet, args.targeted)
cloner.fetchPage(args.url)
cloner.cloneResources()


if args.stripjs is not False:
	cloner.stripJS()

if args.stripcss is not False:
	cloner.stripCSS()

if args.stripforms is not False:
	cloner.stripForms()
	
if args.striplinks is not False:
	cloner.stripLinks()


	
if args.injectjs is not False:
	cloner.injectJS()

if args.injectcss is not False:
	cloner.injectCSS()
	
if args.injecthtml is not False:
	cloner.injectHTML()
	
	
cloner.writeFiles()
	
print "Complete"
