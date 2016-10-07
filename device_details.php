<?php
$servername = "localhost";
$username = "db_user";
$password = "db_password";
$dbname = "sensors";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$serial=($_GET["serial"]);

$sql = "SELECT unix_timestamp(timestamp) as timestamp, data FROM rec_data where serial = '$serial' and time
stamp > now()-2000000 order by timestamp";
$result = $conn->query($sql);
while ($row = $result->fetch_array()) {
$timestamp = $row['timestamp'];
$timestamp *=1000;
$data = $row['data'];

   $data1[] = "[$timestamp, $data]";
}

print  "<head><title>Sensor details</title>
	<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">
	<link rel=\"apple-touch-icon\" href=\"/icone/app_icon128.png\">
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
	<script type=\"text/javascript\">
                function navigator_Go(url) {
                               window.location.assign(url);
                }
        </script>
	<script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js\"></script>
	<script src=\"http://code.highcharts.com/highcharts.js\"></script>
	<script>
	$(function () {
		Highcharts.setOptions({
        		global: {
            			timezoneOffset: -60
        		}
    		});
    	$('#container1').highcharts({
        	chart: {
            		type: 'line'
        	},
        	title: {
             	   text: ''
            	},
		legend: {
            		enabled: false
        	},
		xAxis: {
            		type: 'datetime',
            	},
		yAxis: {
           		title: {
                		text: 'Temperature (C)'
           			},
        		},
        	series: [{
			data: [";
echo join($data1, ',') ;
print			"]
   		     }]
 	 	  });
		});
	</script>

	<style type=\"text/css\">
		table.gridtable {
		font-family: avenir,arial,sans-serif;
		color:#000000;
		border-width: 0px;
		border-color: #666666;
		border-collapse: collapse;
	}
	table.gridtable th {
		border-width: 0px;
		text-align: center;
		font-size:16;
		padding: 8px;
		border-style: solid;
		border-color: #666666;
		background-color: #efefef;
	}
	table.gridtable td {
		border-width: 0px;
		padding: 8px;
		font-size:13;
		text-align: center;
		border-style: solid;
		border-color: #666666;
		background-color: #ffffff;
	}
	</style>
	<BR>
	<TABLE width=\"100%\"><TR>
	<TD align=\"left\" width=\"90%\">
	<A href=\"javascript:navigator_Go('index.php');\"><img src=\"icone/left37.png\" width=\"35\"></A></
TD>
	<TD align=\"right\">
	<A href=\"javascript:navigator_Go('device_details.php?serial=$serial');\"><img src=\"icone/refresh5
7.png\" width=\"30\">
	</TD>
	</TR></TABLE>
	<BR><CENTER>
	";
function format_time($t,$f=':') // t = seconds, f = separator
{
  return sprintf("%3d%s%02d", ($t/60) , $f, $t%60);
}
$sql = "SELECT device_name, position, batt_type FROM devices where serial = '$serial'" ;
$result = $conn->query($sql);
if ($result->num_rows > 0) {
while($row = $result->fetch_assoc()) {

$device_name = $row["device_name"];
$position = $row["position"];
$batt_type = $row["batt_type"];

    }
 }


$sql = "SELECT timestamp, data, battery, timestampdiff(second,timestamp,now()) as sec_delay FROM rec_data w
here serial = '$serial' order by timestamp desc limit 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

$time_stamp = $row["timestamp"];
$temp = $row["data"];
$batt = $row["battery"];
$sec_delay=$row["sec_delay"];
$min_delay=format_time($sec_delay);

    }
if ($batt_type == "nimh") {
	$perc_batt = (($batt - 3.2)*100);
	}
else if ($batt_type == "litio") {
        $perc_batt = (($batt - 2.7)*200);
        }


$query = "select count(*) as lq from rec_data where serial = '$serial' and counter > ((select counter from
rec_data where serial = '$serial' order by timestamp desc limit 1) - 100) and timestamp > (now() - 120000)"
;
$result = $conn->query($query);
while($row = $result->fetch_assoc()) {
$link_qlt=$row["lq"];
        }







echo " <table class=\"gridtable\">	";
echo " <tr><th>" . $device_name . "</th><th>" . $position . "</th><th>Temp: " . $temp . "&deg C</th></tr>";
echo " <TR><TD>Serial:  " . $serial . "</TD><TD></TD><TD>Batteria:  " . $batt . " (" . $perc_batt . "%) - "
 . $batt_type . "</TD></TR>";
echo " <TR><TD>Link quality: " . $link_qlt . "%</TD><TD></TD><TD colspan=2>Ultimo aggiornamento (min.): " .
 $min_delay . "</TR>";

print "
	</table><br><br><br>
	<div id=\"container1\" style=\"width:100%; height:400px;\"></div>
";
} else {
    echo "0 results";
}

$conn->close();
?>
