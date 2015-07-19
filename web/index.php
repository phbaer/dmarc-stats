<!doctype html>
<html>
<head>
 <title>DMARC Report</title>
 <link rel="stylesheet" type="text/css" href="screen.css"/>
 <script src="underscore-min.js"></script>
 <script type="text/template" id="tplEntity">
  <table>
    <thead>
      <tr>
        <td>Result</td>
        <td>SPF Domain</td>
        <td>DKIM Domain</td>
        <td>Date</td>
        <td>IP</td>
        <td>Host</td>
      </tr>
    </thead>
    <tbody>
<%
  var currentSerial = -1;
  _.each(data, function(value, key)
  {
    var dkim = "DKIM " + (!value.dkimresult ? "missing" : value.dkimresult);
    var spf = "SPF " + (!value.spfresult ? "missing" : value.spfresult);
%>
    <tr>
      <td class="disp back_<%= value.result %>">
        <span class="res_<%= value.spfresult %>">SPF</span>
        <span class="res_<%= value.dkimresult %>">DKIM</span>
        &raquo; <span class="disp_<%= value.disposition %>"><%= value.disposition %></span>
      </td>
      <td class="spf-domain">
        <%= value.spfdomain %>&nbsp;
      </td>
      <td class="dkim-domain">
        <%= value.dkimdomain %>&nbsp;
      </td>
      <td class="date">
        <% if (currentSerial != value.serial) { %> <%= value.mindate.split(" ")[0] %> &ndash; <%= value.maxdate.split(" ")[0] %><% } else { %>&nbsp;<% } %>
      </td>
      <td class="ip">
        <%= value.ip %>
      </td>
      <td class="hostinfo">
        <%= value.hostname %>
      </td>
    </tr>
<%
    currentSerial = value.serial;
  });
%>
    </tbody>
  </table>
</script>
<script>
function showOrg(str)
{
    var tplEntity = document.getElementById("tplEntity");
    var element = document.getElementById("test");

    var orgs = document.getElementById("organisations");
    for (i = 0; i < orgs.childNodes.length; ++i)
    {
        if (orgs.childNodes[i].nodeType != 1)
            continue;
        orgs.childNodes[i].style.backgroundColor = "";
    }

    var orig = document.getElementById(str);
    orig.style.backgroundColor = "#eee";

    // Cleanup, delete all childreb
    while (element.firstChild)
        element.removeChild(element.firstChild);

    // No argument given, exit
    if (str.length == 0)
        return;

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function()
    {
        var template = _.template(tplEntity.innerHTML);
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var text = xmlhttp.responseText;
            eval("obj = " + text);
            _.each(obj, function(value, key)
            {
                var newNode = document.createElement("div");
                var newTextNode = document.createTextNode(key);
                newNode.appendChild(newTextNode);
                element.appendChild(newNode);
                newNode.innerHTML = template({ data: value });
/*                var result = "";
                _.each(value, function(value2, key2)
                {
                    result += template({ value: value2 });
                });
                newNode.innerHTML = result;*/
            });
        }
    }

    xmlhttp.open("GET", "org.php?q=" + str, true);
    xmlhttp.send();
}
</script>
</head>
<body>
  <h1>DMARC Reports</h1>
<?php
///////////////////////////////////////////////////////////////////////////

$dbhost="localhost";
$dbname="dmarc";
$dbuser="===username===";
$dbpass="===password===";

/////////////// NO CHANGES BELOW //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
// Make a MySQL Connection
mysql_connect($dbhost, $dbuser, $dbpass) or die(mysql_error());
mysql_select_db($dbname) or die(mysql_error());

$query_companies = "SELECT DISTINCT org FROM report";

$result_companies = mysql_query($query_companies) or die(mysql_error());

echo '<div id="organisations" class="organisations">' . "\n";

while ($row = mysql_fetch_array($result_companies))
{
    $org = $row['org'];

    $query_count = "SELECT SUM(rcount) AS messages, COUNT(*) AS reports FROM report JOIN rptrecord ON report.serial=rptrecord.serial WHERE org='" . $org . "'";
    $result_count = mysql_query($query_count) or die(mysql_error());
    $count = mysql_fetch_array($result_count);

    $query_count2 = "SELECT COUNT(*) AS count FROM report JOIN rptrecord ON report.serial=rptrecord.serial WHERE org='" . $org . "' AND spfresult='pass' AND dkimresult='pass'";
    $result_count2 = mysql_query($query_count2) or die(mysql_error());
    $count2 = mysql_fetch_array($result_count2);

    echo '<div class="org" onclick="showOrg(\'' . $row['org'] . '\')" id="' . $row['org'] . '">' . "\n";
    echo '<span class="org">' . $row['org'] . '</span><br/>' . "\n";
    echo 'Messages: ' . $count['messages'] . "<br/>\n";
    echo 'Reports: ' . $count['reports'] . "<br/>\n";
    echo 'Passed: ' . $count2['count'] . " (" . number_format(($count2['count'] / $count['messages']) * 100.0, 2) . "%)<br/>\n";
    echo '</div>' . "\n";
}
echo '</div>' . "\n";
echo '<div class="details">' . "\n";
echo '  <div id="test"></div>' . "\n";
echo '</div>' . "\n";
echo '<div class="clear"></div>' . "\n";

