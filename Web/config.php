<?php
/**
 * ReportGenerator -- config.php
 * User: Simon Beattie @si_bt
 * Date: 15/04/2014
 * Time: 12:16
 */

$url = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/reportAPI.php"; // Location of the reportAPI
$severity = file_get_contents(__DIR__ . "/severity"); // Minumum Severity to grab from the DB

$auth = array( 	'password' => 'v- =R~U5_L$u+cpk=;r vo"_@K,b7)m7',
		'username' => 'randomstorm');
