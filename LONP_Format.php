#! /usr/bin/php
<?

// LONP_Format.PHP
//
// Roger Banks 15 Dec 2007
// This program operates on a file called "lonp.txt" and outputs the data into a CSV
// file called "lonp.csv". The input file is the LONP data copied from:
// http://home.insightbb.com/~ctrice/070_LONP.html and saved to a file. The output
// is formatted to be imported by Excel or Filemaker Pro. 

$filename = "lonp.txt";
$outfilename = "lonp.csv";
$handle = fopen($filename, "r");
$out_handle = fopen($outfilename, "w");
$line = "";
if ($handle)
{
	if($out_handle)
	{
		while (!feof($handle))
		{
			for($i = 0; $i < 6; $i++)
			{
				$buffer = fgets($handle, 4096);
				$line .= trim($buffer);
				$line .= ",";
			}
			$line .= "\n";
			fwrite($out_handle, $line);
			$line = "";
		}
		fclose($out_handle);
	}
	fclose($handle);
}
?>