<?php
include("header.php");
include("process.php");
 include("fckeditor/fckeditor.php") ;
///params for pagination
if( isset($_GET['start']) ){
	$start = $_GET['start'];
}else{
	$start = 0;
}
$q_limit = 15;
$filePath = 'templatesandcopyrightforms.php';
///params for pagination

if(isset($_POST['add_edit']))
{
extract($_POST);

//////////////////////////////////Abstract pdf Uploading//////////
		if($_FILES['abstract']['name']!="")
		{
			if(move_uploaded_file($_FILES['abstract']['tmp_name'],'../copyrightandmanutemp/cimg'.date("His").'_'.$_FILES['abstract']['name']))
			{
			$abstract = '../copyrightandmanutemp/cimg'.date("His").'_'.$_FILES['abstract']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$abstract=$abstracted;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Full paper Uploading//////////
		if($_FILES['fullpaper']['name']!="")
		{
			if(move_uploaded_file($_FILES['fullpaper']['tmp_name'],'../copyrightandmanutemp/cimg'.date("His").'_'.$_FILES['fullpaper']['name']))
			{
			$fullpaper = '../copyrightandmanutemp/cimg'.date("His").'_'.$_FILES['fullpaper']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$fullpaper=$fullpapered;
		}
		
		//////////////////////////////////////////////////////////
//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath']['tmp_name'],'../issuesimgs/cimg'.date("His").'_'.$_FILES['photopath']['name']))
			{
			$photopath = '../issuesimgs/cimg'.date("His").'_'.$_FILES['photopath']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath=$dbpic;
		}
		
		//////////////////////////////////////////////////////////
if(isset($act))
{
	if($contentid!='')
	{
	$contentid=$contentid;
	}
	else
	{
	$contentid='';
	}
if(managetemplatesandcopyrights($catid,$type,$conttitle,$authors,$journal,$recevied,$accepted,$published,$doi,$abstract,$fullpaper,$photopath,$status,$contentid)) {
	/*	//$msg = "Error in insertion";
	} else {*/
		$msg = "Content Updated Successfully";
	}
	echo "<script type='text/javascript'>window.location.href='templatesandcopyrightforms.php?msg=$msg'</script>";
}
}
//function for delete
if(isset($_REQUEST["del"]))
{
 $delete="delete from templatesandcopyrights WHERE contentid='".$_REQUEST["del"]."'";
 $delete_query=mysql_query($delete);
 $msg ='Content Deleted Successfully';
 echo "<script type='text/javascript'>window.location.href='templatesandcopyrightforms.php?msg=$msg'</script>";
 }
?>

<html><head>
<title>Welcome to ijraonline.com ::Template and Copyright Form Docs</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!--<script language="JavaScript" src="calendar/calendar_db.js"></script>-->
<script language="JavaScript" src="common/manageprograms.js"></script>
<script language="JavaScript" src="../js/validation.js"></script>
<!--<link rel="stylesheet" href="calendar/calendar.css">-->
<script language="JavaScript" src="calendar/ts_picker.js"></script>
<script language="javascript1.1">
//alert message for delete
function delete_record(contentid)
{
	if(confirm("Are you sure to delete this Program?"))
	{
		document.location.href='templatesandcopyrightforms.php?del='+contentid;
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
					<img src="images/list-icon_4.jpg" width="12" height="12" hspace="5" />Templates and Copyright Docs </td>
				    <!--<td width="60%" align="right"><img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0" /><a href="templatesandcopyrightforms.php?Call=edit" class="add_link">New Content </a></td>-->
				</tr>
			</table>
	  </td>
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
		if((!isset($_GET['contentid']))&&(!isset($_GET['Call'])) ) {
	?>
<tr>
<td><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" >
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1" class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6" /> Template and Copyright Docs </td>
</tr>
<tr>
<td><table width="100%" border="0" cellspacing="1" cellpadding="4" align=center  bgcolor="#A3A3A3">
  <TD width="8%" align="center" bgcolor="#CCCCCC" class="body_txt_bold12px">S.No</TD>
<td  width="29%" align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Title</td>
<td  width="21%" align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Manuscript Template</td>
<td  width="21%" align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Author Copyright Docs </td>
<!--<td  width="9%" align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Status</td>-->
	<td  width="21%" align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Action</td>
  </tr>
		 <?php
		$sql = mysql_query("select * from templatesandcopyrights order by contentid desc");
		$no_rows= mysql_num_rows($sql);
		//echo "select * from courses limit $start, $limit";exit;
		$rsl = mysql_query("select * from templatesandcopyrights order by contentid desc limit $start, $q_limit");
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
	<TD align="center" valign="middle" class="block_txt12px"><?php echo $conttitle;?></TD>
    <TD align="center" valign="middle" class="block_txt12px"><a href="../copyrightandmanutemp/<?php echo $abstract;?>" target="_blank"><font color="#FF0000">Manuscript Template</a></TD>
	<TD align="center" valign="top" class="block_txt12px"><a href="../copyrightandmanutemp/<?php echo $fullpaper;?>" target="_blank"><font color="#FF0000">Author Copyright Form</a></TD>
	
	<!--<TD align="center" valign="top" class="block_txt12px"><img src="< ?php echo $photopath;?>" width="100" height="80"></TD>-->
	<!--<TD align="center" valign="middle" class="block_txt12px">< ?php if($status ==1) {echo "Active";} else { echo "Inactive";} ?></TD>-->
	<td class="gray_txt" width="21%" valign="middle" align="center"><a href="templatesandcopyrightforms.php?Call=edit&contentid=<?php echo $res['contentid']?>" class="edit_link">Edit</a>
	&nbsp;| &nbsp;<a href="javascript:delete_record('<?php echo $res['contentid']?>')" class="edit_link">Delete</a>	</td>
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
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1"   class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6" /> <?php if(isset($_REQUEST['contentid'])){ ?>Edit<?php } else { ?>Add<?php } ?>Template and Copyrights </td>
</tr>
<tr>
<td><table width="90%" border="0" align="center" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF" >
<form name="programs" action="" method="post" enctype="multipart/form-data" onSubmit="return validate(this);">
<input type="hidden" name="act" value="edit">

<input type="hidden" name="contentid" value="<?php echo $_REQUEST['contentid'];?>">
<?php
if(isset($_REQUEST['contentid']))
{
 $rs = gettemplatesandcopyrights($_REQUEST['contentid']);
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
  
  <input type="hidden" name="catid" value="1">
  <tr>
	<td width="30%" height="30" align="left" class="body_txt_bold12px">Title<font color="#FF0000">*</font>&nbsp;:  </td>
	<td width="70%" align="left"><input name="conttitle" type="text" class="admin_textbox" id="conttitle" size="30" value="<?php print($conttitle);?>" />                                    </td>
  </tr>
  
  
  <tr>
    <td height="30" align="left" class="body_txt_bold12px">Manuscript Template : </td>
    <td align="left"><input name="abstract" type="file"  id="abstract" size="30" />
    <input type="hidden" name="abstracted" value="<?php echo $abstract?>"><br>&nbsp;<?php
		if(!empty($abstract))
          echo "<img src='../copyrightandmanutemp/".$abstract."' width='100' height='80'/>";
	   ?></td>
  </tr>
  <tr>
    <td height="30" align="left" class="body_txt_bold12px">Author Copyright Form: </td>
    <td align="left"><input name="fullpaper" type="file"  id="fullpaper" size="30" />
    <input type="hidden" name="fullpapered" value="<?php echo $fullpaper?>"><br>&nbsp;<?php
		if(!empty($fullpaper))
          echo "<img src='../copyrightandmanutemp/".$fullpaper."' width='100' height='80'/>";
	   ?></td>
  </tr>

					
  <input type="hidden" name="status" value="1">
 <!-- <tr>
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