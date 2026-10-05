var xmlhttp

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



function gettheaters(str,movieid)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="gettheaters.php";
url=url+"?cityid="+str+"&movieid="+movieid;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("txtthid").innerHTML=xmlhttp.responseText;
  }
};
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function gettimings(str)
{
//	alert(str);
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="gettimings.php";
url=url+"?tid="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("timeid").innerHTML=xmlhttp.responseText;
  }
};
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function validate()
{
	if(document.theaters.moviename.value == "")
	{
		alert("Please enter moviename");
		document.theaters.moviename.focus();
	    return false;
	}
	if(document.theaters.cityid.value == "")
	{
		alert("Please select city");
		document.theaters.cityid.focus();
	    return false;
	}
	if(document.theaters.theaterid.value == "")
	{
		alert("Please select theater");
		document.theaters.theaterid.focus();
	    return false;
	}
	if(document.theaters.startdate.value == "")
	{
		alert("Please enter startdate");
		document.theaters.startdate.focus();
	    return false;
	}
	if(document.theaters.enddate.value == "")
	{
		alert("Please enter enddate");
		document.theaters.enddate.focus();
	    return false;
	}
}

function delete_record(tmid)
{
	if(confirm("Are you sure to delete this Theater?"))
	{
		document.location.href='managemovietheater.php?del='+tmid;
	}
}
