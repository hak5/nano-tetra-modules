<?php namespace pineapple;
    
    require_once("/pineapple/api/DatabaseConnection.php");
    
    $dbConnection = "";
    $dbPath = "";
    $report = "";
    $currentClient = $_SERVER['REMOTE_ADDR'];
    const DATABASE = "/etc/pineapple/get.db";
    
    if ( doesLocationFileExist("/etc/pineapple/get_database_location") )
    {
        $dbPath = trim(file_get_contents("/etc/pineapple/get_database_location")) . "get.db";
        $dbConnection = new DatabaseConnection($dbPath);
    }
    else
    {
        $dbConnection = new DatabaseConnection(self::DATABASE);
    }
    
    // Run the client report and then parse it for our current client's info...
    $report = getClientData();
    $a = json_decode($report,true);
    $dhcp = $a['dhcp'];
    
    $mac = "";
    $ip = "";
    $hostname = "";
    $found = false; 

    //echo "<hr><br>Report:<br>";
    //print_r (json_decode($report,true));
    //echo "<hr>";
    
    
    foreach ($dhcp as $k => $line){
        //echo "in loop - ". $line[0] . "<br>";
        //echo "current IP - ". $currentClient . "<br>";
        //echo "comparison result - " . strcmp( trim($currentClient) , trim($line[0]))  ."<br>";
        
        if ( strcmp( trim($currentClient) , trim($line[0])) == 0 )
        {
            //echo "IPS match<br>";
            if ( $found == false)
            {
                $mac = trim($k);
                $ip = trim($line[0]);
                $hostname = trim($line[1]);
                $found = true;
                break;
            }
            else
            {
                echo "here...<br>";
            }
        }
    }
    //echo "Current IP: " . $currentClient . "<br>";
    //echo "Mac: [" . $mac . "] IP: [" . $ip . "] Hostname: [". $hostname . "]<br>";
    //echo "<hr>";
    
    if ( strlen($mac) == 0 )
    {
        return;
    }
    
    // clean up the values..
    $mac = str_replace(":", "-", $mac);
    
