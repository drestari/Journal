// JavaScript Document
function validate(f)
{
if(f.old_introbanner.value=="" && f.introbanner.value == "")
{
	alert("Please Upload Intro Banner");
	f.introbanner.focus();
	return false;
}
if(f.old_homebanner.value=="" && f.homebanner.value == "")
{
	alert("Please Upload Home Banner");
	f.homebanner.focus();
	return false;
}
if(f.old_bookingbanner.value=="" && f.bookingbanner.value == "")
{
	alert("Please Upload Booking Banner");
	f.bookingbanner.focus();
	return false;
}
 if( getSelectedIndex(f.status) == -1 )
					{
						alert("Please select Status." );
						f.status[0].focus();
						return false;
						;
					}
	else return true;
}