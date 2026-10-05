<?php include_once("header.php");?>
            <tr>
              <td align="center" valign="top"><table width="1003" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td height="70">&nbsp;</td>
                  </tr>
                  <tr>
                    <td align="center" valign="middle"><table width="482" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td align="center" valign="top"><table width="482" height="218" border="0" cellpadding="0" cellspacing="0" class="border1px">
                              <tr>
                                <td align="left" valign="top" class="login_box_bg"><table width="96%" border="0" align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                      <td height="48"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                                          <tr>
                                            <td width="4%"><img src="images/list-icon.jpg" width="14" height="14" /></td>
                                            <td width="96%" class="red_heading_txt_12px uppercase_txt">Admin <span class="black_heading_txt_12px">Login</span> </td>
                                          </tr>
                                      </table></td>
                                    </tr>
									<tr height="20"><td align="center">
										<b><?php if(isset($_REQUEST['msg']) && $_REQUEST['msg']=='I') echo "Invalid User/Password"; ?></b>&nbsp;
									</td></tr>
                                    <tr>
                                      <td valign="top">
										<form name="admloginform" action="validateuser.php" method="post">
									  <table width="80%" border="0" align="center" cellpadding="7" cellspacing="0">
                                          <tr>
                                            <td width="26%" align="left" valign="middle" class="black_bold_txt_12px">User Name </td>
                                            <td width="74%" align="left" valign="middle"><input name="user" type="text" class="textbox" /></td>
                                          </tr>
                                          <tr>
                                            <td align="left" valign="middle" class="black_bold_txt_12px">Password</td>
                                            <td align="left" valign="middle"><input type="password" name="pwd" class="textbox" /></td>
                                          </tr>
                                          <tr>
                                            <td align="left" valign="top">&nbsp;</td>
                                            <td align="left" valign="top"><input name="Submit" type="submit" class="button_bg" value="Login" /></td>
                                          </tr>
                                         <!-- <tr>
                                            <td align="left" valign="top">&nbsp;</td>
                                            <td align="left" valign="middle"><img src="images/list-icon_2.jpg" width="3" height="5" /> <a href="forgotpassword.php" class="forgotlink">Forgot Password ?</a> </td>
                                          </tr> -->
                                      </table>
									  </form></td>
                                    </tr>
                                </table></td>
                              </tr>
                          </table></td>
                        </tr>
                        <tr>
                          <td><table width="482" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td width="241" align="left" valign="top"><img src="images/login-box-shadow-left.jpg" width="178" height="8" /></td>
                                <td width="241" align="right" valign="top"><img src="images/login-box-shadow-right.jpg" width="178" height="8" /></td>
                              </tr>
                          </table></td>
                        </tr>
                    </table></td>
                  </tr>
                  <tr>
                    <td height="70">&nbsp;</td>
                  </tr>
              </table></td>
            </tr>
            
        </table></td>
      </tr>
    </table></td>
  </tr>
<?php include("footer.php");?>