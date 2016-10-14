<?php
/*!***************************************************************************
*! FILE NAME  : eyesis4pi_controls.php
*! DESCRIPTION: change the settings of the 8 eyesis4pi cameras
*! Copyright (C) 2012 Elphel, Inc
*! -----------------------------------------------------------------------------**
*!  This program is free software: you can redistribute it and/or modify
*!  it under the terms of the GNU General Public License as published by
*!  the Free Software Foundation, either version 3 of the License, or
*!  (at your option) any later version.
*!
*!  This program is distributed in the hope that it will be useful,
*!  but WITHOUT ANY WARRANTY; without even the implied warranty of
*!  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*!  GNU General Public License for more details.
*!
*!  The four essential freedoms with GNU GPL software:
*!  * the freedom to run the program for any purpose
*!  * the freedom to study how the program works and change it to make it do what you wish
*!  * the freedom to redistribute copies so you can help your neighbor
*!  * the freedom to distribute copies of your modified versions to others
*!
*!  You should have received a copy of the GNU General Public License
*!  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*! -----------------------------------------------------------------------------**
*!  $Log: eyesis4pi_controls.php,v $
*/

// Check location - should be launched on the PC websrver
$elp_const=get_defined_constants(true);


if (isset($elp_const["elphel"])) {
  $fp = fopen($_SERVER['SCRIPT_FILENAME'], 'rb');
  fseek($fp, 0, SEEK_END);  /// file pointer at the end of the file (to find the file size)
  $fsize = ftell($fp);      /// get file size
  fseek($fp, 0, SEEK_SET);  /// rewind to the start of the file
  /// send the headers
  header("Content-Type: application/x-php");
  header("Content-Length: ".$fsize."\n");
  fpassthru($fp);           /// send the script (this file) itself
  fclose($fp);
  exit (0);
}

// defaults
$i2c = false;

$temperature = false;
$set_parameter = false;
$get_parameter = false;
$test=false;
$res_xml = "";

$debug=true;

$run_camogm=false;
$start = false;
$stop = false;
$mount = false;
$unmount = false;
$get_hdd_space = false;
$status = false;
$exit = false;
$run_status = false;

// keys assign
foreach($_GET as $key=>$value) {
	switch($key) {
		case "set_parameter"   : $set_parameter = true; break;
		case "get_parameter"   : $get_parameter = true; break;
		case "pname"           : $pname  = $value; break;
		case "pvalue"          : $pvalue = $value; break;

		case "i2c"             : $i2c = true; break;
		case "i2c_width"       : $i2c_width = $value; break;
		case "i2c_bus"         : $i2c_bus = $value; break;
		case "i2c_adr"         : $i2c_adr = $value; break;
		case "i2c_data"        : $i2c_data = $value; break;

		case "test"            : $test=true; break;
		
		case "run_camogm"      : $run_camogm = true; break;
		case "exit"            : $exit = true; break;
		case "start"           : $start = true; break;
		case "stop"            : $stop = true; break;
		case "status"          : $status=true; break;
		case "run_status"      : $run_status=true; break;

		//camogm
		case "split"           : $split = true; break;
		case "duration"        : $split_duration= $value+0; break;
		case "size"            : $split_size= $value+0; break;
		case "max_frames"      : $split_max_frames= $value+0; break;
		case "rec_delay"       : $rec_delay=$value+0; break;
		case "set_prefix"      : $set_prefix=true; break;
		case "prefix"          : $prefix = $value; break;

		case "mount"           : $mount=true; break;
		case "unmount"         : $unmount=true; break;
		
		case "get_hdd_space"   : $get_hdd_space=true; break;
		case "mount_point"     : $mount_point = $value; break;
		
		//case "debug"           : $debug = $value; break;
		case "debuglev"        : $debuglev = $value+0; break;
		//end camogm
	}
}

$master_ip = "";
$master_port = "";
$master_channel = "";

// keys assign
$cams = array();
if (isset($_GET['rq'])){
  $pars = explode(",",$_GET['rq']);
  foreach($pars as $val){
    $ip = strtok($val,":");
    $port = strtok(":");
    $channel = strtok(":");
    $master = strtok(":");
    array_push($cams,array('ip'=>$ip,'port'=>$port,'channel'=>$channel,'master'=>$master));
    if ($master=="1"){
        $master_ip = $ip;
        $master_port = $port;
        $master_channel = $channel;
    }
  }
}

