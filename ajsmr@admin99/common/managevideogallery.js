// JavaScript Document
function validate()
{
if(document.videogallery.eventid.value == "")
{
	alert("Please select event");
	document.videogallery.eventid.focus();
	return false;
}
if(document.videogallery.programid.value == "")
{
	alert("Please select program");
	document.videogallery.programid.focus();
	return false;
}
if(document.videogallery.title.value == "")
{
	alert("Please enter title");
	document.videogallery.title.focus();
	return false;
}
if(document.videogallery.description.value == "")
{
	alert("Please enter description");
	document.videogallery.description.focus();
	return false;
}
}

function delete_record(vgid)
{
	if(confirm("Are you sure to delete this Videogallery?"))
	{
		document.location.href='managevideogallery.php?del='+vgid;
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
