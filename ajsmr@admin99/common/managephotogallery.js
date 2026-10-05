// JavaScript Document
function validate()
{
if(document.photogallery.movieid.value == "")
{
	alert("Please select movie");
	document.photogallery.movieid.focus();
	return false;
}
if(document.photogallery.title.value == "")
{
	alert("Please enter title");
	document.photogallery.title.focus();
	return false;
}
/*if(document.photogallery.description.value == "")
{
	alert("Please enter description");
	document.photogallery.description.focus();
	return false;
}*/
if(document.photogallery.dbpic.value == "" && document.photogallery.image.value == "")
{
	alert("Please Upload Image");
	document.photogallery.image.focus();
	return false;
}
}
/////Select Movie Box Validation Start/////
function svalidate(f)
{
//	alert("hai");
if(f.movieid.value == "")
{
	alert("Please Select Movie Name");
	f.movieid.focus();
	return false;
}
else return true;
}
//////Select Movie Box Validation End////

function delete_record(pgid)
{
	if(confirm("Are you sure to delete this Photogallery?"))
	{
		document.location.href='managephotogallery.php?del='+pgid;
	}
}

var xmlhttp

function getprograms(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getprograms.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=stateChanged1;
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function stateChanged1()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("txtprogram").innerHTML=xmlhttp.responseText;
  }
}

function GetXmlHttpObject()
{
if (window.XMLHttpRequest)
  {
  // code for IE7+, Firefox, Chrome, Opera, Safari
  return new XMLHttpRequest();
  }
if (window.ActiveXObject)
  {
  // code for IE6, IE5
  return new ActiveXObject("Microsoft.XMLHTTP");
  }
return null;
}
