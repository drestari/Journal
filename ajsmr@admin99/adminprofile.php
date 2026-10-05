<?php
	include("header.php");
	include("process.php");
	if(isset($_POST['Submit']))//add admin
	{
		extract($_POST);
    	if(manageadminusers($fname,$lname,$username,$password,$email,$auid))
		{
		   $msg = "Updated successfully";
		}
	}
?>
<script language="JavaScript" src="calendar/calendar_db.js"></script>
<script language="JavaScript" src="common/adminprofile.js"></script>
<link rel="stylesheet" href="calendar/calendar.css">
            <tr>
              <td align="center" valign="top"><table width="985" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td colspan="3" align="left" valign="top" height="15"></td>
                    </tr>
                  <tr>
                    <td width="225" align="left" valign="top"><?php include("adminleft.php"); ?></td>
                    <td width="25" align="left" valign="top"  class="devider_bg" ><img src="images/devider.jpg" width="25" height="3" /></td>
                    <td width="735" align="left" valign="top"><table width="735" border="0" cellspacing="0" cellpadding="0">					
                      <tr>
                        <td height="26" align="left" valign="middle" bgcolor="#8f8f8f"><table width="725" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="17"><img src="images/list-icon_4.jpg" width="12" height="12" /></td>
                            <td width="708" class="white_bold_txt_12px uppercase_txt">admin Profile </td>
                          </tr>
                        </table></td>
                      </tr>
                      <tr>
                        <td align="left" valign="top" class="pannel_border"><table width="715" border="0" align="center" cellpadding="0" cellspacing="0">
                        <?php if(isset($msg)) { ?>
						 <tr>
                            <td height="25" align="center" class="body_txt_bold12px" style="color:#ff0000"><?php echo $msg;?></td>
                      </tr>
						  <?php } ?>
                          <tr>
                            <td height="10"></td>
                          </tr>
						  <?php
						  $rs = getadminusers($_SESSION['adminId']);
									if($rs)
									{
										$row = mysql_fetch_assoc($rs);
										extract($row);
									}
						  ?>
                          <tr>
                            <td>
							<form name="adminprofile" method="post" action="">
                            <input type="hidden" name="cusername" id="cusername" value="<?php echo $username;?>">
                            <input type="hidden" name="avail" id="avail" value="no">
							<input type="hidden" name="auid" value="<?php echo $_SESSION['adminId'];?>" />
							<table width="600" border="0" align="center" cellpadding="7" cellspacing="0">
                              <tr>
                                <td width="143" align="left" valign="middle" class="body_txt_bold12px">First Name </td>
                                <td width="3" align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td width="412" align="left" valign="middle"><input name="fname" type="text" class="admin_textbox" value="<?php echo $fname;?>" /></td>
                              </tr>
                              <tr>
                                <td align="left" valign="middle" class="body_txt_bold12px">Last Name </td>
                                <td align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td align="left" valign="middle"><input name="lname" type="text" class="admin_textbox" value="<?php echo $lname;?>"  /></td>
                              </tr>
                              <tr>
                                <td align="left" valign="middle" class="body_txt_bold12px">Username Name </td>
                                <td align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td align="left" valign="middle"><input name="username" id="username" type="hidden" class="admin_textbox" value="<?php echo $username;?>"  /><?php echo $username;?>
                               <!-- &nbsp;<img src="images/list-icon_2.jpg" width="3" height="5" /> <a href="javascript:checkavail()" class="forgotlink" > Check Availability</a> <spam id="txtavail"></spam>--></td>
                              </tr>
                              <tr>
                                <td align="left" valign="middle" class="body_txt_bold12px">Password</td>
                                <td align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td align="left" valign="middle"><input name="password" type="password" class="admin_textbox" value="<?php echo $password;?>"   /></td>
                              </tr> 
                              <tr>
                                <td align="left" valign="middle" class="body_txt_bold12px">Email</td>
                                <td align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td align="left" valign="middle"><input name="email" type="text" class="admin_textbox" value="<?php echo $email;?>"   /></td>
                              </tr>
                              <!--<tr>
                                <td align="left" valign="middle" class="body_txt_bold12px">Status</td>
                                <td align="left" valign="middle" class="body_txt_bold12px">:</td>
                                <td align="left" valign="middle">
                                <input name="status" type="radio" value="1" < ?php if($status == 1) { ?> checked="checked" < ?php } ?>  />&nbsp;Active&nbsp;<input name="status" type="radio" value="0" < ?php if($status == 0) { ?> checked="checked" < ?php } ?>     />&nbsp;Inactive</td>
                              </tr>  -->
                              <tr>
                                <td align="left" valign="middle" class="body_txt12px">&nbsp;</td>
                                <td align="left" valign="middle" class="body_txt12px">&nbsp;</td>
                                <td align="left" valign="middle"><input name="Submit" type="submit" class="button_bg" value="Update" onclick="return validate()" /></td>
                              </tr>

                            </table>
							</form>
							</td>
                          </tr>
                          <tr>
                           
                            <td height="10"></td>
                          </tr>
                        </table></td>
                      </tr>
                    </table></td>
                  </tr>
                  <tr>
                    <td colspan="3" align="left" valign="top" height="15"></td>
                    </tr>
                  
              </table></td>
            </tr>
            
        </table></td>
      </tr>
    </table></td>
  </tr>
<?php include("footer.php"); ?>
