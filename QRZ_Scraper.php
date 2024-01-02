#! /usr/bin/php
<?

// Roger Banks 30 Mar 2007

// This script is an html screen scraper. It is target at the details page
// on the QRZ.Com website. It take a single argument containg the callsign
// and returns the information from the website.


$url = "http://www.qrz.com/detail/$argv[1]";
$html = file_get_contents($url);

//print $html;

$tag = 'Name';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'Addr1';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'Addr2';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'Country';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'State';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'County';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";

$tag = 'Grid';
$regexp = "/>$tag<\/td><td class=\"q2\"><b>(.*?)<\/b>/s";
preg_match($regexp, $html, $hit);
print "$tag: ". $hit[1] . "\n";


?>