//overrides
$debuglev = 0;

$pc_time=getdate();

//perform some tests
if ($test) {
}

if ($set_parameter) {
    for ($i=0;$i<count($cams);$i++){
        if ($pname=="SET_SKIP") 
            $fp = fopen("http://{$cams[$i]['ip']}/camogm_interface.php?sensor_port={$cams[$i]['channel']}&cmd=set_skip&skip_mask=".dechex($pvalue), 'r');
        else
            $fp = fopen("http://{$cams[$i]['ip']}/camogm_interface.php?sensor_port={$cams[$i]['channel']}&cmd=set_parameter&pname=$pname&pvalue=$pvalue", 'r');

        if ($fp) {
            $content = '';
            while ($line = fread($fp, 1024)) {
                $content .= $line;
            }
            $message_IP[$i] = $content;
        }else{
            $message_IP[$i] = "no connection";
        }
        $res_xml .= $message_IP[$i];
    }

    header("Content-Type: text/xml");
    header("Content-Length: ".strlen($res_xml)."\n");
    header("Pragma: no-cache\n");
    printf("%s", $res_xml);
    flush();
}

if ($get_parameter) {
	for ($i=0;$i<count($cams);$i++) {
                $content = file_get_contents("http://{$cams[$i]['ip']}/camogm_interface.php?sensor_port={$cams[$i]['channel']}&cmd=get_parameter&pname=$pname");
                $message_IP[$i] = $content;
                $res_xml .= $message_IP[$i];
	}

	header("Content-Type: text/xml");
	header("Content-Length: ".strlen($res_xml)."\n");
	header("Pragma: no-cache\n");
	printf("%s", $res_xml);
	flush();

}

//CAMOGM
if ($run_camogm) {
	for ($i=0;$i<count($cams);$i++) {
	    // start camogm
	    //if (!$debug) fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_camogm", 'r');
	    //else         fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_camogm&debug=$debug&debuglev=$debuglev", 'r');
	    
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_camogm", 'r');
	    
	    // set debug level
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_debuglev&debuglev=$debuglev", 'r');
	    // set "/var/0" prefix
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_prefix&prefix=/var/html/CF/", 'r');
            // default path
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=setrawdevpath&path=/dev/sda2", 'r');
	    //mount
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=mount&partition=/dev/hda1&mountpoint=/var/html/CF", 'r');
	    // set .mov format
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=setjpeg", 'r');
	    // set frames_per_chunk in exif to 1
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_frames_per_chunk&frames_per_chunk=1", 'r');
	    // set default split parameters
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_duration&duration=1000", 'r');
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_size&size=1800000000", 'r');
	    //debugging
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_max_frames&max_frames=1000", 'r');
	    
	    /*
	    // start camogm
	    if (!$debug) fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_camogm", 'r');
	    else         fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_camogm&debug=$debug&debuglev=$debuglev", 'r');

	    // set debug level
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_debuglev&debuglev=$debuglev", 'r');

	    // set "/var/0" prefix
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_prefix&prefix=/var/html/CF/", 'r');

            // default path
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=setrawdevpath&path=/dev/sda1", 'r');
	    //mount
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=mount&partition=/dev/hda1&mountpoint=/var/html/CF", 'r');

	    // set .mov format
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=setjpeg", 'r');
	    
	    // set frames_per_chunk in exif to 1
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_frames_per_chunk&frames_per_chunk=1", 'r');
	    // set default split parameters
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_duration&duration=1000", 'r');
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_size&size=1800000000", 'r');
	    
	    //debugging
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_max_frames&max_frames=1000", 'r');
	    
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_max_frames&max_frames=100", 'r');
	    
	    //create dir
	    //fopen("http://{$cams[$i]['ip']}/phpshell.php?command=mkdir%20/var/html/CF", 'r');
	    //set prefix?!
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=set_prefix&prefix=/var/html/CF/", 'r');
	    */
	}
}

if ($exit) {
	for ($i=0;$i<count($cams);$i++) fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=exit", 'r');
}