/*
$query_report = "SELECT * FROM report ORDER BY mindate"; 
	 
$result_report = mysql_query($query_report) or die(mysql_error());
	 
function format_date($date, $format){
                    $answer = date($format, strtotime($date));
                    return $answer;
        };

echo "<table align=center border=0 cellpadding=3>\n";
echo "<thead><tr><th>Start Date</th><th>End Date</th><th>Domain</th><th>Reporting Organization</th><th>Report ID</th><th>Messages</th></tr></thead><tbody>\n";

$result_report = mysql_query($query_report) or die(mysql_error());

while($row = mysql_fetch_array($result_report)){
	$array_report[] = $row;
	$message_query = "SELECT *, SUM(rcount) FROM rptrecord WHERE serial = {$row['serial']}";
	$message_process = mysql_query($message_query) or die(mysql_error());
	$message_result = mysql_fetch_array($message_process);
	$date_output_format = "r";
	echo "<tr align=center>";
	echo "<td align=right>". format_date($row['mindate'], $date_output_format). "</td><td align=right>". format_date($row['maxdate'], $date_output_format). "</td><td>". $row['domain']. "</td><td>". $row['org']. "</td><td><a href=?report=". $row['serial']. "#rpt". $row['serial']. ">". $row['reportid']. "</a></td><td>". $message_result['SUM(rcount)']. "</td>";
	echo "</tr>";
	echo "\n";
}
echo "</tbody>";
echo "</table>";
echo " <br />";
echo "\n";
//echo "-------------------------------------------------------------------------------------";
echo "<hr align=center width=90% noshade>";
echo " <br />";
echo "\n";
/////////Start Lower Section

// Get value (if it exists) from URL
$displayreport = 0;
if ($_GET) {
	$displayreport = $_GET["report"];
}

if($displayreport !== 0){

$current = 0;

$query_date = "SELECT * FROM report where serial = $displayreport";

$query_rptrecord = "SELECT * FROM rptrecord where serial = $displayreport";

$result_date = mysql_query($query_date) or die(mysql_error());
$showdate = mysql_fetch_array($result_date);
echo "<br/><center><strong>". format_date($showdate['mindate'], r ). "</strong></center><br />\n";

$result_rptrecord = mysql_query($query_rptrecord) or die(mysql_error());

echo "<table align=center border=0 cellpadding=2>";
echo "<th>IP Address</th><th>Host Name</th><th>Message Count</th><th>Disposition</th><th>Reason</th><th>DKIM Domain</th><th>DKIM Result</th><th>SPF Domain</th><th>SPF Result</th>\n";
while($row = mysql_fetch_array($result_rptrecord)){
	$rowcolor="FFFFFF";
	if (($row['dkimresult'] == "fail") && ($row['spfresult'] == "fail")){
	$rowcolor="FF0000"; //red
	} elseif (($row['dkimresult'] == "fail") || ($row['spfresult'] == "fail")){
	$rowcolor="FFA500"; //orange
	} elseif (($row['dkimresult'] == "pass") && ($row['spfresult'] == "pass")){
	$rowcolor="00FF00"; //lime
	} else {
	$rowcolor="FFFF00"; //yellow
	};
	echo "<tr align=center bgcolor=". $rowcolor. ">";
        echo "<td><a name=rpt". $row['serial'].">". long2ip($row['ip']). "</td><td>". gethostbyaddr(long2ip($row['ip'])). "</td><td>". $row['rcount']. "</td><td>". $row['disposition']. "</td><td>". $row['reason']. "</td><td>". $row['dkimdomain']. "</td><td>". $row['dkimresult']. "</td><td>". $row['spfdomain']. "</td><td>". $row['spfresult']. "</td>";
        echo "</tr>";
	echo "\n";
}
echo "</table>";

echo "<hr align=center width=90% noshade>";
echo "<center><h5>Brought to you by <a href=http://www.techsneeze.com>TechSneeze.com</a> - <a href=mailto:dave@techsneeze.com>dave@techsneeze.com</a></h5></center><br />\n";
}*/
echo "<div style=\"clear: both\"></div>";
echo "</body>";
echo "</html>";
//var_dump($array_report);
//var_dump($message_result);
//print_r(array_keys($array_report[5]));
?>
