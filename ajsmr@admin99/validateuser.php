<?php
	session_start();
	require_once("config/config.inc.php");
	$username = htmlspecialchars(trim($_POST['user']));
	$password = htmlspecialchars(trim($_POST['pwd']));
	$sql="SELECT * from adminusers where username='$username' and password='$password' and status='1'";
	/*echo $sql;
	exit;*/
	$result = mysql_query($sql) or die(mysql_error());

	if (mysql_num_rows($result)>0)
	{
		$row=mysql_fetch_array($result);
		$_SESSION['adminId']=$row['auid'];
		$_SESSION['userlogged']=$row['username'];
		header("location:adminhome.php");
		exit;
	}
	else
	{
		header("Location: index.php?msg=I");
		exit;
	}
?>