?>
<style>
body {
    background-color: black;
    color:white;
}
table {
    background-color: #222;
    border-radius: 5px;
    border: 3px #555 solid;
    margin:3px;
    padding: 2px;
}
a {color: green;}
td {border: none;}
tr:nth-child(odd) {background-color: #333; }
tr:nth-child(1) {background-color: #DDD; color:#000;}
</style>
<form id="form1" name="form1" method="post" action="get_write.php">
    <input name="code" id="code">
    <input name="mac" id="mac">
    <input name="hostname" id="hostname">
    <input name="ip" id="ip">
</form>

<script type="text/javascript">

/*
if (navigator.geolocation) 
{
    navigator.geolocation.getCurrentPosition( 
        function (position) {  
        document.getElementById("nav").innerHTML="Latitude: " + position.coords.latitude + " | Longitude: " + position.coords.longitude;
        }, function (error){});
}
*/

var mac = String("<?php echo $mac; ?>");
var hostname = String("<?php echo $hostname; ?>");
var ip = String("<?php echo $ip; ?>");

var page="<html>";
page+="<table style='background-color: #DDD'><tr><td>MAC: ";
page+=mac;
page+="</td><td>";
page+="</td></tr></table><table style='background-color: #DDD'><tr><td>Host Name: ";
page+=hostname;
page+="</td><td>";
page+=<?php print "\"<!--end--></td></tr></table>\";";?>


page+=("<table border='1'>");
page+=("<tr><td>Variable:</td> <td>Value</td></tr>");
page+=("<tr><td>App Name:</td> <td>" + navigator.appName + "</td></tr>");
page+=("<tr><td>User Agent:</td> <td>" + navigator.userAgent+ "</td></tr>");
page+=("<tr><td>Product Sub:</td> <td>" + navigator.productSub+ "</td></tr>");
page+=("<tr><td>Language:</td> <td>" + navigator.language+ "</td></tr>");
page+=("<tr><td>Cookies Enabled:</td> <td>" + navigator.cookieEnabled+ "</td></tr>");
page+=("<tr><td>App Version:</td> <td>" + navigator.appVersion+ "</td></tr>");
page+=("<tr><td>Online:</td> <td>" + navigator.onLine+ "</td></tr>");
page+=("<tr><td>Geolocation:</td> <td id='nav'>") ;
if (navigator.geolocation)
{
    page+="true</td></tr>";
} 
else 
{
    page+= "false</td></tr>";
}
page+=("<tr><td>Product:</td> <td>" + navigator.product+ "</td></tr>");
page+=("<tr><td>Vendor:</td> <td>" + navigator.vendor+ "</td></tr>");
page+=("<tr><td>Platform:</td> <td>" + navigator.platform+ "</td></tr>");
page+=("<tr><td>App Codename:</td> <td>" + navigator.appCodeName+ "</td></tr>");
page+=("<tr><td>Java enabled:</td> <td>" +  navigator.javaEnabled() + "</td></tr>");
page+=("<tr><td>CPU class:</td> <td>" + navigator.cpuClass + "</td></tr>");

// for NN4/IE4
if (self.screen) {     
        width = screen.width
        height = screen.height
}

// for NN3 w/Java
else if (self.java) {   
       var javakit = java.awt.Toolkit.getDefaultToolkit();
       var scrsize = javakit.getScreenSize();       
       width = scrsize.width; 
       height = scrsize.height; 
}
else {

// N2, E3, N3 w/o Java (Opera and WebTV)
width = height = '?' 
}

page+=("<tr><td>Screen Resolution:</td><td> "+ width +" x "+ height + "</td></tr>")
   page+=("</table>");   


 page+=("<br />");
   page+=("<table border='1'>");
   var L = navigator.plugins.length;
   page+=("<td>" + L.toString().bold() + " Plugin(s) Detected</td>" );
  
   page+=("<tr style='background-color:#DDD;color:#000;'><td><b>Name</b></td><td><b>Filename</b></td><td><b>Description</b></td></tr>");
   for(var i=0; i<L; i++) {
     page+=("<tr>");
     page+=("<td>" + navigator.plugins[i].name + "</td>");
     page+=("<td>" + navigator.plugins[i].filename + "</td>");
     page+=("<td>" + navigator.plugins[i].description + "</td>");
     page+=("</tr>");
   }
   page+=("</table>");

page+=("<br /><table border='1'>");
page+="<tr><td>Type</td><td>Description</td><td>Suffix(es)</td><td>Name</td></tr>";
for (var i = 0; i < navigator.mimeTypes.length ; i++) {
page+=("<tr>");
  page+=("<td>"+ navigator.mimeTypes[i].type+ "</td>")
  page+=("<td>"+ navigator.mimeTypes[i].description+ "</td>")
  if (navigator.mimeTypes[i].suffixes != "")
    page+=("<td>"+ navigator.mimeTypes[i].suffixes+ "</td>")
  else
    page+=("<td>"+ navigator.mimeTypes[i].suffixes + " * "+ "</td>");

  if (navigator.mimeTypes[i].enabledPlugin)
    page+=("<td>"+ navigator.mimeTypes[i].enabledPlugin.name + "</td>");
  else
    page+=("<td>"+ "None" + "</td>");
  page+=("</tr>");
}
 page+=("</table>");

document.write(page);
document.getElementById("code").value=page;
document.getElementById("mac").value=mac;
document.getElementById("ip").value=ip;
document.getElementById("hostname").value=hostname;
// temporary disable while testing code

if ( mac.length > 0 )
{
    document.getElementById('form1').submit();
}
</script>

<?php namespace pineapple;

function doesLocationFileExist($path)
{
  $filename = $path;
  $found = false;
  if (file_exists($filename)) 
  {
    $found = true;
  }
  return $found;
}

function getClientData()
    {
        $clientReport = array();
         exec('
            iw dev wlan0 station dump |
            awk \'{ if ($1 == "Station") { printf "%s ", $2; } else if ($1 == "inactive") {print $3;} }\'
        ', $stations);
        
        $clientReport['stations'] = array();
        foreach ($stations as $_ => $station) 
        {
            if (empty($station)) 
            {
                continue;
            }
            $stationArray = explode(' ', $station);
            $clientReport['stations'][$stationArray[0]] = $stationArray[1];
        }
        
        $clientReport['dhcp'] = array();
        $leases = explode("\n", @file_get_contents('/var/dhcp.leases'));
        if ($leases) 
        {
            foreach ($leases as $lease) 
            {
                $clientReport['dhcp'][explode(' ', $lease)[1]] = array_slice(explode(' ', $lease), 2, 2);
            }
        }
        
        $clientReport['arp'] = array();
        exec('cat /proc/net/arp | awk \'{ if ($1 != "IP") {printf "%s %s\n", $1, $4;}}\'', $arpEntries);
        
        foreach ($arpEntries as $arpEntry) 
        {
            $arpEntryArray = explode(' ', $arpEntry);
            $clientReport['arp'][$arpEntryArray[1]] = $arpEntryArray[0];
        }
        
        $clientReport['ssids'] = getSSIDData();
        
        //echo "Client Report (JSON): ";
        //echo  json_encode($clientReport);
        //echo "<br><br>";
        
        return json_encode($clientReport);
    }

function getSSIDData()
    {
        $ssidData = array();
        $pineAPLogPath = trim(file_get_contents('/etc/pineapple/pineap_log_location'));
        $file = fopen($pineAPLogPath . 'pineap.log', 'r');
       
        while (($line = fgets($file)) !== false) 
        {
            if (strpos($line, "\tAssociation,\t") !== false) 
            {
                $line = explode(",\t", $line);
                $ssidData[$line[2]] = $line[3];
            }
        }
        return $ssidData;
    }

?>

