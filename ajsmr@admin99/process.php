<?php
	include("config/config.inc.php");
	include("config/pagination.php");
	include("fckeditor/fckeditor.php") ;
	
	function getadminusers($auid='',$start='',$limitstr='')
	{
		$sql = "select * from adminusers where status=1 ";
		if($auid != '')
			$sql .= " and auid=".$auid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	
	
	
	////////////////////////////////////////////////////////
	
	function manageadminusers($fname,$lname,$username,$password,$email,$auid='')
	{
		if($auid!= '')
		{	
		/*echo "update adminusers set fname='$fname',lname='$lname',username='$username',password='$password',email='$email' where auid=$auid";*/
		mysql_query("update adminusers set fname='$fname',lname='$lname',username='$username',password='$password',email='$email' where auid=$auid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into adminusers (fname,lname,username,password,email) values('$fname','$lname','$username','$password','$email')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	
	
    
	
	///////////////////////Header Slide ShowImages/////////////////////////////////
	function getslideshowimg($simgid='',$start='',$limitstr='')
	{
		$sql = "select * from slideshowimg";
		if($simgid != '')
			$sql .= " where simgid=".$simgid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	
	//////////////////////////insert and update slide show images/////////
	function manageslideshowimg($title,$tagline,$weburl,$bimage,$publishdate,$status,$simgid='')
	{
		if($simgid!= '')
		{
			mysql_query("update slideshowimg set title='$title',tagline='$tagline',weburl='$weburl',bimage='$bimage',publishdate='$publishdate',status='$status' where simgid=$simgid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into slideshowimg(title,tagline,weburl,bimage,publishdate,status) values('$title','$tagline','$weburl','$bimage','$publishdate','$status')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	//////////////////////////////////////////////////////////////////////
	
	
	function getajsmr_issueyears($catid='',$start='',$limitstr='')
	{
		$sql = "select * from ajsmr_issueyears ";
		if($catid != '')
			$sql .= " where catid=".$catid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	function manageajsmr_issueyears($catename,$eventdate,$status,$catid='')
	{
		if($catid != '')
		{
			mysql_query("update ajsmr_issueyears  set catename='$catename',eventdate='$eventdate',status='$status' where catid=$catid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into ajsmr_issueyears(catename,eventdate,status) values('$catename','$eventdate','$status')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	
	function getajsmr_issuecontent($contentid='',$start='',$limitstr='')
	{
		$sql = "select * from ajsmr_issuecontent";
		if($contentid != '')
			$sql .= " where contentid=".$contentid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	
	function manageajsmr_issuecontent($catid,$type,$conttitle,$authors,$journal,$recevied,$accepted,$published,$doi,$abstract,$absurl,$fullpaper,$photopath,$status,$contentid='')
	{
		if($contentid != '')
		{
			mysql_query("update ajsmr_issuecontent set catid='$catid',type='$type',conttitle='$conttitle',authors='$authors',journal='$journal',recevied='$recevied',accepted='$accepted',published='$published',doi='$doi',abstract='$abstract',absurl='$absurl',fullpaper='$fullpaper',photopath='$photopath',status='$status' where contentid=$contentid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into ajsmr_issuecontent (catid,type,conttitle,authors,journal,recevied,accepted,published,doi,abstract,absurl,fullpaper,photopath,status) values('$catid','$type','$conttitle','$authors','$journal','$recevied','$accepted','$published','$doi','$abstract','$absurl','$fullpaper','$photopath','$status')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	
	/////////////////////////Content Pages//////////////////////
	function getcontentpages($conid ='',$start='',$limitstr='')
	{
		//$sql = "select * from padmavathi_articles where status=1  order by newsid DESC";
		$sql = "select * from contentpages where status=1";
		if($conid != '')
			$sql .= " and conid =".$conid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;	
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	////////////////////////////////////////////////////////////
	//////////////////////////Content Pages////////////////////////
	function managecontentpages($title,$dailytitle,$description,$photopath,$photopath2,$photopath3,$photopath4,$photopath5,$photopath6,$photopath7,$photopath8,$publishdate,$conid='')
	{
		if($conid!= '')
		{
		mysql_query("update contentpages set title='$title',dailytitle='$dailytitle',description='".addslashes($description)."',photopath='$photopath',photopath2='$photopath2',photopath3='$photopath3',photopath4='$photopath4',photopath5='$photopath5',photopath6='$photopath6',photopath7='$photopath7',photopath8='$photopath8',publishdate='$publishdate' where conid =$conid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{		
		mysql_query("insert into contentpages(title,dailytitle,description,photopath,photopath2,photopath3,photopath4,photopath5,photopath6,photopath7,photopath8,publishdate) values('$title','$dailytitle','".addslashes($description)."','$photopath','$photopath2','$photopath3','$photopath4','$photopath5','$photopath6','$photopath7','$photopath8','$publishdate')");
			if(mysql_affected_rows()>0)
				return true;
		}
		
		return false;
	}
	///////////////////////////////////////////////////////////////
	
	/////////////////////////Select Resumes//////////////////////
	function geteditorialboards($rid ='',$start='',$limitstr='')
	{
		$sql = "select * from editorialboards where status=1  ";
		if($rid != '')
			$sql .= " and rid =".$rid;
		if($limitstr != '')
			$sql .= "order by rid DESC limit ".$start.",".$limitstr;	
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	////////////////////////////////////////////////////////////
	
	/////////////////////////Select Resumes//////////////////////
	function getsubmitmanuscripts($rid ='',$start='',$limitstr='')
	{
		$sql = "select * from submitmanuscripts where status=1  ";
		if($suid != '')
			$sql .= " and suid =".$suid;
		if($limitstr != '')
			$sql .= "order by suid DESC limit ".$start.",".$limitstr;	
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	////////////////////////////////////////////////////////////
	///////Templates and Copyright forms///////////////////////
	function gettemplatesandcopyrights($contentid='',$start='',$limitstr='')
	{
		$sql = "select * from templatesandcopyrights";
		if($contentid != '')
			$sql .= " where contentid=".$contentid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	
	function managetemplatesandcopyrights($catid,$type,$conttitle,$authors,$journal,$recevied,$accepted,$published,$doi,$abstract,$fullpaper,$photopath,$status,$contentid='')
	{
		if($contentid != '')
		{
			mysql_query("update templatesandcopyrights set catid='$catid',type='$type',conttitle='$conttitle',authors='$authors',journal='$journal',recevied='$recevied',accepted='$accepted',published='$published',doi='$doi',abstract='$abstract',fullpaper='$fullpaper',photopath='$photopath',status='$status' where contentid=$contentid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into templatesandcopyrights (catid,type,conttitle,authors,journal,recevied,accepted,published,doi,abstract,fullpaper,photopath,status) values('$catid','$type','$conttitle','$authors','$journal','$recevied','$accepted','$published','$doi','$abstract','$fullpaper','$photopath','$status')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	
	//////////////Manage Abstract /////////////////
	function getajsmr_abstracts($contentid='',$start='',$limitstr='')
	{
		$sql = "select * from ajsmr_abstracts";
		if($contentid != '')
			$sql .= " where contentid=".$contentid;
		if($limitstr != '')
			$sql .= " limit ".$start.",".$limitstr;
		$rs = mysql_query($sql);
		if($rs && mysql_num_rows($rs)>0)
			return($rs);
		return false;
	}
	
	function manageajsmr_abstracts($conttitle,$issuedetails,$authors,$journal,$doi,$abstract,$abstractsdesc,$referencestxt,$keywords,$howtoguide,$articledates,$status,$contentid='')
	{
		if($contentid != '')
		{
			mysql_query("update ajsmr_abstracts set conttitle='$conttitle',issuedetails='$issuedetails',authors='$authors',journal='$journal',doi='$doi',abstract='$abstract',abstractsdesc='$abstractsdesc',referencestxt='".addslashes($referencestxt)."',keywords='$keywords',howtoguide='$howtoguide',articledates='$articledates',status='$status' where contentid=$contentid");
			if(mysql_affected_rows()>0)
				return true;
		}
		else
		{
			mysql_query("insert into ajsmr_abstracts (conttitle,issuedetails,authors,journal,doi,abstract,abstractsdesc,referencestxt,keywords,howtoguide,articledates,status) values('$conttitle','$issuedetails','$authors','$journal','$doi','$abstract','$abstractsdesc','".addslashes($referencestxt)."','$keywords','$howtoguide','$articledates','$status')");
			if(mysql_affected_rows()>0)
				return true;
		}
		return false;
	}
	
?>