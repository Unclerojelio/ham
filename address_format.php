#! /usr/bin/php

<?
// Author:   Roger Banks
// Date:     10 April 2005
// File:     log.php
// Language: PHP
//
// Usage: "address_format.php"
//
// Description:
// This script parses a exported XML file from jLog
// and outputs a a tab-separated file for import into FM Pro.
// The format of the XML file is:
// <ADIF>
//    <header>
//       <version></version>
//       <description></description>
//       <exported-by>
//          <program></program>
//          <version></version>
//          <contacts></contacts>
//       </exported-by>
//    </header>
//	  <qso>
//		<qso-date></qso-date>
//      <time-on></time-on>
//      <call></call>
//		<band></band>
//      <mode></mode>
//      <tx-pwr></tx-pwr>
//		<rst-sent></rst-sent>
//		<rst-rcvd></rst-rcvd>
//		<time-off></time-off>
//		<name></name>
//		<qth></qth>
//		<qsl-sdate></qsl-sdate>
//		<qsl-rcvd></qsl-rcvd>
//		<comment></comment>
//		<dcxx></dcxx>
//		<ituz></ituz>
//		<cqz></cqz>
//		<pfx></pfx>
//		<cont></cont>
//		<state></state>
//		<cnty></cnty>
//		<gridsquare></gridsquare>
//      <sat-name></sat-name>
//      <address></address>
//      <notes></notes>
//      <ten-ten></ten-ten>
//	  </qso>
// </ADIF>
//
// Change Log:
// 10 Apr 2005 Started Project
// 10 Apr 2005 Release 1
// 18 Apr 2005 Add loop guard so that only unsent qsl are output and limit
//             output to 27 records.
// 19 Apr 2005 Fixed character data handler so that entire address field is
//             read without extraneous characters and with tab separators.
// 24 Apr 2005 Added code to exclude FISTS Club contacts from address file.
//             Will QSL via Buro.
// 05 May 2005 Now prints calls of QSL to go to FISTS or 3905ccn buros.
// 08 May 2005 Added functions to generalize accumulating and printing qsl to
//             go out via buro or with SASE.
// 10 May 2005 Added generaized function to write out qsl addresses to file.
//             Directs now handled in a general fashion.
// 21 May 2005 Added guard to Print_QSL_Via and Write_QSL_List so that empty
//             arrays are not processed.
// 22 May 2005 Added functions to collect and display numbers parsed from
//             notes field.
// 31 May 2005 Changed ccns array to wm9h and added w5il. This change allows
//             3905CCN contacts to go to the correct respective buro.
// 18 Jun 2005 Added band to FISTS log.
// 29 Jun 2005 Added fields to the ADD and WRITE_QSL_LIST functions to 
//             support QSL card printing.
// 02 Jul 2005 Added Write_Numbers. Read $dxcc data for FISTS points.
// 05 Aug 2005 Commented out "FM" guard so that Sat FM contacts are exported.
// 02 Sep 2005 parse sat_name
// 04 Sep 2005 outputs sat contacts to sat_contacts.txt
// 24 Sep 2005 Added state and cc columns to fists.txt output
// 			   Points now calculated in FISTS_NRS and passed in array.
// 25 Sep 2005 Outputs 'fists_was.txt' containg WAS data for FISTS Club award
// 26 Sep 2005 preg_match for FISTS# and club now case insensitive.
// 26 Sep 2005 Added to CVS repository.
// 27 Sep 2005 Cut and paste format_name from log.php. Guard $fist_states so
//             blank states are excluded.
// 23 Dec 2005 Output Canadian calls to text file.
// 24 Dec 2005 Added support for Ten-Ten number and buro outputs, a la FISTS et al.
// 31 Dec 2005 Output file of PSK31 QSOs.
// 22 Jan 2006 Output file of FISTS Millionaire contacts.
// 28 Jan 2006 Added point value for KN0WCW club call.
// 29 Jan 2006 fixed bug in millionaire output. Now collects dupe calls
//             made on different bands.
// 06 Mar 2006 if $freq empty, fills from $band.
// 13 Jul 2006 Added QSL_MSG to exported 
// 18 AUG 2006 Added Format_Date function but it isn't referenced anywhere.
// 28 Aug 2006 Added calls to Write_QSL_List with WM9H and W5IL.
// 10 Sep 2006 Added call to Write_QSL_List with FISTS_BURO.
// 11 Sep 2006 Hacked up KG4 bug. If real KG4 logged, will need to fix.
// 16 Jan 2007 Changed Add_QSL_List and Write_QSL_List, now keyed with
//             composite key of call, band, and mode.
// 17 Jan 2007 Corrected Print_QSL_Via to reflect changes implemented yesterday.
// 22 Jan 2007 Fixes to comply with jLog v4 output changes.
// 29 Mar 2007 Psk QSOs array now has callsign as unique key, sorts array.
// 24 Apr 2007 Added calls to find and output SKCC Buro list.
// 23 Dec 2007 Now counts PSK63 QSOs.
// 08 Apr 2010 Outputs 30MDG contacts.
// 14 Feb 2011 Output $grid to psk_qsos.
//
// Todo:
// 03 May 2005 Accumulate FISTS and 3905 contacts to arrays and print out
//             report. Report should also print one-line summaries of qsl
//             data to aid in completing QSL cards.
// 05 May 2005 Need function that take array as parameter and prints out 
//             summary. ** Done **
// 22 May 2005 Generalize FISTS_NRS to collect any pair of id
//             and number.
// 10 Sep 2005 Add "Total Records Processed" output. ** Done **
// 22 Feb 2006 Have psk qso in array with callsign as a key and sort. ** Done **

