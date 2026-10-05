// JavaScript Document
// JavaScript Document
function validate(f)
{
if(f.regionid.value == "")
{
	alert("Please enter region");
	f.regionid.focus();
	return false;
}
if(f.state.value == "")
{
	alert("Please enter State");
	f.state.focus();
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

function delete_record(stateid)
{
	if(confirm("Are you sure to delete this State?"))
	{
		document.location.href='managestates.php?del='+stateid;
	}
}