if ($start) {
	if ($rec_delay<>0) {
		// get time from master camera
		if ($fp = fopen("http://$master_ip/camogm_interface.php?sensor_port=$master_channel&cmd=gettime", 'r')) {
			$content = '';
			while ($line = fread($fp, 1024)) {
				$content .= $line;
			}
			$xml = new SimpleXMLElement($content);
			$current_time=$xml -> gettime;
		} else {
			// just PC time
			$pc_time=getdate();
			$current_time = $pc_time[0];
		}

	  $start_timestamp=$current_time+$rec_delay;

	  //was commented for testing
	  //for ($i=0;$i<$n;$i++) fopen("http://".$cam_ip[$i]."/camogm_interface.php?cmd=set_start_after_timestamp&start_after_timestamp=$start_timestamp", 'r');
	}

	for ($i=0;$i<count($cams);$i++) {
            //delayed start?
	    //file_get_contents("http://$master_ip:$master_port/bimg");
	    //file_get_contents("http://".$cam_ip[0].":8081/bimg");
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=start", 'r');
	}
}

if ($stop) {
	for ($i=0;$i<count($cams);$i++) fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=stop", 'r');
}

if ($mount) {
	for ($i=0;$i<count($cams);$i++) {
	    fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=mount&partition=/dev/sda1&mountpoint=/mnt/sda1", 'r');
	}
}

if ($unmount) {
	for ($i=0;$i<count($cams);$i++) {
	    //fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=unmount&mountpoint=/var/html/CF", 'r');
	    //fopen("http://{$cams[$i]['ip']}/phpshell.php?command=sync", 'r');
	}
}

if ($get_hdd_space) {
    for ($i=0;$i<count($cams);$i++) {
        //$content = file_get_contents()
		if ($fp = fopen("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=get_hdd_space&mountpoint=$mount_point", 'r')) {
			$content = '';
			while ($line = fread($fp, 1024)) {
				$content .= $line;
			}

			$xml = new SimpleXMLElement($content);
			$free_space = $xml -> get_hdd_space;
			$message_IP[$i] = $free_space;
		} else {
			$message_IP[$i] = "no connection";
		}
		
	}
        $res_xml = "<?xml version='1.0' standalone='yes'?>\n<camogm_multiple>\n";

	for ($i=0;$i<count($cams);$i++) {
		$res_xml .= "<cam>\n";
		$res_xml .= "<hdd_free_space>\n";
		$res_xml .= $message_IP[$i];
		$res_xml .= "</hdd_free_space>\n";
		$res_xml .= "</cam>\n";
	}

	$res_xml .= "</camogm_multiple>\n";

	header("Content-Type: text/xml");
	header("Content-Length: ".strlen($res_xml)."\n");
	header("Pragma: no-cache\n");
	printf("%s", $res_xml);
	flush();
}

if ($status) {
    for ($i=0;$i<count($cams);$i++) {
        $content = file_get_contents("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=status");
        $content = substr($content,22);
        $message_IP[$i] = $content;
    }

    //add get_hdd_space here?

    $res_xml = "<?xml version='1.0' standalone='yes'?>\n<camogm_multiple>\n";

    for ($i=0;$i<count($cams);$i++) {
        $res_xml .= "<cam>\n";
        $res_xml .= $message_IP[$i];
        $res_xml .= "</cam>\n";
    }

    $res_xml .= "</camogm_multiple>\n";

    header("Content-Type: text/xml");
    header("Content-Length: ".strlen($res_xml)."\n");
    header("Pragma: no-cache\n");
    printf("%s", $res_xml);
    flush();
}

if ($run_status) {
    for ($i=0;$i<count($cams);$i++) {
        $content = file_get_contents("http://{$cams[$i]['ip']}/camogm_interface.php?cmd=run_status");
        $content = new SimpleXMLElement($content);
        $message_IP[$i] = "\t<camogm_state>".($content->state)."</camogm_state>\n";
    
        //add get_hdd_space here?

        $res_xml = "<?xml version='1.0' standalone='yes'?>\n<camogm_multiple>\n";
    }
    for ($i=0;$i<count($cams);$i++) {
        $res_xml .= "<cam>\n";
        $res_xml .= $message_IP[$i];
        $res_xml .= "</cam>\n";
}
    $res_xml .= "</camogm_multiple>\n";
  
    header("Content-Type: text/xml");
    header("Content-Length: ".strlen($res_xml)."\n");
    header("Pragma: no-cache\n");
    printf("%s",$res_xml);
    flush();
}

?>