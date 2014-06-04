<?php
/**
 * ReportGenerator -- vulnerabilities.php
 * User: Simon Beattie @si_bt
 * Date: 15/04/2014
 * Time: 09:39
 */

echo '<html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<link rel="stylesheet" type="text/css" href="main.css">';
echo '<title>Vulnerability View</title>';
echo '</head>';

echo "<a class=\"myButton\"; href=\"../index.php\">Return to Menu</a></p>";
echo "<table border=0 cellpadding=0 cellspacing=0>";

require_once(__DIR__ . "/../config.php");

$reportId = $_GET['reportid'];
$severity = $_GET['severity'];


$reportData = json_decode(getReportData($reportId, $severity, $url, $auth)); //Get all report data from the API. Returns JSON so decoding that too

if (!$reportData)
{
    die("There is no data to display, try adjusting your severity settings");
}

outputVulnHostPort($reportData); // Picking out only the Vulnerabilities and each host, protocol and port from the full data.


function outputVulnHostPort($reportData) // Pass full report array to return hosts, ports and protocols sorted by vulnerability
{

    $ignoredVulns = array(
        "Nessus SYN scanner",
        "Host Fully Qualified Domain Name (FQDN) Resolution",
        "Traceroute Information",
        "Device Type",
        "Nessus Scan Information",
        "OS Identification",
        "OS Identification Failed",
    );

    $data = array();
    foreach ($reportData as $hostData)
    {
        foreach ($hostData->vulnerabilities as $vulnerability)
        {

            if ($vulnerability->risk == 'None')
            {
                $risk = 'Informational';
            } else {
                $risk = $vulnerability->risk;
            }


            $data[] = array(
                                                'ip' => ip2long($hostData->hostname),
                                                'vuln' => $vulnerability->name,
                                                'risk' => $risk,
                                                'severity' => $vulnerability->severity,
                                                'port' => $vulnerability->port . "/" . $vulnerability->protocol,
                                                'service' => $vulnerability->service);
        }

    }

    usort($data, function($firstArrayElement, $secondArrayElement)
    {
        $first = $firstArrayElement['ip'];
        $second = $secondArrayElement['ip'];

        $ret = strcmp($first, $second);
        if($ret == 0)
        {
            $firstSeverity = (float) $firstArrayElement['severity'];
            $secondSeverity = (float) $secondArrayElement['severity'];

            if($secondSeverity > $firstSeverity)
            {
                return 1;
            }
            elseif($firstSeverity > $secondSeverity)
            {
                return -1;
            }
            elseif($firstSeverity == $secondSeverity)
            {
                return 0;
            }
        }

        return $ret;
    });

    $ip = "";


    $counts = array();
    foreach ($data as $value){
        foreach ($value as $key2 => $value2){
            if ($key2 == "ip")
            {
                $index = $value2;
                if (array_key_exists($index, $counts)){
                    $counts[$index]++;
                } else {
                    $counts[$index] = 1;
                }
            }
        }
    }

    foreach ($data as $vuln)
    {
        if ( in_array($vuln['vuln'], $ignoredVulns))
        {
            $counts[$vuln['ip']]--;
        }
    }
    $started = 0;
    foreach ($data as $vuln)
    {

        if (($vuln['risk'] == "High") || ($vuln['risk'] == "Critical"))
        {
            $colour = "red";
        }
        elseif ($vuln['risk'] == "Medium")
        {
            $colour = "orange";
        }
        elseif ($vuln['risk'] == "Low")
        {
            $colour = "green";
        }
        elseif ($vuln['risk'] == "Info")
        {
            $colour = "blue";
        }
        else
        {
            $colour = "blue";
        }

        if ($vuln['severity'] > 4)
        {
            $status = 'FAIL';
            $statusColour = 'fail';
        }
        else
        {
            $status = 'PASS';
            $statusColour = 'pass';
        }

        if ($vuln['severity'] == 0)
        {
            $vuln['severity'] = 'N/A';
            $sevColour = "black";
        }
        else
        {
            $sevColour = $colour;
        }


        if ( in_array($vuln['vuln'], $ignoredVulns))
        {
            continue;
        }

        if ( $vuln['vuln'] == "Service Detection")
        {
            $vuln['vuln'] = $vuln['vuln'] . ": " . $vuln['service'];
        }

        if ($ip == long2ip($vuln['ip']))
        {

            print( "
            <tr>
                  <td>" . $vuln['vuln'] . "</td>
                  <td>" . $vuln['port'] . "</td>
                  <td class=" . $colour .">" . $vuln['risk'] . "</td>
                  <td class=" . $sevColour .">" . $vuln['severity'] . "</td>
                  <td class=" . $statusColour .">" . $status . "</td>
                  <td>N/A</td>
             </tr>
            ");
        } else {
            if ( $started == 0)
            {
                $started++;
            } else {
                print("
                <tr>
                  <td>Host Summary</td>
                  <td colspan=6></td>
                </tr>
            ");
            }

            print( "
            <tr>
                  <td border:solid 1pt gray; vertical-align: top; rowspan=\"" . $counts[$vuln['ip']] . "\">" . long2ip($vuln['ip']) . "</td>
                  <td>" . $vuln['vuln'] . "</td>
                  <td>" . $vuln['port'] . "</td>
                  <td class=" . $colour .">" . $vuln['risk'] . "</td>
                  <td class=" . $sevColour .">" . $vuln['severity'] . "</td>
                  <td class=" . $statusColour .">" . $status . "</td>
                  <td>N/A</td>
             </tr>

            ");

            $ip = long2ip($vuln['ip']);
        }

    }

}

echo "</table>";

function getReportData($reportId, $severity, $url, $auth) // Pass reportID, severity and $url from config file to return full report JSON
{
    $query = '?report=4&reportid=' . $reportId . '&severity=' . $severity;
    $report = curlGet($url, $query, $auth);
    return $report;
}


function curlGet($url, $query, $auth) // Curl function
{

    $url_final = $url . '' . $query;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url_final);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "$auth[username]:$auth[password]");

    $return = curl_exec($ch);
    curl_close($ch);
    return $return;

}