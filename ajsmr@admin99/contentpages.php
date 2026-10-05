<?php
	include("header.php");
	include("process.php");
    include("../fckeditor/fckeditor.php") ;
	header('Content-type: text/html; charset=utf-8');
		/**** Pagination ********/
	$q_limit =25;
	if( isset($_GET['start']) )
	{
		$start = $_GET['start'];
	}
	else
	{
		$start = 0;
	}
	$filePath = "contentpages.php";
	$otherParams='';
	/******** Pagination end *******/	
	if(isset($_POST['add_edit']))
	{
		//print_r($_POST);
		extract($_POST);
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath']['name']))
			{
			$photopath = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath']['name'];
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
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath2']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath2']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath2']['name']))
			{
			$photopath2 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath2']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath2=$dbpic2;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath3']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath3']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath3']['name']))
			{
			$photopath3 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath3']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath3=$dbpic3;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath4']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath4']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath4']['name']))
			{
			$photopath4 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath4']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath4=$dbpic4;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath5']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath5']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath5']['name']))
			{
			$photopath5 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath5']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath5=$dbpic5;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath6']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath6']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath6']['name']))
			{
			$photopath6 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath6']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath6=$dbpic6;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath7']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath7']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath7']['name']))
			{
			$photopath7 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath7']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath7=$dbpic7;
		}
		
		//////////////////////////////////////////////////////////
		
		//////////////////////////////////Image Uploading//////////
		if($_FILES['photopath8']['name']!="")
		{
			if(move_uploaded_file($_FILES['photopath8']['tmp_name'],'../latestnewspics/news'.date("His").'_'.$_FILES['photopath8']['name']))
			{
			$photopath8 = '../latestnewspics/news'.date("His").'_'.$_FILES['photopath8']['name'];
			}
			else
			{
			echo "error";
			}
		}
		else
		{
			$photopath8=$dbpic8;
		}
		
		//////////////////////////////////////////////////////////
		
		   if(isset($act))
		{
			if(managecontentpages($title,$dailytitle,$description,$photopath,$photopath2,$photopath3,$photopath4,$photopath5,$photopath6,$photopath7,$photopath8,$publishdate,$conid))
			{
				/*$msg = "Error in insertion";
				echo "<script type='text/javascript'>window.location.href='contentpages.php'</script>";
			}
			else
			{*/
				$msg = "Content Updated Successfully";
				/*echo "<script type='text/javascript'>window.location.href='contentpages.php?msg=$msg'</script>";*/
			}
		}
		else
		{
			//checking if the username is existing or not
			if(mysql_num_rows(mysql_query("select * from contentpages where title='$title'"))==0)
			{
				if(managecontentpages($title,$dailytitle,$description,$photopath,$photopath2,$photopath3,$photopath4,$photopath5,$photopath6,$photopath7,$photopath8,$publishdate,$conid =''))
				{
				    $msg = "Content  Added Successfully";
					/*echo "<script type='text/javascript'>window.location.href='contentpages.php?msg=$msg'</script>";*/
				}
			}
			else
			{
				$msg = 'Title Already Exists';
			}
			//header("location:notifications.php");
		}
		echo "<script type='text/javascript'>window.location.href='contentpages.php?msg=$msg'</script>";
	}
	if(isset($_REQUEST["del"]))
	{
		$delete="delete from contentpages WHERE conid ='".$_REQUEST["del"]."'";
		$delete_query=mysql_query($delete);
		$msg ='Content Deleted Successfully';
		echo "<script type='text/javascript'>window.location.href='contentpages.php?msg=$msg'</script>";
	}
