// JavaScript Document

function validate()
{
	if(document.theaters.theatername.value == "")
	{
		alert("Please enter theater name");
		document.theaters.theatername.focus();
		return false;
	}
	if(document.theaters.cityid.value == "")
	{
		alert("Please select city");
		document.theaters.cityid.focus();
		return false;
	}
	if(document.theaters.seats.value == "")
	{
		alert("Please enter number of seats");
		document.theaters.seats.focus();
		return false;
	}
	if(document.theaters.adtprice.value == "")
	{
		alert("Please enter adult price");
		document.theaters.adtprice.focus();
		return false;
	}
	if(document.theaters.chtprice.value == "")
	{
		alert("Please enter children price");
		document.theaters.chtprice.focus();
		return false;
	}
	if(document.theaters.srtktprice.value == "")
	{
		alert("Please enter senior citizen price");
		document.theaters.srtktprice.focus();
		return false;
	}
	if(document.theaters.shows.value == "" )
	{
		alert("Please enter shows");
		document.theaters.shows.focus();
		return false;
	}
	var shtimes = document.getElementsByName("showtime[]");
	for(i=0;i<shtimes.length;i++)
	{
		if(checkshowtime(shtimes[i].value) == 0 || shtimes[i].value == '')
		{
			alert("Time is not in a valid format. Please give time in hh:mm am/AM/pm/PM");	
			shtimes[i].focus();
			return false;
		}
	}
	var valid=true;
	var oEditor = FCKeditorAPI.GetInstance('descr') ;
	var jsArticleBody = oEditor.GetXHTML(true) ;
	if(!jsArticleBody)
	{
		alert("Please enter description");
		return false;
	}
}
function checkshowtime(shtime)
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
		return flag;
		/*if(flag == 0)
		{
			alert("Time is not in a valid format. Please give time in hh:mm am/AM/pm/PM");	
			document.timings.showtime.focus();
			return false;
		}*/
}
function delete_record(theaterid)
{
	if(confirm("Are you sure to delete this theater?"))
	{
		document.location.href='managetheaters.php?del='+theaterid;
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