// ***********************************************************
// 
// Write code to strip ampersand characters from input file !!!!!
// (as in "Lewis & Clark" county)
// Replace all '\n' with '\t'.
//
// ************************************************************



$filename = 'log.xml';

// initialize global variables
$call = "";            // callsign
$qso_date = "";        // qso date
$time_on = "";         // qso start time
$band = "";            // frequency band
$mode = "";            // mode
$tx_pwr = "";          // transmitter power
$name = "";            // station name
$qth = "";             // station location
$grid = "";            // gridsquare
$qsl_sent = "";        // qsl card sent
$qsl_rcvd = "";        // qsl card received
$address = "";         // mailing address
$notes = "";           // notes field
$comment = "";		   // comment field
$state = "";		   // state field
$freq = "";            // frequency field
$rst_sent = "";        // RST Sent
$rst_rcvd = "";        // RST Received
$qsl_via = "";         // qsl-via field
$qsl_msg = "";	       // qsl-msg field
$dxcc = "";			   // DXCC
$sat_name = "";        // satellite name
$ten_ten = "";         // ten-ten number
$fists = "";           // array of FISTS club calls to QSL via buro
$wm9h = "";            // array of 3905CCN calls to QSL via WM9H buro
$w5il = "";            // array of 3905CCN calls to QSL via W5IL buro
$sases = "";           // array of calls to send SASE for QSL.
$directs = "";         // array of calls of qsl to be sent direct.
$skcc = "";            // array of calls to send via SKCC buro.
$fists_nrs = "";       // array of fists numbers
$millionaires = "";    // array of fists millionaire award contacts
$ten_ten_nrs = "";     // array of ten-ten numbers
$sat_contacts = "";    // array of satellite contacts
$canadians = "";       // array of Canadian callsigns
$psk_qsos = "";        // array of PSK31 QSOs
$current_tag = "";     // the name of the current XML tag being processed
$rcrds_processed = 0;  // number of records processed

function Format_Date($date) {

	$months = array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
	                'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
	                
	$parts = explode('/', $date);
	return $parts[1] . " " . $months[$parts[0]] . " " . $parts[2];
}

function Print_QSL_Via($list, $via) {
	if($list != "") {
		echo "\n******* $via *******\n";
		foreach ($list as $key => $item) {
			echo "$item[13]\n";
		}
		echo "======================\n";
		$count = count($list);
		echo "Total: $count\n\n";
	}
}