?>
<html>
<head>
<title>Welcome to ajsmrjournal.com :: Content Pages</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" src="common/content_pages.js"></script>
<script language="JavaScript" src="js/validation.js"></script>
<script language="JavaScript" src="calendar/ts_picker.js"></script>
<script type="text/ecmascript">
function validate()
{
if(document.news.title.value == "")
{
alert("Please enter title");
document.news.title.focus();
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
function delete_record(conid,conid)
{
	if(confirm("Are you sure to delete this One?"))
	{
		document.location.href='contentpages.php?del='+conid+'&conid='+conid;
	}
}
</script>

<!-- Google Translate Start -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<!-- Google Translate End -->
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
					<img src="images/list-icon_4.jpg" width="12" height="12" hspace="5">
						All Content </td>
					<td width="18%" align="right"> <img src="images/list_icon.gif" width="3" height="7" hspace="5" border="0"><a href="contentpages.php?Call=edit" class="add_link">Content</a></td>
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
	if((!isset($_REQUEST['conid ']))&&(!isset($_REQUEST['Call'])) ) {?>
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
<td><table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" >
<tr>
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1" class="white_bold_txt_11px uppercase_txt padding_left5px"> All <span class="white_bold_txt_12px uppercase_txt">Content</span> List </td>
</tr>
		<tr>
		<td><table width="100%" border="0" cellspacing="1" cellpadding="6" align=center  bgcolor="#A3A3A3">
		<td width="6%"  align="center" bgcolor="#CCCCCC" class="body_txt_bold12px">S.No</td>
		<td width="27%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px">  Title</td>
		<td width="29%"  align="center" bgcolor="#CCCCCC"  class="body_txt_bold12px"> Content </td>
		<td   align="center" bgcolor="#CCCCCC"   class="body_txt_bold12px" >Action</td>
		</tr>
          <?php
		 $sql = mysql_query("select * from contentpages order by conid ASC");
		  $no_rows= mysql_num_rows($sql);
		  $rsl = mysql_query("select * from contentpages order by conid ASC limit $start, $q_limit");
		  $i=0;
		  $bgcolor=0;
		  while($res=mysql_fetch_array($rsl))
		  {
		  extract($res);
		  if($bgcolor=="#FFFFFF") $bgcolor="#F7F7F7";
		  else $bgcolor="#FFFFFF";
		  $i++;
		  ?>
  <tr bgcolor = "#FFFFFF" >
	<TD align="center" valign="top" class="block_txt12px"><?php echo $i;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo $title;?></TD>
	<TD align="center" valign="top" class="block_txt12px"><?php echo strip_tags(substr($description,0,450))."...";?></TD>
	<td class="gray_txt" width="13%" valign="top" align="center"><a href="contentpages.php?Call=edit&conid=<?php echo $res['conid']?>" class="edit_link">Edit/</a>
	<a href="javascript:delete_record(<?php echo $res['conid'];?>,<?php echo $res['conid'];?>)" class="edit_link">Delete</a></td>
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
		paginate($start,$q_limit,$no_rows,$filePath,$otherParams);?></td>
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
<td height="24" align="left" valign="middle" bgcolor="#B1B1B1"   class="white_bold_txt_11px uppercase_txt padding_left5px"><img src="images/list_icon_5.jpg" width="3" height="6"> <?php 
	if(isset($_REQUEST['conid']))
   {
   		echo "Edit";
	}
	else
		echo "Add";
?>   All <span class="white_bold_txt_12px uppercase_txt">Content</span> </td>
</tr>
<tr>
<td><table width="80%" border="0" align="center" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF" >
<form name="news"  method="post" action="" onSubmit="return validate();" enctype="multipart/form-data">
 <?php
 if(isset($_REQUEST['conid']))
   {
  ?>
  <input type="hidden" name="act" value="edit">
   <input type="hidden" name="title" value="edit">
  <input type="hidden" name="conid" value="<?php echo $_REQUEST['conid'];?>">						   
  <?php								
  $rs = getcontentpages($_REQUEST['conid']);
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

<td width="29%" height="30" align="left" >Title<font color="#FF0000">* </font>  </td>

<td width="71%" align="left"> <input name="title" id="title" type="text" class="admin_textbox" value="<?php echo $title;?>" /><!--<div id='translControl' style="float:right;width:60px;"></div>--></td>

<!--</tr>
<td width="29%" height="30" align="left" >Subtitle Title<font color="#FF0000">* </font>  </td>

<td width="71%" align="left"> <input name="dailytitle" id="dailytitle" type="text" class="admin_textbox" value="< ?php echo $dailytitle;?>" /></td>

</tr>-->

 
  <tr>
						<td colspan="2" height="30" align="left" valign="top" >Full Description<font color="#FF0000">*</font> </td>
					</tr>
					<tr>    
						<td colspan="2" align="left">
<?php 	 					$sBasePath = 'fckeditor/' ;
							$oFCKeditor = new FCKeditor('description') ;
							$oFCKeditor->BasePath	= $sBasePath ;
							$oFCKeditor->Value		= '' ;
							if(isset($description))
							$oFCKeditor->Value	= stripslashes($description);
							$oFCKeditor->Width  = '600' ;
							$oFCKeditor->Height = '350' ;	
							$oFCKeditor->Create();
?>					
</td>
					</tr>
					
	<td align="right" bgcolor="#FFFFFF" height="40"></td>
	<td width="70%" align="left" bgcolor="#FFFFFF"><input type="submit" name="add_edit" value="Save Data"  class="button_bg" />&nbsp;&nbsp;&nbsp;&nbsp;
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
</table></td>
                      </tr>
                      <tr>
                        <td height="70">&nbsp;</td>
                      </tr>
                  </table></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                </tr>
              </table>
</td>
</tr>
<?php include("footer.php"); ?>	