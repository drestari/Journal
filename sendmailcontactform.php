<?php
	if($_POST) 
	{
		extract($_POST);
		include("mailformatcontact.php");
		
	 				
		//to admin 
		$to= "editorajsmr@gmai.com";		
		$userfrom="Ajsmr Journal<editorajsmr@gmai.com>";				
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";			
		$headers = $headers."From: ".$userfrom. "\r\n";	
		$msubject = "Message Form Ajsmr Journal Contact Form";
		$mdescription = $admessage;			
		mail($to, $msubject, $mdescription, $headers);
		unset($_POST);
	 	//////
		header("Location:thankyou.php");
		exit;
	}
?>