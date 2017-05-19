#!/usr/bin/php -q 
<? 
 ob_implicit_flush(false); 
 error_reporting(0); 
 set_time_limit(300); 

//   Nerd Vittles Weather, (c) Copyright Ward Mundy, 2006. All rights reserved.

//-------- DON'T CHANGE ANYTHING ABOVE THIS LINE ----------------

 $debug = 0; 
 $newlogeachdebug = 1;
 $emaildebuglog = 0;
 $email = "yourname@yourdomain" ;

//-------- DON'T CHANGE ANYTHING BELOW THIS LINE ----------------

$log = "/var/log/asterisk/nv-weather.txt" ;
if ($debug and $newlogeachdebug) :
 if (file_exists($log)) :
  unlink($log) ;
 endif ;
endif ;

 $stdlog = fopen($log, 'a'); 
 $stdin = fopen('php://stdin', 'r'); 
 $stdout = fopen( 'php://stdout', 'w' ); 

if ($debug) :
  fputs($stdlog, "Nerd Vittles Weather (c) Copyright 2006, Ward Mundy. All Rights Reserved.\n\n" . date("F j, Y - H:i:s") . "  *** New session ***\n\n" ); 
endif ;

function read() {  
 global $stdin;  
 $input = str_replace("\n", "", fgets($stdin, 4096));  
 dlog("read: $input\n");  
 return $input;  
}  

function write($line) {  
 dlog("write: $line\n");  
 echo $line."\n";  
}  

function dlog($line) { 
 global $debug, $stdlog; 
 if ($debug) fputs($stdlog, $line); 
} 

function execute_agi( $command ) 
{ 
GLOBAL $stdin, $stdout, $stdlog, $debug; 
 
fputs( $stdout, $command . "\n" ); 
fflush( $stdout ); 
if ($debug) 
fputs( $stdlog, $command . "\n" ); 
 
$resp = fgets( $stdin, 4096 ); 
 
if ($debug) 
fputs( $stdlog, $resp ); 
 
if ( preg_match("/^([0-9]{1,3}) (.*)/", $resp, $matches) )  
{ 
if (preg_match('/result=([-0-9a-zA-Z]*)(.*)/', $matches[2], $match))  
{ 
$arr['code'] = $matches[1]; 
$arr['result'] = $match[1]; 
if (isset($match[3]) && $match[3]) 
$arr['data'] = $match[3]; 
return $arr; 
}  
else  
{ 
if ($debug) 
fputs( $stdlog, "Couldn't figure out returned string, Returning code=$matches[1] result=0\n" );  
$arr['code'] = $matches[1]; 
$arr['result'] = 0; 
return $arr; 
} 
}  
else  
{ 
if ($debug) 
fputs( $stdlog, "Could not process string, Returning -1\n" ); 
$arr['code'] = -1; 
$arr['result'] = -1; 
return $arr; 
} 
}  

// ------ Code execution begins here
// parse agi headers into array  
//while ($env=read()) {  
// $s = split(": ",$env);  
// $agi[str_replace("agi_","",$s0)] = trim($s1); 
// if (($env == "") || ($env == "\n")) {  
//   break;  
// }  
//}  

while ( !feof($stdin) )  
{ 
$temp = fgets( $stdin ); 
 
if ($debug) 
fputs( $stdlog, $temp ); 
 
// Strip off any new-line characters 
$temp = str_replace( "\n", "", $temp ); 
 
$s = explode( ":", $temp ); 
$agivar[$s[0]] = trim( $s[1] ); 
if ( ( $temp == "") || ($temp == "\n") ) 
{ 
break; 
} 
}  

$airportcode = $_SERVER["argv"][1];

if ($debug) :
fputs($stdlog, "Airport Code: " . $APCODE . "\n" );
endif ;




$token = md5 (uniqid (""));
$tmptext = "/tmp/tts-$token.txt" ;
$tmpwave = "/var/lib/asterisk/sounds/tts/tts-$token.wav" ;
$fd = fopen("http://pdaweather.org/text.php?city=$airportcode", "r"); 
if (!$fd) {
 echo "<p>Unable to open web connection. \n"; 
 exit; 
} 
$value = "";
while(!feof($fd)){
	$value .= fread($fd, 4096);	
}
fclose($fd);

$fd = fopen($tmptext, "w"); 
if (!$fd) {
 echo "<p>Unable to open temporary text file in /tmp for writing. \n"; 
 exit; 
} 
$retcode = fwrite($fd,$value);	
fclose($fd);

$retcode2 = system ("flite -f  $tmptext -o $tmpwave") ;

unlink ("$tmptext") ;

$tmpwave = "tts/tts-$token" ;

execute_agi("SET VARIABLE TMPWAVE $tmpwave"); 

if ($emaildebuglog) :
 system("mime-construct --to $email --subject " . chr(34) . "Nerd Vittles Weather Session Log" . chr(34) . " --attachment $log --type text/plain --file $log") ;
endif ;


// clean up file handlers etc.  
fclose($stdin);  
fclose($stdout);
fclose($stdlog);  
exit;   
  
?>