function Add_QSL_List(&$list, $via) {
	global $qsl_via;
	global $qsl_sent;
	global $call;
	global $address;
	global $qso_date;
	global $time_on;
	global $freq;
	global $state;
	global $notes;
	global $rst_sent;
	global $rst_rcvd;
	global $mode;
	global $qsl_rcvd;
	global $qsl_msg;
	global $comment;
	global $band;
	
	$freqs  =array('2M'   => 144,
			       '6M'   =>  50,
			       '10M'  =>  28,
			       '12M'  =>  24,
			       '15M'  =>  21,
			       '17M'  =>  18,
			       '20M'  =>  14,
			       '30M'  =>  10,
			       '40M'  =>   7,
			       '80M'  => 3.5,
			       '160M' => 1.8,);
	if($freq == "") $freq = $freqs[$band];
	
	if($qsl_sent == "") $qsl_sent = "N";
	
	$pos = strpos($qsl_via, $via);
	if($pos !== FALSE && $qsl_sent == "N") {
		
		$net = "CW";
		if(strpos(strtolower($comment), "early")&& $mode == "SSB")
			$net = "SSB Early";
		if(strpos(strtolower($comment), "late")&& $mode == "SSB")
			$net = "SSB Late";
			
		$status = "fixed";
		if(strpos(strtolower($notes), "mobile")) $status = "mobile";
		if(strpos(strtolower($notes), "portable")) $status = "portable";
		
		if(strpos(strtolower($notes), "capital")) {
			$temp = $state;
			$state = "the $temp Capital";
		}
		
		$qsl = "TNX";
		if($qsl_rcvd == "N") $qsl = "PSE";
		
		$key = $call . $band . $mode;
		
		$list[$key] = array($address, $qso_date, $time_on, 
		                    $freq, $state, $rst_sent, $rst_rcvd,
		                    $status, $mode, $qsl, $net, $band, $qsl_msg, $call);
		$qsl_sent = "Y";
	}
}

function Write_QSL_List($list, $via) {
	if($list != "") {
		$filename = $via . ".txt";
		if (!$out_file_ptr = fopen($filename, 'w')) {
			 echo "Cannot open file ($filename)";
			 exit;
		}
		foreach($list as $key => $item) {
			$pieces = explode('\t', $item[0]);
			fwrite($out_file_ptr, "$item[13]\t");      // call
			fwrite($out_file_ptr, "$pieces[0]\t");     // address
			fwrite($out_file_ptr, "$pieces[1]\t");     // address
			fwrite($out_file_ptr, "$pieces[2]\t");     // address
			fwrite($out_file_ptr, "$pieces[3]\t");     // address
			fwrite($out_file_ptr, "$item[1]\t");       // qso date
			fwrite($out_file_ptr, "$item[2]\t");       // UTC
			fwrite($out_file_ptr, "$item[3]\t");       // freq
			fwrite($out_file_ptr, "$item[4]\t");       // state
			fwrite($out_file_ptr, "$item[5]\t");       // Sent RST
			fwrite($out_file_ptr, "$item[6]\t");       // Rcvd RST
			fwrite($out_file_ptr, "$item[7]\t");       // Status
			fwrite($out_file_ptr, "$item[8]\t");       // Mode
			fwrite($out_file_ptr, "$item[9]\t");       // QSL rcvd?
			fwrite($out_file_ptr, "$item[10]\t");      // Net
			fwrite($out_file_ptr, "$item[11]\t");      // Band
			fwrite($out_file_ptr, "$item[12]\n");      // QSL Msg
		}
		echo "Writing $filename\n";
		fclose($out_file_ptr);
	}
}

