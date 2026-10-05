// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.languagename.value == "")
{
	alert("Please Enter Language Name");
	f.languagename.focus();
	return false;
}

else return true;
}

function delete_record(langid)
{
	if(confirm("Are you sure to delete this Language?"))
	{
		document.location.href='managelanguages.php?del='+langid;
	}
}

