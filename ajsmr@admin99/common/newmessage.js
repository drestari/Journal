// JavaScript Document
function validate()
{
if(document.newmessage.to.value == "")
{
alert("Please enter to");
document.newmessage.to.focus();
return false;
}
if(document.newmessage.subject.value == "")
{
alert("Please enter subject");
document.newmessage.subject.focus();
return false;
}
if(document.newmessage.message.value == "")
{
alert("Please enter message");
document.newmessage.message.focus();
return false;
}
}