<?php session_start();
error_reporting(0);
if(strtolower(basename($_SERVER['SCRIPT_NAME']))!='index.php' && !isset($_SESSION['userlogged']))
 	header("Location:index.php");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Welcome to ajsmrjournal.com</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<script language="JavaScript" src="calendar/calendar_db.js"></script>
<link rel="stylesheet" href="calendar/calendar.css">
</head>
<body>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" valign="top"><table width="1003" align="center" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td align="center" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="168" align="left" valign="top"><table width="1003" border="0" cellspacing="0" cellpadding="0" align="center">
                  <tr>
                    <td width="50%" height="123" align="left" valign="middle"><a href="adminhome.php"><img src="images/logo.png" alt="logo.png" width="250" height="175" vspace="0" border="0" /></a></td>
                    <td width="50%" align="right" valign="middle">&nbsp;</td>
                </tr>
                  <tr>
                    <td height="45" align="left" valign="middle"><table width="98%" border="0" align="left" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="11%" align="right" valign="middle"><img src="images/admin-icon.jpg" width="32" height="32" hspace="5" /></td>
                          <td width="89%" align="left" valign="middle" class="red_heading_txt_16px uppercase_txt">Admin <span class="green_heading_txt_16px uppercase_txt">Area </span></td>
                        </tr>
                    </table></td>
                    <td valign="middle" align="right" class="body_txt_bold12px">
						<?php 
							if(isset($_SESSION['userlogged']))
							{ 
						?>
								Welcome <a href="adminprofile.php" ><?php echo $_SESSION['userlogged'];?></a> |
								<a href='logout.php'>Logout</a>
						<?php
							}	
						?></td>
                  </tr>
              </table></td>
            </tr>