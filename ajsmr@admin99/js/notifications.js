// JavaScript Document
function validateForm(f)
{
	if(!GenValidation(f.title,'News Title','','text') || !GenValidation(f.description,'News Descripation','','') || !GenValidation(f.publishdate,'Entry Date','','')){
		return false;
	}
	else if( getSelectedIndex(f.status) == -1 )
					{
						alert("Please select Status." );
						f.status[0].focus();
						return;
					}
	else return true;
}