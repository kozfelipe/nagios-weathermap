<?php

// weathermaphover.php
//
// Script creates an image/png using GD showing the nagios host state.
// Very useful in network-weathermap!
// It fetches the info from status.dat
//
// +-------------------------------------------------------------------------+
// | Weathermaphover for Nagios v0.7                                        |
// | by Jan Jaap van Santen                                                  |
// | github: janjaaps                                                        |
// | email: github@lebber.net                                                |
// | email: janjaap@scict.nl                                                 |
// +-------------------------------------------------------------------------+
// | Usage is very simple using the OVERLIBGRAPH on a NODE                   |
// | in the weathermap config.                                               |
// | And with just a monitored nagios host as input:                         |
// +-------------------------------------------------------------------------+
// | NODE <host>                                                             |
// |   LABEL <hostlabel>                                                     |
// |   OVERLIBGRAPH http://srvnagios/nagios/weathermaphover.php?host=<host> |
// +-------------------------------------------------------------------------+
// | The only setting needed to be made in this file is.                     |
// | Set this to your status.dat location:                                   |
// | $statusFile = "/var/ramdisk/status.dat";                                |
// | $statusFile = "/opt/nagios/var/status.dat";                             |
// +-------------------------------------------------------------------------+

// statusXML.php
//
// Script to parse Nagios status.dat and present it as XML, to allow inclusion
//   of status info in a web page served from a separate machine.
//
// +-------------------------------------------------------------------------+
// | XML Final Project      http://xmlfinal.jasonantman.com                  |
// +-------------------------------------------------------------------------+
// | Copyright (c) 2009 Jason Antman.                                        |
// |                                                                         |
// | This program is free software; you can redistribute it and/or modify    |
// | it under the terms of the GNU General Public License as published by    |
// | the Free Software Foundation; either version 3 of the License, or       |
// | (at your option) any later version.                                     |
// |                                                                         |
// | This program is distributed in the hope that it will be useful,         |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
// | GNU General Public License for more details.                            |
// |                                                                         |
// | You should have received a copy of the GNU General Public License       |
// | along with this program; if not, write to:                              |
// |                                                                         |
// | Free Software Foundation, Inc.                                          |
// | 59 Temple Place - Suite 330                                             |
// | Boston, MA 02111-1307, USA.                                             |
// +-------------------------------------------------------------------------+
// |Please use the above URL for bug reports and feature/support requests.   |
// +-------------------------------------------------------------------------+
// | Authors: Jason Antman <jason@jasonantman.com>                           |
// +-------------------------------------------------------------------------+
// | $LastChangedRevision::                                                $ |
// | $HeadURL::                                                            $ |
// +-------------------------------------------------------------------------+


