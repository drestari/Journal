<?php
	$tstr = $_SERVER['REQUEST_URI'];
	$tarray = explode("=",$tstr);
	if(count(explode("%27",$tarray[1]))>1)
	{
		//substr(
		//$tarray = explode("%27",$tarray[1]);
		$tstr = substr($tarray[1],3,count($tarray[1])-4);
		if(strpos($tstr,"%27"))
		{
			$tstr = str_replace("%27","'",$tstr);
		}
		echo $tstr;
		exit;
		//$tarr = str_replace("%20",' ',$tarray[1]);
		$tarr = str_replace("%20",' ',$tstr);
		$myfile = $tarr;
	}
	else if(count(explode("'",$tarray[1]))>1)
	{
		$str1 = substr($tarray[1],1,strlen($tarray[1])-2);
		if(strpos($str1,"%20"))
		{
			$str1 = str_replace("%20",' ',$str1);
		}
		$myfile = $str1;
	}
	else
		$myfile = $_REQUEST['f'];
	//exit;
	if ( file_exists($myfile) )  
	{
		$mm_type="application/octet-stream";
		header("Cache-Control: public, must-revalidate");
		header("Pragma: hack"); 
		header("Content-Type: " . $mm_type);
		header("Content-Length: " .(string)(filesize($myfile)) );
		header('Content-Disposition: attachment; filename="'.basename($myfile).'"');
		header("Content-Transfer-Encoding: binary\n");
		ob_clean();
		flush();
		readfile($myfile);
	}
	exit;
?>