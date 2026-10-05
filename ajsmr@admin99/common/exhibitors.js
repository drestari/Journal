// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.exbname.value == "")
{
	alert("Please Exhibitor Name");
	f.exbname.focus();
	return false;
}
if(f.exbemail.value == "")
{
	alert("Please Exhibitor Email");
	f.exbemail.focus();
	return false;
}
if(f.exbphone.value == "")
{
	alert("Please Exhibitor Phone");
	f.exbphone.focus();
	return false;
}
if(f.password.value == "")
{
	alert("Please Exhibitor Password");
	f.password.focus();
	return false;
}
	else return true;
}