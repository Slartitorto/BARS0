<?php
$servername = "localhost";
$username = "db_user";
$password = "db_password";
$dbname = "sensors";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	exit();
}

print  "<head><title>Home Sensors</title>
	<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\
" />
	<link rel=\"apple-touch-icon\" href=\"/icone/temp_icon.png\">
	<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"default\"
 />
	<script type=\"text/javascript\">
		function navigator_Go(url) {
    				window.location.assign(url);
		}
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
                font-size:14;
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
        <TD></TD>
        <TD align=\"right\"><A href=\"javascript:navigator_Go('index.php');\"><img src=\"icone/refresh57.png\" width=\"30\"></
A></TD>
        </TR></TABLE>
	<CENTER><BR><BR>
	";

$query = "SELECT serial, device_name, position, batt_type, min_ok, max_ok FROM devices";
$result = $conn->query($query);
$x=0;
while($row = $result->fetch_assoc()) {
	$serial[$x]=$row["serial"];
	$device_name[$x]=$row["device_name"];
	$position[$x]=$row["position"];
	$batt_type[$x]=$row["batt_type"];
	$min_ok[$x]=$row["min_ok"];
	$max_ok[$x]=$row["max_ok"];
	++$x;
}

$count=count($serial);

for($i=0;$i<$count;$i++) {

	$query = "select data, battery, timestampdiff(second,timestamp,now()) as sec_delay from rec_data where serial = '$seri
al[$i]' order by timestamp desc limit 1";
	$result = $conn->query($query);
	while($row = $result->fetch_assoc()) {
        	$last_data[$i]=$row["data"];
        	$sec_delay[$i]=$row["sec_delay"];
        	$battery[$i]=$row["battery"];
	}
}
for($i=0;$i<$count;$i++) {

        $query = "select count(*) as lq from rec_data where serial = '$serial[$i]' and counter > ((select counter from rec_dat
a where serial = '$serial[$i]' order by timestamp desc limit 1) - 100) and timestamp > (now() - 120000)";
        $result = $conn->query($query);
        while($row = $result->fetch_assoc()) {
                $link_qlt[$i]=$row["lq"];
        }
}


for($i=0;$i<$count;$i++) {

        if (($batt_type[$i] == "litio" and $battery[$i] < 2.7) or ($batt_type[$i] == "nimh" and $battery[$i] < 3.2)) {
                $warn[$i] = "battery_low";
        }
	else if ($sec_delay[$i] > 1000 or $link_qlt[$i] < 80) {
                $warn[$i] = "yellow";
        }
	else if ($last_data[$i] < $min_ok[$i] or $last_data[$i] > $max_ok[$i]) {
		$warn[$i] = "red";
        }
	else {
		$warn[$i] = "green";
	}
}

print "<table class=\"gridtable\"><tr><th>Termometro</th><th>Posizione</th><th>Temp</th><td></td></tr> ";
	for($i=0;$i<$count;$i++) {
	echo "<TR>";
	echo "<TD><A HREF=\"javascript:navigator_Go('device_details.php?serial=";
        echo  $serial[$i] . "');\">" . $device_name[$i]. "</A></TD><TD>" . $position[$i] . "</TD>";
        echo "<TD>" . $last_data[$i] . "</TD>";
	echo "<TD><img src=\"icone/" . $warn[$i] . "_signal.png\" width=\"25\"></TD>";
	}
	echo "</TR>";
	echo "</TABLE> ";

$conn->close();
?>
