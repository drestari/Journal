// JavaScript Document
function validate(f)
{
//	alert("hai");
if(f.moviename.value == "")
{
	alert("Please Enter Movie Name");
	f.moviename.focus();
	return false;
}
if(f.langid.value == "")
{
	alert("Please Select Language");
	f.langid.focus();
	return false;
}
if(f.rdate.value == "")
{
	alert("Please Enter Release Date");
	f.rdate.focus();
	return false;
}
if(f.casting.value == "")
{
	alert("Please Enter Casting");
	f.casting.focus();
	return false;
}

if(f.old_pic.value == "" && f.pic.value == "")
{
	alert("Please Upload Image");
	f.pic.focus();
	return false;
}

if(f.banner.value == "")
{
	alert("Please Enter Banner");
	f.banner.focus();
	return false;
}
if(f.producer.value == "")
{
	alert("Please Enter Producer Name");
	f.producer.focus();
	return false;
}
if(f.director.value == "")
{
	alert("Please Enter Director Name");
	f.director.focus();
	return false;
}
if(f.musicdirector.value == "")
{
	alert("Please Enter Musicdirector Name");
	f.musicdirector.focus();
	return false;
}
if(f.cinematographer.value == "")
{
	alert("Please Enter Cinematographer");
	f.cinematographer.focus();
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
 if( getSelectedIndex(f.status) == -1 )
					{
						alert("Please select Status." );
						f.status[0].focus();
						return false;
						;
					}
	else return true;
}

function delete_record(movieid)
{
	if(confirm("Are you sure to delete this Movie?"))
	{
		document.location.href='managemovies.php?del='+movieid;
	}
}

