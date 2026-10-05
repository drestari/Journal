// JavaScript Document

//form validation
function validateForm(f)
{
	if(!GenValidation(f.name,'Name','','') || !GenValidation(f.emailid,'Email Address','','') || !GenValidation(f.phone,'Phone Number','','')){
		return false;
	}else return true;
}
//end of form validation