//Function to create an image on error
function errorimage($message) {
        header('Content-Type: image/png');
        $im = imagecreatetruecolor(300, 300);
        $white = ImageColorAllocate ($im, 255, 255, 255);
        $black = ImageColorAllocate ($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        imagestring($im, 5, 1, 1,  'Error:', $black);
        imagestring($im, 5, 1, 20,  $message, $black);
        imagepng($im);
        imagedestroy($im);
        exit;
}

//Check arguments count
if (count($_GET) == 0) {
    errorimage("No arguments, use ?host=");
}

//Check if argument = host
if (!isset($_GET['host'])) {
    errorimage("Wrong arguments, use ?host=");
}

//Arguments to variable
$nagioshostid = $_GET['host'];


//Settings
#$statusFile = "/opt/nagios/var/status.dat";
$statusFile = "/var/ramdisk/status.dat";
$imagewidth = 500;
$maxservices = 20;
//end settings


$nag_version = getFileVersion($statusFile); // returns integer 2 or 3
$created_ts = 0;

$debug = false;

if($nag_version == 3)
  {
    $data = getData3($statusFile); // returns an array
  }
else
  {
    $data = getData2($statusFile); // returns an array
  }

$hosts = $data['hosts'];
$services = $data['services'];
$program = "";
if(array_key_exists("program", $data))
  {
    $program = $data['program'];
  }

//Changes Jan Jaap van Santen to create GD images for weathermap overlibgraph from nagios info

//Draw function
function drawhostline($im,$stringname,$stringtext,$fontsize,$height,$width,$state="none") {

        $white = ImageColorAllocate ($im, 255, 255, 255);
        $black = ImageColorAllocate ($im, 0, 0, 0);
        $green = ImageColorAllocate ($im, 0, 255, 0);
        $yellow = ImageColorAllocate ($im, 255, 255, 0);
        $red = ImageColorAllocate ($im, 255, 0, 0);
        $blue = ImageColorAllocate ($im, 0, 0, 255);
        $grey = ImageColorAllocate ($im, 210, 210, 210);
        $greylight = ImageColorAllocate ($im, 240, 240, 240);
        $current_state_color = Array('0' => $green, '1' => $yellow, '2' => $red, '3' => $blue);
        $current_state_text = Array('0' => "OK", '1' => "Warning", '2' => "Critical", '3' => "Unknown");

	//calculate extra need height
	$fontheight=imagefontheight($fontsize)+$height+2;
	
	if($state=="none") 
	{ 
		$statuscolor = $greylight; 
		$statustext = $stringtext;
	} 
	elseif($state == $stringtext) 
	{ 
		$statuscolor = $current_state_color[$state]; 
		$statustext = $current_state_text[$state];
	
	}
	else
	{
                $statuscolor = $current_state_color[$state];
                $statustext = $stringtext;
	}
	imagefilledrectangle($im, 1, $height, 110, $fontheight, $grey);
        imagestring($im, $fontsize, 4, $height+1, $stringname, $black);
        imagefilledrectangle($im, 110, $height, imagesx($im)-1, $fontheight, $statuscolor);
	imagestring($im, $fontsize, 114, $height+1,$statustext, $black);
        imagerectangle($im, 0, $height, 110, $fontheight, $black);
        imagerectangle($im, 110, $height, imagesx($im)-1, $fontheight, $black);

	return $fontheight;
}

$hostcount=0;

$servicescount=0;

foreach ($services as $hostName => $service)
   {
     foreach ($service as $serviceDesc => $serviceArray)
       {
        if($serviceArray['host_name'] == $nagioshostid)
        {
        $servicescount++;
        }
       }
   }


if($servicescount < $maxservices) 
{
	$serviceslines=$servicescount;
}
else
{
	$serviceslines=$maxservices;
}
$imageheight = 15 * 9 + 15 * $serviceslines +1;


foreach($hosts as $hostName => $hostArray)
    {
       if($hostArray['host_name'] == $nagioshostid)
       {

	//Build up image host part
        header('Content-Type: image/png');
        $im = imagecreatetruecolor($imagewidth, $imageheight);
	
	//Set initial fontheight/start point
	$fontheight=0;
	//Draw Hostname
	$fontheight=drawhostline($im,"Nagios hostname",$nagioshostid,3,$fontheight,$imagewidth);
	
	//Draw Host status
	$fontheight=drawhostline($im,"State",$hostArray['current_state'],2,$fontheight,$imagewidth,$hostArray['current_state']);
	
        //Draw Host plugin output
	$fontheight=drawhostline($im,"Output",$hostArray['plugin_output'],2,$fontheight,$imagewidth);        

	//Draw Host perf data
	$fontheight=drawhostline($im,"Perf data",$hostArray['performance_data'],2,$fontheight,$imagewidth);

	//Draw current attempt
	$fontheight=drawhostline($im,"Current attempt",$hostArray['current_attempt']."/".$hostArray['max_attempts'],2,$fontheight,$imagewidth);

        //Draw last check
        $fontheight=drawhostline($im,"Last check",date('Y-m-d G:i:s',$hostArray['last_check']),2,$fontheight,$imagewidth);

        //Draw next check
        $fontheight=drawhostline($im,"Next check",date('Y-m-d G:i:s',$hostArray['next_check']),2,$fontheight,$imagewidth);

        //Draw last state change
        $fontheight=drawhostline($im,"Last state change",date('Y-m-d G:i:s',$hostArray['last_state_change']),2,$fontheight,$imagewidth);

	//Draw Services
        $fontheight=drawhostline($im,"Services","Shown: " . $serviceslines ."/". $servicescount,3,$fontheight,$imagewidth);



        $hostcount++;



       }
    }

$servicescount=0;

foreach ($services as $hostName => $service)
   {
     foreach ($service as $serviceDesc => $serviceArray)
       {
	if($serviceArray['host_name'] == $nagioshostid) 
	{
	//Draw Hostname
        $fontheight=drawhostline($im,$serviceArray['service_description'],$serviceArray['plugin_output'],2,$fontheight,$imagewidth,$serviceArray['current_state']);
	}
       }
   }






if($hostcount!=1) {
    errorimage("Specified host doesn't exist!");
}
imagepng($im);
imagedestroy($im);
exit;


// END Changes

//no xml output
//outputXML($hosts, $services, $program);

function outputXML($hosts, $services, $program)
{
    // begin outputting XML
    //header("Content-type: text/xml"); 
    echo "<?xml version=\"1.0\"?>"."\n";
    echo "<nagios_status>"."\n";
    
    // program status
    if($program != "")
      {
	echo '<programStatus>'."\n";
	foreach($program as $key => $val)
	  {
	    $key = xmlname($key);
	    $val = xmlstring($val);
	    echo '      <'.$key.'>'.$val.'</'.$key.'>'."\n";
	  }
	echo '</programStatus>'."\n";
      }

    // hosts
    echo '<hosts>'."\n";
    foreach($hosts as $hostName => $hostArray)
    {
	$current_state = $hostArray['current_state'];
	echo '   <host>'."\n";
	foreach($hostArray as $key => $val)
	  {
	    $key = xmlname($key);
	    $val = xmlstring($val);
	    echo '      <'.$key.'>'.$val.'</'.$key.'>'."\n";
	  }
	echo '   </host>'."\n";
    }
    echo '</hosts>'."\n";
    
    // loop through the services
    echo '<services>'."\n";
    foreach ($services as $hostName => $service)
      {
	foreach ($service as $serviceDesc => $serviceArray)
	  {
	    echo '   <service>'."\n";
	    foreach($serviceArray as $key => $val)
	      {
		$key = xmlname($key);
		$val = xmlstring($val);
		echo '      <'.$key.'>'.$val.'</'.$key.'>'."\n";
	      }
	    echo '   </service>'."\n";
	  }
      }
    echo '</services>'."\n";
    echo "</nagios_status>";
}

// replace reserved characters in key for name
function xmlname($s)
{
  $s = str_replace("<", "&lt;", $s);
  $s = str_replace(">", "&gt;", $s);
  $s = str_replace("&", "&amp;", $s);
  return $s;
}

function xmlstring($s)
{
  $s = str_replace("<", "&lt;", $s);
  $s = str_replace(">", "&gt;", $s);
  $s = str_replace("&", "&amp;", $s);
  return $s;
}

// figure out what version the file is
function getFileVersion($statusFile)
{
  global $created_ts;
  $version = 2;

  $fh = fopen($statusFile, 'r');
  $inInfo = false;
  while($line = fgets($fh))
    {
      if(trim($line) == "info {")
	{
	  $inInfo = true;
	}
      elseif(trim($line) == "}")
	{
	  $inInfo = false;
	  break;
	}
      elseif($inInfo)
	{
	  $vals = explode("=", $line);
	  if(trim($vals[0]) == "created")
	    {
	      $created = $vals[1];
	    }
	  elseif(trim($vals[0]) == "version")
	    {
	      if(substr($vals[1], 0, 1) == "3")
		{
		  $version = 3;
		}
	    }
	}
    }
  return $version;
}

// parse nagios2 status.dat
function getData2($statusFile)
{
    // the keys to get from host status:
    $host_keys = array('host_name', 'has_been_checked', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'current_attempt', 'state_type', 'last_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'last_update');

    // keys to get from service status:
    $service_keys = array('host_name', 'service_description', 'has_been_checked', 'check_execution_time', 'check_latency', 'current_state', 'state_type', 'last_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'last_check', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'last_update', 'is_flapping');

    # open the file
    $fh = fopen($statusFile, 'r');
    
    # variables to keep state
    $inSection = false;
    $sectionType = "";
    $lineNum = 0;
    $sectionData = array();

    $hostStatus = array();
    $serviceStatus = array();

    #variables for total hosts and services
    $typeTotals = array();
    
    # loop through the file
    while($line = fgets($fh))
    {
	$lineNum++; // increment counter of line number, mainly for debugging
	$line = trim($line); // strip whitespace
	if($line == ""){ continue;} // ignore blank line
	if(substr($line, 0, 1) == "#"){	continue;} // ignore comment
	
	// ok, now we need to deal with the sections
	
	if(! $inSection)
	{
	    // we're not currently in a section, but are looking to start one
	    if(strstr($line, " ") && (substr($line, -1) == "{")) // space and ending with {, so it's a section header
	    {
		$sectionType = substr($line, 0, strpos($line, " ")); // first word on line is type
		$inSection = true;
		// we're now in a section
		$sectionData = array();

		// increment the counter for this sectionType
		if(isset($typeTotals[$sectionType])){$typeTotals[$sectionType]=$typeTotals[$sectionType]+1;}else{$typeTotals[$sectionType]=1;}
		
	    }
	}
    
	if($inSection && $line == "}") // closing a section
	{
	    if($sectionType == "service")
	    {
		$serviceStatus[$sectionData['host_name']][$sectionData['service_description']] = $sectionData;
	    }
	    if($sectionType == "host")
	    {
		$hostStatus[$sectionData["host_name"]] = $sectionData;
	    }
	    $inSection = false;
	    $sectionType = "";
	    continue;
	}
	else
	{
	    // we're currently in a section, and this line is part of it
	    $lineKey = substr($line, 0, strpos($line, "="));
	    $lineVal = substr($line, strpos($line, "=")+1);
	    
	    // add to the array as appropriate
	    if($sectionType == "service")
	    {
		if(in_array($lineKey, $service_keys))
		{
		    $sectionData[$lineKey] = $lineVal;
		}
	    }
	    elseif($sectionType == "host")
	    {
		if(in_array($lineKey, $host_keys))
		{
		    $sectionData[$lineKey] = $lineVal;
		}
	    }
	    // else continue on, ignore this section, don't save anything
	}

    }
    
    fclose($fh);
    
    $retArray = array("hosts" => $hostStatus, "services" => $serviceStatus);
    
    return $retArray;    
}

// parse nagios3 status.dat
function getData3($statusFile)
{
  global $debug;
    // the keys to get from host status:
  $host_keys = array('host_name', 'modified_attributes', 'check_command', 'check_period', 'notification_period', 'check_interval', 'retry_interval', 'event_handler', 'has_been_checked', 'should_be_scheduled', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'last_hard_state', 'last_event_id', 'current_event_id', 'current_problem_id', 'last_problem_id', 'plugin_output', 'long_plugin_output', 'performance_data', 'last_check', 'next_check', 'check_options', 'current_attempt', 'max_attempts', 'state_type', 'last_state_change', 'last_hard_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'current_notification_id', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'event_handler_enabled', 'flap_detection_enabled', 'failure_prediction_enabled', 'process_performance_data', 'obsess_over_host', 'last_update', 'is_flapping', 'percent_state_change', 'scheduled_downtime_depth');
  // keys to get from service status:
  $service_keys = array('host_name', 'service_description', 'modified_attributes', 'check_command', 'check_period', 'notification_period', 'check_interval', 'retry_interval', 'event_handler', 'has_been_checked', 'should_be_scheduled', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'last_hard_state', 'last_event_id', 'current_event_id', 'current_problem_id', 'last_problem_id', 'current_attempt', 'max_attempts', 'state_type', 'last_state_change', 'last_hard_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'long_plugin_output', 'performance_data', 'last_check', 'next_check', 'check_options', 'current_notification_number', 'current_notification_id', 'last_notification', 'next_notification', 'no_more_notifications', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'event_handler_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'flap_detection_enabled', 'failure_prediction_enabled', 'process_performance_data', 'obsess_over_service', 'last_update', 'is_flapping', 'percent_state_change', 'scheduled_downtime_depth');

    # open the file
    $fh = fopen($statusFile, 'r');

    # variables to keep state
    $inSection = false;
    $sectionType = "";
    $lineNum = 0;
    $sectionData = array();

    $hostStatus = array();
    $serviceStatus = array();
    $programStatus = array();

    #variables for total hosts and services
    $typeTotals = array();
    
    # loop through the file
    while($line = fgets($fh))
    {
	$lineNum++; // increment counter of line number, mainly for debugging
	$line = trim($line); // strip whitespace
	if($line == ""){ continue;} // ignore blank line
	if(substr($line, 0, 1) == "#"){	continue;} // ignore comment
	
	// ok, now we need to deal with the sections
	if(! $inSection)
	{
	    // we're not currently in a section, but are looking to start one
	  if(substr($line, strlen($line)-1, 1) == "{") // space and ending with {, so it's a section header
	    {
		$sectionType = substr($line, 0, strpos($line, " ")); // first word on line is type
		$inSection = true;
		// we're now in a section
		$sectionData = array();

		// increment the counter for this sectionType
		if(isset($typeTotals[$sectionType])){$typeTotals[$sectionType]=$typeTotals[$sectionType]+1;}else{$typeTotals[$sectionType]=1;}
		
	    }
	}
    	elseif($inSection && trim($line) == "}") // closing a section
	{
	    if($sectionType == "servicestatus")
	    {
		$serviceStatus[$sectionData['host_name']][$sectionData['service_description']] = $sectionData;
	    }
	    elseif($sectionType == "hoststatus")
	    {
		$hostStatus[$sectionData["host_name"]] = $sectionData;
	    }
	    elseif($sectionType == "programstatus")
	      {
		$programStatus = $sectionData;
	      }
	    $inSection = false;
	    $sectionType = "";
	    continue;
	}
	else
	{
	    // we're currently in a section, and this line is part of it
	    $lineKey = substr($line, 0, strpos($line, "="));
	    $lineVal = substr($line, strpos($line, "=")+1);

	    // add to the array as appropriate
	    if($sectionType == "servicestatus" || $sectionType == "hoststatus" || $sectionType == "programstatus")
	    {
	      if($debug){ echo "LINE ".$lineNum.": lineKey=".$lineKey."= lineVal=".$lineVal."=\n";}
	      $sectionData[$lineKey] = $lineVal;
	    }
	    // else continue on, ignore this section, don't save anything
	}

    }
    
    fclose($fh);

    $retArray = array("hosts" => $hostStatus, "services" => $serviceStatus, "program" => $programStatus);
    return $retArray;    
}


// this formats the age of a check in seconds into a nice textual description
function ageString($seconds)
{
	$age = "";
	if($seconds > 86400)
	{
		$days = (int)($seconds / 86400);
		$seconds = $seconds - ($days * 86400);
		$age .= $days." days ";
	}
	if($seconds > 3600)
	{
		$hours = (int)($seconds / 3600);
		$seconds = $seconds - ($hours * 3600);
		$age .= $hours." hours ";
	}
	if($seconds > 60)
	{
		$minutes = (int)($seconds / 60);
		$seconds = $seconds - ($minutes * 60);
		$age .= $minutes." minutes ";
	}
	$age .= $seconds." seconds ";
	return $age;
}


/*
    // the keys to get from host status:
    $host_keys = array('host_name', 'has_been_checked', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'current_attempt', 'state_type', 'last_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'last_update');

    // keys to get from service status:
    $service_keys = array('host_name', 'service_description', 'has_been_checked', 'check_execution_time', 'check_latency', 'current_state', 'state_type', 'last_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'last_check', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'last_update', 'is_flapping');

*/


?>

