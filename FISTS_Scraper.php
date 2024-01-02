#! /usr/bin/php
<?
// Roger Banks 28 May 2007

// This script is an html screen scraper. It is designed accept a list of 
// callsigns and lookup each one on the FISTS website. It determines if a 
// callsign has a FISTS number and appends those that do to an output file.

$filename = "callsigns.txt";
//$regexp = "/FISTS number: (.*?)<\/h2>/s";
$regexp = "/FISTS number:<\/b> <font size=\'\+2\'>(.*?) - /s";

 $handle = fopen($filename, "r");
 if ($handle) {
 	while (!feof($handle)) {
 		$callsign = trim(fgets($handle, 4096));

 		$url = "http://www.wm7d.net/perl/fistsselect.pl?fists_number=$callsign";
 		$html = file_get_contents($url);
 		if(preg_match($regexp, $html, $hit)) {
 			print $callsign . " -- ". $hit[1] . "\n";
 		}
 	}
 	fclose($handle);
}

?>