// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.cityname.value == "")
{
	alert("Please enter city name");
	f.cityname.focus();
	return false;
}
if(f.timezone.value == "")
{
	alert("Please select timezone");
	f.timezone.focus();
	return false;
}
	else return true;
}

function delete_record(cityid)
{
	if(confirm("Are you sure to delete this City?"))
	{
		document.location.href='managecities.php?del='+cityid;
	}
}

