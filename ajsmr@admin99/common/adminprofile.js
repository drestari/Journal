// JavaScript Document
var xmlhttp
function checkavail(str)
{
	var str = document.adminprofile.username.value;
	var userid = document.adminprofile.auid;
	xmlhttp=GetXmlHttpObject();
	if (xmlhttp==null)
	{
		alert ("Your browser does not support XMLHTTP!");
		return;
	}
	var url="checkadminavail.php";
	url=url+"?q="+str;
	if(userid && !isNaN(userid.value))
		url=url+"&userid="+userid.value;
	url=url+"&sid="+Math.random();
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4)
		{  
		//alert(xmlhttp.responseText);
			var resptext = xmlhttp.responseText;
			var resparray = resptext.split("**");
			document.getElementById("txtavail").innerHTML=resparray[0];
			document.getElementById("avail").value=resparray[1];
			
		}
	};
	xmlhttp.open("GET",url,true);
	xmlhttp.send(null);
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







function validate()
{
		var username = document.getElementById("username");
	var cusername = document.getElementById("cusername");
	
if(cusername)
	{
		//alert("111 --- cu : "+cusername.value+" -- u : "+username.value);
		if(username.value!=cusername.value && document.adminprofile.avail.value == 'no')
		{
			alert("User name is not available or Plz check availability");
			return false;
		}
	}
	else if( document.adminprofile.avail.value == 'no')
	{
		//alert("222--- u : "+username.value);
		alert("User name is not available or Plz check availability");
		return false;
	}
if(document.adminprofile.fname.value == "")
{
	alert("Please enter first name");
	document.adminprofile.fname.focus();
	return false;
}
if(document.adminprofile.lname.value == "")
{
	alert("Please enter last name");
	document.adminprofile.lname.focus();
	return false;
}	
if(document.adminprofile.username.value == "")
{
	alert("Please enter username");
	document.adminprofile.username.focus();
	return false;
}
if(document.adminprofile.password.value == "")
{
	alert("Please enter password");
	document.adminprofile.password.focus();
	return false;
}
var emailRegEx = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	 str = document.adminprofile.email.value;
	if(!str.match(emailRegEx))
	{
		alert("Please Enter a Valid Email address");
		document.adminprofile.email.focus();
		return false;
	}
}