<?php
include("header.php");
include("process.php");
///params for pagination
if( isset($_GET['start']) ){
$start = $_GET['start'];
}else{
$start = 0;
}
$q_limit = 25;
$filePath = 'issuesyears.php'; 
///params for pagination

if(isset($_POST['add_edit']))
{
extract($_POST);
if(isset($act))
{
if($catid!='')
{
$catid=$catid;
}
else
{
$catid='';
}
if(manageajsmr_issueyears($catename,$eventdate,$status,$catid)) {
/*//$msg = "Error in insertion";
} else {*/
$msg = "Category Updated Successfully";
}
echo "<script type='text/javascript'>window.location.href='issuesyears.php?msg=$msg'</script>";
}
}
//function for delete
if(isset($_REQUEST["del"]))
{
$courseid=$_REQUEST["del"];
$delete="delete from ajsmr_issueyears WHERE catid='".$_REQUEST["del"]."'";
$delete_query=mysql_query($delete);
$msg ='Category Deleted Successfully';
echo "<script type='text/javascript'>window.location.href='issuesyears.php?msg=$msg'</script>";
}
?>

<html>
<head>
<title>Welcome to ajsmrjournal.com :: Issue Years</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" src="common/manageevents.js"></script>
<script language="JavaScript" src="../js/validation.js"></script>
<script language="JavaScript" src="calendar/ts_picker.js"></script>
<script language="javascript1.1">
function delete_record(catid)
{
if(confirm("Are you sure to delete this Event?"))
{
document.location.href='issuesyears.php?del='+catid;
}
}
</script>
</head>
<body topmargin="0" leftmargin="0" rightmargin="0">
<table width="100%"  border="0" cellspacing="0" cellpadding="0">
<tr align="center">
<td colspan="2">

