<?php
	include("header.php");
	include("process.php");
		/**** Pagination ********/
	$q_limit =10;
	if( isset($_GET['start']) )
	{
		$start = $_GET['start'];
	}
	else
	{
		$start = 0;
	}
	$filePath = "recivedsubmitmanuscripts.php";
	$otherParams='';
	/******** Pagination end *******/	
	if(isset($_POST['add_edit']))
	{
		//print_r($_POST);
		extract($_POST);
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath']['tmp_name'],'../submitmanusript/cimg'.date("His").'_'.$_FILES['photopath']['name']))
			{
			$photopath = '../submitmanusript/cimg'.date("His").'_'.$_FILES['photopath']['name'];
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
			if(managesubmitmanuscripts($manuscripttitle,$authorname,$coauthorname,$noofauthors,$authoraffiliation,$cname,$cauthemail,$substeam,$filepath,$filepath2))
			{
				/*$msg = "Error in insertion";
				echo "<script type='text/javascript'>window.location.href='recivedsubmitmanuscripts.php'</script>";
			}
			else
			{*/
				$msg = "Submitmanu Sacripts Updated Successfully";
				/*echo "<script type='text/javascript'>window.location.href='recivedsubmitmanuscripts.php?msg=$msg'</script>";*/
			}
		}
		else
		{
			//checking if the username is existing or not
			if(mysql_num_rows(mysql_query("select * from editorialboards where name='$name'"))==0)
			{
				if(managesubmitmanuscripts($manuscripttitle,$authorname,$coauthorname,$noofauthors,$authoraffiliation,$cname,$cauthemail,$substeam,$filepath,$filepath2,$suid =''))
				{
				    $msg = "Submitmanu Sacripts Added Successfully";
					/*echo "<script type='text/javascript'>window.location.href='recivedsubmitmanuscripts.php?msg=$msg'</script>";*/
				}
			}
			else
			{
				$msg = 'Title Already Exists';
			}
			//header("location:notifications.php");
		}
		echo "<script type='text/javascript'>window.location.href='recivedsubmitmanuscripts.php?msg=$msg'</script>";
	}
	if(isset($_REQUEST["del"]))
	{
		$delete="delete from submitmanuscripts WHERE suid ='".$_REQUEST["del"]."'";
		$delete_query=mysql_query($delete);
		$msg ='Submitmanu Sacripts Deleted Successfully';
		echo "<script type='text/javascript'>window.location.href='recivedsubmitmanuscripts.php?msg=$msg'</script>";
	}
?>
<html>
<head>
<title>IjraOnline.com</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" src="common/content_pages.js"></script>
<script language="JavaScript" src="js/validation.js"></script>
<script language="JavaScript" src="calendar/ts_picker.js"></script>
<script type="text/ecmascript">
function validate()
{
if(document.news.title.value == "")
{
alert("Please enter name");
document.news.name.focus();
return false;
}
var oEditor = FCKeditorAPI.GetInstance('description') ;
	var descr = oEditor.GetXHTML(true) ;
	
	if(!descr)
	{
		alert("Please enter description");
		return false;
	}
}
</script>
<script language="javascript1.1">
//alert message for delete
function delete_record(suid,suid)
{
	if(confirm("Are you sure to delete this One?"))
	{
		document.location.href='recivedsubmitmanuscripts.php?del='+suid+'&suid='+suid;
	}
}
</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" href="calendar/calendar.css">
</head>
<body topmargin="0" leftmargin="0" rightmargin="0">
<table width="100%"  border="0" cellspacing="0" cellpadding="0">
<tr align="center">
<td colspan="2">
<table width="1000" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="body_content_bg"><table width="1000" border="0" align="center" cellpadding="0" cellspacing="0">
                      <tr>
                        <td height="20">&nbsp;</td>
                      </tr>
                      <tr>
                        <td align="center" valign="middle"><table width="985" align="center" border="0" cellspacing="0" cellpadding="0">
 
    <tr>
    <td width="225" height="42" valign="top"><?php include("adminleft.php"); ?></td>
   <td width="25" align="left" valign="top"  class="devider_bg" ><img src="images/devider.jpg" width="25" height="3" /></td>
    <td width="735" align="left" valign="top">
	<table width="735"  border="0" cellpadding="0" cellspacing="0"  class="pannel_border" >
	<tr>
		<td height="26" align="left" bgcolor="#8f8f8f">
			<table width="99%"  border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td width="50%"  class="white_bold_txt_12px uppercase_txt">
					<img src="images/list-icon_4.jpg" width="12" height="12" hspace="5">Recived Submitmanu Sacripts List </td>
					<!--<td width="18%" align="right"> <img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0"><a href="recivedsubmitmanuscripts.php?Call=edit" class="add_link">New One</a></td>-->
				</tr>
			</table>
	  </td>
	</tr>
	<tr><td align="center" valign="top" bgcolor="#FFFFFF">
