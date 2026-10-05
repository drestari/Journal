<?php
if(isset($_POST["email"]))
{
$admessage ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>ajsmrjournal.com</title>
<link href="http://'.$_SERVER['HTTP_HOST'].'/admin/css/style.css" rel="stylesheet" type="text/css" /></head>
<body>
	<table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="font:normal 12px arial; color:#000;">
	<tr>
		<td height="35" align="left" valign="middle" colspan="2" style="background:#006699; font:bold 12px Georgia;color:#fff; text-indent:20px;" >Message Form ajsmrjournal Contact  Form </td>
	</tr>
	<tr><td colspan="2" align="center"><table width="100%" style="border:1px solid #006699" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" valign="top"><table width="96%" border="0" cellspacing="0" cellpadding="8" align="center" class="border1">
          <tr>
            <td width="27%" height="10" class="style2"></td>
            <td width="73%" height="5" class="text"></td>
          </tr>
          <tr> </tr>
		   <tr>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="style2" style="PADDING-LEFT=5PX;"><strong>Query of Type: </strong></td>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="text" style="PADDING-LEFT=5PX;">'.$queryoftype.'</td>
          </tr>
          
          <tr>
            <td width="27%" height="5" bgcolor="#FFFFFF" class="style2"></td>
            <td width="73%" height="10" bgcolor="#FFFFFF" class="text"></td>
          </tr>
		  <tr>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="style2" style="PADDING-LEFT=5PX;"><strong>Full Name: </strong></td>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="text" style="PADDING-LEFT=5PX;">'.$name.'</td>
          </tr>
          
          <tr>
            <td width="27%" height="5" bgcolor="#FFFFFF" class="style2"></td>
            <td width="73%" height="10" bgcolor="#FFFFFF" class="text"></td>
          </tr>
		   <tr>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="style2" style="PADDING-LEFT=5PX;"><strong>Phone Number:</strong></td>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="text" style="PADDING-LEFT=5PX;">'.$pnumber.'</td>
          </tr>
		   <tr>
            <td width="27%" height="5" bgcolor="#FFFFFF" class="style2"></td>
            <td width="73%" height="10" bgcolor="#FFFFFF" class="text"></td>
          </tr>
		   <tr>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="style2" style="PADDING-LEFT=5PX;"><strong>Email ID:</strong></td>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="text" style="PADDING-LEFT=5PX;">'.$email.'</td>
          </tr>
          <tr>
            <td width="27%" height="5" bgcolor="#FFFFFF" class="style2"></td>
            <td width="73%" height="10" bgcolor="#FFFFFF" class="text"></td>
          </tr>
		  
		   
		   <tr>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="style2" style="PADDING-LEFT=5PX;"><strong>Message:</strong></td>
            <td align="left" valign="top" bgcolor="#f7f7f7" class="text" style="PADDING-LEFT=5PX;">'.$textmessage.'</td>
          </tr>
          <tr>
            <td width="27%" height="5" bgcolor="#FFFFFF" class="style2"></td>
            <td width="73%" height="10" bgcolor="#FFFFFF" class="text"></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
	</TR>
	</table>
	';
	}
?>