function FISTS_NRS(&$numbers, &$millionaires) {
	global $notes;
	global $qso_date;
	global $call;
	global $band;
	global $dxcc;
	global $state;
	global $name;
	
	$pieces = explode('
', $notes);
	if(preg_match('/FISTS#[ ]*([0-9]+)[ ]*(Club)*/i', $pieces[0], $matches)) {
		preg_match('/CC#[ ]*([0-9]+)/', $pieces[1], $cc_matches);
		$points = "1";
		if($dxcc != "291") $points = "2";
		if(strtolower($matches[2]) == "club") $points = "3";
		if($call == "KN0WCW") $points = "5";
		if(substr($call, 0, 3) == "KG4") $points = "1";
		$numbers[$matches[1]] = array($call, $qso_date, $band, $points, $state, $cc_matches[1], $name);
		if($qso_date >= 20060101) {
			$combo_key = $call . $band;
			$millionaires[$combo_key] = array($call, $qso_date, $band, $matches[1], $name);
		}
	}
}

function Print_Numbers($numbers) {
	if($numbers != "") {
		echo "\n********** FISTS Numbers ************\n";
		ksort($numbers);
		foreach($numbers as $number => $item) {
			echo "$number\t$item[0]\t$item[1]\t$item[2]\n";
		}
		echo "=====================================\n";
		$count = count($numbers);
		echo "Total: $count\n";
	}
}

function Write_Numbers($numbers, $millionaires) {
	if($numbers != "") {
		$filename = "fists.txt";
		if (!$out_file_ptr = fopen($filename, 'w')) {
			 echo "Cannot open file ($filename)";
			 exit;
		}
		ksort($numbers);
		foreach($numbers as $number => $item) {
			fwrite($out_file_ptr, "$item[0]\t$item[3]\t$item[1]\t$item[2]\t$item[4]\t$number\t$item[5]\n");
			if($item[4] != "") {
				$fists_states[$item[4]] = array($item[0], $item[1], $item[2], $number, $item[6]);
			}
		}
		echo "Writing $filename\n";
		fclose($out_file_ptr);
	}
	if($fists_states != "") {
		$filename = "fists_was.txt";
		if (!$out_file_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		ksort($fists_states);
		foreach($fists_states as $fists_state => $item) {
			fwrite($out_file_ptr, "$fists_state\t$item[0]\t$item[4]\t$item[2]\t$item[3]\t$item[1]\n");
		}
		echo "Writing $filename\n";
		fclose($out_file_ptr);
	}
	if($millionaires != "") {
		$filename = "millionaires.txt";
		if (!$out_file_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		ksort($millionaires);
		foreach($millionaires as $millionaire => $item) {
			fwrite($out_file_ptr, "$item[0]\t$item[4]\t$item[2]\t$item[3]\t$item[1]\n");
		}
		echo "Writing $filename\n";
		fclose($out_file_ptr);
	}
}

function Write_Sat_Contacts($sat_contacts) {
	if($sat_contacts != "") {
		$filename = "sat_contacts.txt";
		if(!$outfile_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		foreach($sat_contacts as $contact) {
			fwrite($outfile_ptr, "$contact[0]\t$contact[1]\t$contact[2]\t");
			fwrite($outfile_ptr, "$contact[3]\t$contact[4]\t$contact[5]\t");
			fwrite($outfile_ptr, "$contact[6]\n");
		}
		$count = count($sat_contacts);
		fwrite($outfile_ptr, "Total contacts: $count\n");
		echo "Writing $filename\n";
		fclose($outfile_ptr);
	}
}

function Write_Canadians($canadians) {
	if($canadians != "") {
		$filename = "canadians.txt";
		if(!$outfile_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		foreach($canadians as $contact) {
			fwrite($outfile_ptr, "$contact[0]\t$contact[1]\n");
		}
		echo "Writing $filename\n";
		fclose($outfile_ptr);
	}
}

function Write_TenTen_Numbers($ten_ten_nrs) {
	if($ten_ten_nrs != "") {
		$filename = "tentens.txt";
		if(!$outfile_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		foreach($ten_ten_nrs as $contact) {
			fwrite($outfile_ptr, "$contact[0]\t$contact[1]\t$contact[2]\t");
			fwrite($outfile_ptr, "$contact[3]\t$contact[4]\n");
		}
		echo "Writing $filename\n";
		fclose($outfile_ptr);
	}
}

function Write_PSK_QSOS($psk_qsos) {
	if($psk_qsos != "") {
		$filename = "psk_qsos.txt";
		if(!$outfile_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		ksort($psk_qsos);
		foreach($psk_qsos as $key => $contact) {
			fwrite($outfile_ptr, "$key\t$contact[0]\t$contact[1]\t");
			fwrite($outfile_ptr, "$contact[2]\t$contact[3]\t$contact[4]\n");
		}
		echo "Writing $filename\n";
		fclose($outfile_ptr);
	}
}

function Write_30MDG_QSOS($digital30m_qsos) {
	if($digital30m_qsos != "") {
		$filename = "digital30m_qsos.txt";
		if(!$outfile_ptr = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}
		foreach($digital30m_qsos as $contact) {
			fwrite($outfile_ptr, "$contact[0]\t$contact[1]\t");
			fwrite($outfile_ptr, "$contact[2]\t$contact[3]\n");
		}
		echo "Writing $filename\n";
		fclose($outfile_ptr);
	}
}

function isEven ( $num ) {
   return !($num % 2);
}

///////////////////////////////////////////////////////////////////////////////
//
// Function: format_name
// Date: 13 Apr 2005
// Description:
//		Reformats person names and city, start names to correct capitalization.
// Inputs:
//		string $str	The string to reformat
// Outputs:
//		string containing reformated string
// Side effects:
//		none
//
///////////////////////////////////////////////////////////////////////////////
function format_name($str) {
	$parts = explode(',', $str);
	$name = ucwords(strtolower($parts[0]));
	$suffix = trim(strtoupper($parts[1]));
	if($suffix == "") return $name;
	else if($suffix == 'JR') $suffix = 'Jr';
	return $name . ', ' . $suffix;
}

///////////////////////////////////////////////////////////////////////////////
//
// Function: create_parser
// Date: 07 May 2003
// Description:
//		This function opens an XML file and creates a parser to read it
// Inputs:
//		string $filename	The name of the XML file to parse
// Outputs:
//		array return value: indicates successful file open and parser
//		creation
// Side effects:
//		none
//
///////////////////////////////////////////////////////////////////////////////
function create_parser ($filename) {
	$fp = fopen($filename, 'r');
	$parser = xml_parser_create();

	xml_set_element_handler($parser, 'start_element', 'end_element');
	xml_set_character_data_handler($parser, 'character_data');
	//xml_set_processing_instruction_handler($parser, 'processing_instruction');
	//xml_set_default_handler($parser, 'default');

	return array($parser, $fp);
} // create_parser

///////////////////////////////////////////////////////////////////////////////
//
// Function: parse
// Date: 07 May 2003
// Description:
//		This function parses the lines of an XML file
// Inputs:
//		$parser	Pointer to parser
//		$fp	file pointer
// Outputs:
//		array return value: indicates successful parse operation
// Side effects:
//		none
//
///////////////////////////////////////////////////////////////////////////////
function parse($parser, $fp) {
	$blocksize = 4 * 1024;

	while($data = fread($fp, $blocksize)) {
		if(!xml_parse($parser, $data, feof($fp))) {
			echo 'Parse Error: ' . 
			     xml_error_string($parser) . 
			     " at line " . 
			     xml_get_current_line_number($parser);
			return FALSE;
		}
	}
	return TRUE;
} // parse

///////////////////////////////////////////////////////////////////////////////
//
// Function: start_element
// Date: 07 May 2003
// Description:
//		This function handles start elements passed from the parser
// Inputs:
//		pointer $parser		pointer to parser
//		string  $element	the name of the element
//		array	$attributes	the elements attributes
// Outputs:
//		none
// Side effects:
//		sets values for globals $id, $lat, $lon, $current_element
//
///////////////////////////////////////////////////////////////////////////////
function start_element($parser, $element, &$attributes) {

	global $call;
	global $current_tag;
	
	$current_tag = $element;
	//echo "Current Tag: $current_tag<BR>";
} // start_element

///////////////////////////////////////////////////////////////////////////////
//
// Function: end_element
// Date: 07 May 2003
// Description:
//		This function handles end elements passed from the parser.
//		Formats $lat and $lon to display five significant digits.
// Inputs:
//		pointer $parser		pointer to parser
//		string  $element	the name of the element
// Outputs:
//		html table row containing waypoint information
// Side effects:
//		sets value for global $current_element
//
///////////////////////////////////////////////////////////////////////////////
function end_element($parser, $element) {

	global $qso_date;
	global $time_on;
	global $call;
	global $band;
	global $mode;
	global $tx_pwr;
	global $name;
	global $qth;
	global $grid;
	global $qsl_sent;
	global $qsl_rcvd;
	global $qsl_msg;
	global $address;
	global $notes;
	global $comment;
	global $state;
	global $freq;
	global $qsl_via;
	global $rst_sent;
	global $rst_rcvd;
	global $dxcc;
	global $sat_name;
	global $ten_ten;
	global $fists;
	global $wm9h;
	global $w5il;
	global $sases;
	global $directs;
	global $skcc;
	global $fists_nrs;
	global $millionaires;
	global $ten_ten_nrs;
	global $sat_contacts;
	global $canadians;
	global $psk_qsos;
	global $digital30m_qsos;
	global $achvr_qsos;
	global $current_tag;
	global $rcrds_processed;
	
	$current_tag = ""; // This is important, otherwise character data will
		               // be cleared before next 'waypoint' end tag.
	
	if($element == "QSO") {
	
		// echo "Call: $call\n";
		
		Add_QSL_List($fists, 'FISTS');
		Add_QSL_List($wm9h, 'WM9H');
		Add_QSL_List($w5il, 'W5IL');
		Add_QSL_List($sases, 'SASE');
		Add_QSL_List($tenten, 'TENTEN');
		Add_QSL_List($skcc, 'SKCC');
		Add_QSL_List($achvr_qsos, 'ACHVR');
		
		if($qsl_sent == "N" && 
		   $row_count <= 27 && 
		   //$mode != "FM" &&
		   $address != "") {
		   
		   $qsl_via = 'DIRECT';
		   Add_QSL_List($directs, 'DIRECT');
		}
		
		FISTS_NRS($fists_nrs, $millionaires);       // collect FISTS Numbers
		
		if($sat_name != "") {
			//echo "adding sat contact with $call\n";
			$sat_contacts[] = array($call, $qso_date, $time_on, $name, $grid, $state, $sat_name);
		}
		
		if($dxcc == "1") {
			$canadians[] = array($call, $qso_date);
		}
		
		if($ten_ten != "") {
			$ten_ten_nrs[] = array($call, $ten_ten, $qso_date, $state, $name);
		}
		
		if($mode == "PSK31") {
			$psk_qsos[$call] = array($qso_date, $time_on, $band, $mode, $grid);
		}
		
		if(($band == "30M") && ($mode != "SSB") && ($mode != "CW") && ($mode != "FM")) {
			$digital30m_qsos[] = array($call, $qso_date, $time_on, $mode);
		}
		
		// collect 3905ccn info (capitals, mobile, combos, yls, vips, etc)
		// from <notes> field.
		//echo "DXCC:  $dxcc\n";
		
		$qso_date = "";
		$time_on = "";
		$call = "";
		$address = "";  // clear out address
		$name = "";
		$grid = "";
		$qsl_sent = "";
		$qsl_rcvd = "";
		$qsl_msg = "";
		$mode = "";
		$notes = "";
		$comment = "";
		$qsl_via = "";
		$state = "";
		$freq = "";
		$rst_sent = "";
		$rst_rcvd = "";
		$dxcc = "";
		$band = "";
		$ten_ten = "";
		$sat_name = "";
		
		$rcrds_processed++;
	}
} // end_element

///////////////////////////////////////////////////////////////////////////////
//
// Function: character_data
// Date: 07 May 2003
// Description:
//		This function handles the character data from the parser.
// Inputs:
//		pointer $parser		pointer to parser
//		string  $data   	the cdata
// Outputs:
//		none
// Side effects:
//		sets values for globals $link, $type, $name
//
///////////////////////////////////////////////////////////////////////////////
function character_data($parser, $data) {

	global $qso_date;
	global $time_on;
	global $call;
	global $band;
	global $mode;
	global $tx_pwr;
	global $name;
	global $qth;
	global $grid;
	global $qsl_sent;
	global $qsl_rcvd;
	global $address;
	global $notes;
	global $comment;
	global $qsl_via;
	global $qsl_msg;
	global $state;
	global $freq;
	global $rst_sent;
	global $rst_rcvd;
	global $dxcc;
	global $current_tag;
	global $row_count;
	global $sat_name;
	global $ten_ten;
	
	//echo "Current Tag: $current_tag Data: -$data-\n";
	
	if($current_tag == "CALL") $call .= $data;
	else if($current_tag == "QSO-DATE") $qso_date .= $data;
	else if($current_tag == "TIME-ON") $time_on .= $data;
	else if($current_tag == "BAND") $band .= $data;
	else if($current_tag == "MODE") $mode .= $data;
	else if($current_tag == "TX-PWR") $tx_pwr .= $data;
	else if($current_tag == "NAME") $name .= format_name($data);
	else if($current_tag == "QTH") $qth .= ucwords(strtolower($data));
	else if($current_tag == "GRIDSQUARE") $grid .= $data;
	else if($current_tag == "QSL_SENT") $qsl_sent .= $data;
	else if($current_tag == "QSL_RCVD") $qsl_rcvd .= $data;
	else if($current_tag == "ADDRESS") {
		if(strlen($data) > 1) $address .= trim($data) . '\t';
	}
	else if($current_tag == "NOTES") $notes .= $data;
	else if($current_tag == "QSL-VIA") $qsl_via .= $data;
	else if($current_tag == "QSL-MSG") $qsl_msg .= $data;
	else if($current_tag == "COMMENT") $comment .= $data;
	else if($current_tag == "STATE") $state .= $data;
	else if($current_tag == "FREQ") $freq .= $data;
	else if($current_tag == "RST-SENT") $rst_sent .= $data;
	else if($current_tag == "RST-RCVD") $rst_rcvd .= $data;
	else if($current_tag == "DXCC") $dxcc = $data;
	else if($current_tag == "SAT-NAME") $sat_name .= $data;
	else if($current_tag == "TEN-TEN") $ten_ten .= $data;
    //echo "Character Data: $data<BR>";
} //character_data

/*
function processing_instruction() {
}

function default() {
}
*/

// main program
if(list($parser, $fp) = create_parser($filename)) {
	parse($parser, $fp);
	fclose($fp);
	xml_parser_free($parser);
}

Print_QSL_Via($fists, "FISTS");
Print_QSL_Via($wm9h, "WM9H");
Print_QSL_Via($w5il, "W5IL");
Print_QSL_Via($sases, "SASE");
Print_QSL_Via($directs, "DIRECT");
Print_QSL_Via($tenten, "TENTEN");
Print_QSL_Via($skcc, "SKCC");
Print_QSL_Via($achvr_qsos, "ACHVR");

Write_QSL_LIST($sases, "SASE");
Write_QSL_LIST($directs, "DIRECT");
Write_QSL_LIST($wm9h, "WM9H");
Write_QSL_LIST($w5il, "W5IL");
Write_QSL_LIST($fists, "FISTS_BURO");
Write_QSL_LIST($skcc, "SKCC_BURO");
Write_QSL_LIST($achvr_qsos, "ACHVR");

Write_Numbers($fists_nrs, $millionaires);
Write_Sat_Contacts($sat_contacts);
Write_Canadians($canadians);
Write_TenTen_Numbers($ten_ten_nrs);
Write_PSK_QSOS($psk_qsos);
Write_30MDG_QSOS($digital30m_qsos);

echo "Number of records processed: $rcrds_processed\n";

?>