<table width="985" align="center" border="0" cellspacing="0" cellpadding="0">
<tr>
<td colspan="3" align="left" valign="top" height="15"></td>
</tr>
<tr>
<td width="225" height="42" valign="top"><?php include("adminleft.php"); ?></td>
<td width="25" align="left" valign="top"  class="devider_bg" ><img src="images/devider.jpg" width="25" height="3" /></td>
<td width="735" align="left" valign="top">
<table width="735"  border="0" cellpadding="0" cellspacing="0"  class="pannel_border" >
<tr>
<td height="26" align="left" bgcolor="#8f8f8f">
<table width="99%"  border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
<td width="30%"  class="white_bold_txt_12px uppercase_txt">
<img src="images/list-icon_4.jpg" width="12" height="12" hspace="5" />Year and Isues</td>
<td width="60%" align="right"><img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0" /><a href="issuesyears.php?Call=edit" class="add_link">Year and Issues </a></td>
</tr>
</table>
</td>
</tr>
<tr>
<td>&nbsp;&nbsp;&nbsp;</td>
<td>&nbsp;&nbsp;&nbsp;</td>
</tr>
<tr>
<td height="26" align="left" bgcolor="#8f8f8f"><table width="99%"  border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
<td width="26%"  class="white_bold_txt_12px uppercase_txt">&nbsp;</td>
<td width="74%" align="right"><img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0"><a href="issues_content.php" class="add_link">Manage Content</a><!--&nbsp;&nbsp;<img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0"><a href="#" class="add_link">Naku Nachina</a>--></td>
</tr>
</table></td>
</tr>
<tr><td align="center" valign="top" bgcolor="#FFFFFF">
<!--table1 start-->
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td height="10"></td>
</tr>
<?php 
$msg = $_REQUEST['msg'];
if(isset($msg)) {
?>
<tr>
<td height="25" align="center" class="body_txt_bold12px" style="color:#ff0000">
<?php echo $msg;?>
</td>
</tr>
<?php } ?>
<?php
//on opening the page this will display
if((!isset($_GET['catid']))&&(!isset($_GET['Call'])) ) {
?>
<tr>
<td><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" >
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1" class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6" /> Year and Issues List </td>
</tr>
<tr>
<td><table width="100%" border="0" cellspacing="1" cellpadding="4" align=center  bgcolor="#A3A3A3">
<TD width="10%" align="center" bgcolor="#CCCCCC" class="body_txt_bold12px">S.No</TD>
<td  width="38%" align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Year and Issue</td>
<!--<td  width="26%" align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Publish Date</td>-->
<!--<td  width="9%" align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Status</td>-->
<td  width="26%" align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Action</td>
</tr>
<?php
$sql = mysql_query("select * from ajsmr_issueyears order by eventdate desc");
$no_rows= mysql_num_rows($sql);
//echo "select * from courses limit $start, $limit";exit;
$rsl = mysql_query("select * from ajsmr_issueyears order by eventdate desc limit $start, $q_limit");
$i=$start;
$bgcolor=0;
while($res=mysql_fetch_array($rsl))
{
extract($res);
if($bgcolor=="#FFFFFF") $bgcolor="#F7F7F7";
else $bgcolor="#FFFFFF";
$i++;
?>
<tr bgcolor = "<?php echo $bgcolor;?>">
<TD align="center" valign="middle" class="block_txt12px"><?php echo $i;?></TD>
<TD align="center" valign="middle" class="block_txt12px"><?php echo $catename;?></TD>
<!--<TD align="center" valign="middle" class="block_txt12px"><?php echo $eventdate;?></TD>-->
<!--<TD align="center" valign="middle" class="block_txt12px">< ?php if($status ==1) {echo "Active";} else { echo "Inactive";} ?></TD>-->
<td class="gray_txt" width="26%" valign="middle" align="center"><a href="issuesyears.php?Call=edit&catid=<?php echo $res['catid']?>" class="edit_link">Edit</a>
&nbsp;| &nbsp;<a href="javascript:delete_record('<?php echo $res['catid']?>')" class="edit_link">Delete</a>	</td>
</tr>
<?php  } ?>

<?php if($i==0){ ?>
<tr align="center"  bgcolor="#FFFFFF">
<td height="27" colspan="8" class="gray_txt"><?php print "Records are not Available." ?></td>
</tr>
<?php } ?>
<tr align="center"  bgcolor="#FFFFFF">
<td colspan="8" class="gray_txt" align="right"><?php
//pagination file in config.inc.php 
paginate($start,$q_limit,$no_rows,$filePath,"");?></td>
</tr>
</form>
<!--table1 ends-->
</table></td>
</tr>
</table></td>
</tr>
<?php } else {?>
<tr>
<td>&nbsp;</td>
</tr>
<tr>
<td><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:1px solid #A3A3A3;">
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1"   class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6" /> <?php if(isset($_REQUEST['catid'])){ ?>Edit<?php } else { ?>Add<?php } ?> Year and Issue</td>
</tr>
<tr>
<td><table width="90%" border="0" align="center" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF" >
<form name="events" action="" method="post" enctype="multipart/form-data" onSubmit="return validate(this);">
<input type="hidden" name="act" value="edit">
<input type="hidden" name="catid" value="<?php echo $_REQUEST['catid'];?>">
<?php
if(isset($_REQUEST['catid']))
{
$rs = getajsmr_issueyears($_REQUEST['catid']);
}
if($rs)
{
$row = mysql_fetch_assoc($rs);
extract($row);
}
?>
<tr>
<td height="10" colspan="2" align="left" > </td>
</tr>
<tr>
<td width="30%" height="30" align="left" class="body_txt_bold12px">Year and Issue<font color="#FF0000">*</font>&nbsp;:  </td>
<td width="70%" align="left"><input name="catename" type="text" class="admin_textbox" id="event" size="30" value="<?php print($catename);?>" />                                    </td>
</tr>
<input type="hidden" name="status" value="1">
<!--<tr>
<td width="30%" height="30" align="left" class="body_txt_bold12px"> Status<font color="#FF0000">*</font>:  </td>
<td width="70%" align="left"><input name="status" type="radio"  value="0"< ?php if($status=='0'){echo 'checked';}?> />
Inactive
<input name="status" type="radio"  value="1"< ?php if($status=='1'){echo 'checked';}?> />
Active </td>
</tr>-->
<tr>
<td align="right" bgcolor="#FFFFFF" height="40"></td>
<td width="70%" align="left" bgcolor="#FFFFFF"><input type="submit" name="add_edit" value="Save Data"  class="button_bg"  />&nbsp;&nbsp;
<input type="reset" value="Reset" class="button_bg"  />&nbsp;&nbsp;
<input type="button" name="back" value="Back" class="button_bg"  onclick="javascript:history.back();" />                                    </td>
</tr>
</form>
</table></td>
</tr>
</table></td>
</tr>
<?php } ?>
<tr>
<td>&nbsp;</td>
</tr>
</table></td>
</tr></table>
<!--Edit or add form Table Begins-->
<!--Table ends-->    </td>
</tr>
</table>
<br>
<br>
</td>
</tr>
<?php include("footer.php"); ?>