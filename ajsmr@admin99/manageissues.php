<?php include("header.php");
include("process.php");
?>
<script language="JavaScript" src="common/studycenters.js"></script>
            <tr>
              <td align="center" valign="top"><table width="985" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td colspan="3" align="left" valign="top" height="15"></td>
                    </tr>
                  <tr>
                    <td width="225" align="left" valign="top"><?php include("adminleft.php"); ?></td>
                    <td width="25" align="left" valign="top"  class="devider_bg" ><img src="images/devider.jpg" width="25" height="3" /></td>
                    <td width="735" align="left" valign="top"><table width="735" border="0" cellspacing="0" cellpadding="0" height="100%">
					<?php if(isset($msg)) { ?>
						 <tr>
                            <td width="17" align="center"><?php echo $msg;?></td>
                          </tr>
						  <?php } ?>
                      <tr>
                        <td height="26" align="left" valign="middle" bgcolor="#8f8f8f"><table width="725" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="17"><img src="images/list-icon_4.jpg" width="12" height="12" /></td>
                            <td width="708" class="white_bold_txt_12px uppercase_txt">Issues</td>
                          </tr>
                        </table></td>
                      </tr>
                      <tr>
                        <td align="left" valign="top" class="pannel_border">
							<table width="715" border="0" align="center" cellpadding="0" cellspacing="0">
							  <tr><td height="20"></td>
							  </tr>
							  <tr>
							    <td><table width="97%" border="0" align="center" cellpadding="10" cellspacing="0" class="inner_table_border">
                                  <tr>
                                    <td width="3%" align="left" valign="middle"><img src="images/list_icon_7.jpg" width="14" height="14" /></td>
                                    <td width="97%" align="left" valign="middle" class="innerlinks">
                                    <a href="issuesyears.php">Volume / Issue / Years</a></td>
                                  </tr>
                                  <tr align="center">
                                    <td align="left" valign="middle"><img src="images/list_icon_7.jpg" width="14" height="14" /></td>
                                    <td align="left" valign="middle" class="innerlinks">
                                    <a href="issues_content.php">Issue Content</a></td>
                                  </tr>
								   <tr align="center">
                                    <td align="left" valign="middle"><img src="images/list_icon_7.jpg" width="14" height="14" /></td>
                                    <td align="left" valign="middle" class="innerlinks">
                                    <a href="manageabstracts.php">Manage Abstracts</a></td>
                                  </tr>
                                 
								   
                                  
                                </table></td>
						      </tr>
							  <tr>
								<td height="20">&nbsp;</td>
							  </tr>
							   
							</table>
						</td>
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
