// JavaScript Document

function validate()
{
	if(document.moveinfo.hero.value == "")
	{
		alert("Please enter theater name");
		document.moveinfo.hero.focus();
		return false;
	}
	if(document.moveinfo.heroin.value == "")
	{
		alert("Please select city");
		document.moveinfo.heroin.focus();
		return false;
	}
	if(document.moveinfo.casting.value == "")
	{
		alert("Please enter number of seats");
		document.moveinfo.casting.focus();
		return false;
	}
	if(document.moveinfo.banner.value == "")
	{
		alert("Please enter adult price");
		document.moveinfo.banner.focus();
		return false;
	}
	if(document.moveinfo.producer.value == "")
	{
		alert("Please enter children price");
		document.moveinfo.producer.focus();
		return false;
	}
	if(document.moveinfo.director.value == "")
	{
		alert("Please enter senior citizen price");
		document.moveinfo.director.focus();
		return false;
	}
	if(document.moveinfo.musicdirector.value == "")
	{
		alert("Please enter shows");
		document.moveinfo.musicdirector.focus();
		return false;
	}
	if(document.moveinfo.cinematographer.value == "")
	{
		alert("Please enter shows");
		document.moveinfo.cinematographer.focus();
		return false;
	}
	var valid=true;
	var oEditor = FCKeditorAPI.GetInstance('descr') ;
	var jsArticleBody = oEditor.GetXHTML(true) ;
	if(!jsArticleBody)
	{
		alert("Please enter description");
		return false;
	}
}

function delete_record(infoid)
{
	if(confirm("Are you sure to delete this Movieinfo?"))
	{
		document.location.href='movieinfo.php?del='+infoid;
	}
}