<!--table1 start-->
<table width="720" border="0" cellspacing="0" cellpadding="0">
<tr>
<td height="10"></td>
</tr>
	<?php 
	//on opening the page this will display
	if((!isset($_REQUEST['suid']))&&(!isset($_REQUEST['Call'])) ) {?>
    <?php 
 $msg = $_REQUEST['msg'];
 if(isset($msg)) { ?>
		<tr>
           <td height="25" align="center" class="body_txt_bold12px" style="color:#ff0000">
			   <?php echo $msg;?>
           </td>
        </tr>
<?php } ?>
<tr>
<td><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" >
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1" class="white_bold_txt_11px uppercase_txt padding_left5px">Submitmanu Sacripts</td>
</tr>
		<tr>
		<td><table width="100%" border="0" cellspacing="1" cellpadding="6" align=center  bgcolor="#A3A3A3">
		<td width="6%"  align="center" bgcolor="#CCCCCC" class="body_txt_bold12px">S.No</td>
		<td width="27%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Manu Sacripts Title</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Author Name</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Co-Authorname</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">No of Authors</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Author Affiliation</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Country Name</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Eamil</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">SSteam</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Download (Doc)</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">Download (Doc)</td>
		<td   align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Action</td>
		</tr>
         <?php
          $i=0;
		  $suid =$_REQUEST['suid'];
		  $rs = getsubmitmanuscripts();
		  if($rs)
		  {
		  $no_rows= mysql_num_rows($rs);
		  $rs2 = getsubmitmanuscripts($suid,$start,$q_limit);
		  if(mysql_num_rows($rs2) > 0)	
		  {	
		  
		  while($res1=mysql_fetch_array($rs2))
		  {
		 //print_r($res1);
		  extract($res1);
		  //echo "hello";
		  if($bgcolor=="#FFFFFF") $bgcolor="#F7F7F7";
		  else $bgcolor="#FFFFFF";
		  $i++;				
		?>
  <tr bgcolor = "#FFFFFF" >
	<TD align="center" valign="top" class="block_txt12px"><?php echo $i;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $manuscripttitle;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $authorname;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $coauthorname;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $noofauthors;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $authoraffiliation;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $cname;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $cauthemail;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $substeam;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><a href="../<?php echo $filepath;?>" target="_blank">Download</a></TD>
	<TD align="center" valign="top" class="block_txt12px"><a href="../<?php echo $filepath2;?>" target="_blank">Download</a></TD>
	<!--<TD align="center" valign="top" class="block_txt12px">
	
	< ?php echo $publishdate=date("d M y", time());?>
	
	</TD>-->
	<td class="gray_txt" width="13%" valign="top" align="center"><!--<a href="recivedsubmitmanuscripts.php?Call=edit&suid=< ?php echo $res1['suid']?>" class="edit_link">Edit/</a>-->
	<a href="javascript:delete_record(<?php echo $res1['suid'];?>,<?php echo $res1['suid'];?>)" class="edit_link">Delete</a></td>
  </tr>
  <?php  } ?>
  <tr bgcolor="#F7F7F7">
	<td align="right" colspan="12">&nbsp;</td>
  </tr>
  <tr align="center"  bgcolor="#FFFFFF">
	<td colspan="12" class="gray_txt" align="right"><?php
		//pagination file in config.inc.php 
		paginate($start,$q_limit,$no_rows,$filePath,"");?></td>
  </tr>
  <?php  } 
  }
  else
  { ?>
  <tr align="center"  bgcolor="#FFFFFF">
	<td height="27" colspan="12" class="gray_txt"><?php print "Manu Scripts are not Available." ?></td>
  </tr>
  <?php } ?>
  </form>
  <!--table1 ends-->
</table></td>
</tr>
</table></td>
</tr>
<?php
}
else if(isset($_REQUEST['Call']))
{
?>
<tr>
<td>&nbsp;</td>
</tr>
<tr>
<td><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:1px solid #A3A3A3;">
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1"   class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6"> <?php 
	if(isset($_REQUEST['suid']))
   {
   		echo "Edit";
	}
	else
		echo "Add";
?> Submitmanu Sacripts</td>
</tr>
<tr>
<td><table width="80%" border="0" align="center" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF" >
<form name="news"  method="post" action="" onSubmit="return validate();" enctype="multipart/form-data">
 <?php
 if(isset($_REQUEST['suid']))
   {
  ?>
  <input type="hidden" name="act" value="edit">
   <input type="hidden" name="name" value="edit">
  <input type="hidden" name="suid" value="<?php echo $_REQUEST['suid'];?>">						   
  <?php								
  $rs = getsubmitmanuscripts($_REQUEST['suid']);
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
</table></td>
                      </tr>
                      <tr>
                        <td height="70">&nbsp;</td>
                      </tr>
                  </table></td>
                </tr>
               
              </table>
</td>
</tr>
<?php include("footer.php"); ?>	