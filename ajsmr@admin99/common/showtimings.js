// JavaScript Document

function validate()
{
	var shtime = document.timings.showtime.value;
	if( shtime == "")
	{
		alert("Please enter showtime");
		document.timings.showtime.focus();
		return false;
	}
	else
	{
		var flag = 1;
		var shvalues = shtime.split(":");
		if(shvalues.length!=2)
		{
			flag = 0;
		}
		else
		{
			if(isNaN(parseInt(shvalues[0])))
			{
				flag = 0;
			}
			else
			{
				var shtmarray = shvalues[1].split(" ");
				if(shtmarray.length!=2 || isNaN(parseInt(shtmarray[0])) )
				{
					flag = 0;
				}
				else if(shtmarray[1].toLowerCase() != 'am' && shtmarray[1].toLowerCase() != 'pm')
				{
					flag = 0;
				}
			}
		}
		if(flag == 0)
		{
			alert("Time is not in a valid format. Please give time in hh:mm am/AM/pm/PM");	
			document.timings.showtime.focus();
			return false;
		}
	}
}

function delete_record(showtimeid,theaterid)
{
	if(confirm("Are you sure to delete this show timing?"))
	{
		document.location.href='showtimings.php?del='+showtimeid+'&theaterid='+theaterid;
	}
}




var xmlhttp

function gettxtbox(str)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Your browser does not support XMLHTTP!");
  return;
  }
var url="gettxtbox.php";
url=url+"?q="+str;
url=url+"&sid="+Math.random();
xmlhttp.onreadystatechange=function()
{
if (xmlhttp.readyState==4)
  {
  document.getElementById("txtboxid").innerHTML=xmlhttp.responseText;
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