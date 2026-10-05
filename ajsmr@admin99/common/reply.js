// JavaScript Document
function validate()
{
	if(document.replymessage.toname.value == "")
	 {
		 alert("Please enter to");
		 document.replymessage.toname.focus();
		 return false;
	 }
     if(document.replymessage.subject.value == "")
	 {
		 alert("Please enter subject");
		 document.replymessage.subject.focus();
		 return false;
	 }
     if(document.replymessage.message.value == "")
	 {
		 alert("Please enter message");
		 document.replymessage.message.focus();
		 return false;
	 }


}