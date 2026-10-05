// JavaScript Document
function validate(f)
{
if(f.title.value == "")
{
	alert("Please enter title");
	f.title.focus();
	return false;
}
if(f.descr.value == "")
{
	alert("Please enter descripation");
	f.descr.focus();
	return false;
}

else if( getSelectedIndex(f.status) == -1 )
					{
						alert("Please select Status." );
						f.status[0].focus();
						return false;
						;
					}
	else return true;
}