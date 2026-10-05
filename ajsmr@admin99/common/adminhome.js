var xmlhttp

function getcity(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getcity.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("txtcity").innerHTML=xmlhttp.responseText;
  }
}
;
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


function gettheaters(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getcitytheaters.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("txtcinema").innerHTML=xmlhttp.responseText;
  }
};
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}



function getshowtime(str)
{
xmlhttp=GetXmlHttpObject();
var movieid = document.getElementById('movieid').value;
//alert(movieid);
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getshowtime.php";
url=url+"?q="+str;
url=url+"&movieid="+movieid;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("showtime").innerHTML=xmlhttp.responseText;
  }
};
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function getmovie(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getmovie.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("movie").innerHTML=xmlhttp.responseText;
  }
};
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function getmtheaters(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="getcinemas.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("cinemas").innerHTML=xmlhttp.responseText;
  }
}
;
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function val()
{
	if(document.bookings.cityid.value == "")
	{
		alert("Please select city");
		document.bookings.cityid.focus();
		return false;
	}
	if(document.bookings.movieid.value == "")
	{
		alert("Please select movie");
		document.bookings.movieid.focus();
		return false;
	}
	if(document.bookings.bdate.value == "")
	{
		alert("Please select date");
		document.bookings.bdate.focus();
		return false;
	}
	
}