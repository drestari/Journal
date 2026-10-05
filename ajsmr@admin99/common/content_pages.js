// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.cityname.value == "")
{
	alert("Please Enter City Name");
	f.cityname.focus();
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

function delete_record(contentid)
{
	if(confirm("Are you sure to delete this Content?"))
	{
		document.location.href='content_pages.php?del='+contentid;
	}
}

