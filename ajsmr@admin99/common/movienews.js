// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.title.value == "")
{
	alert("Please Enter News Title");
	f.title.focus();
	return false;
}
if(f.descr.value == "")
{
	alert("Please Enter Description");
	f.descr.focus();
	return false;
}
if(f.old_image.value == "" && f.image.value == "")
{
	alert("Please Upload Image");
	f.image.focus();
	return false;
}
if(f.pubdate.value == "")
{
	alert("Please Enter Date");
	f.pubdate.focus();
	return false;
}
	else return true;
}


