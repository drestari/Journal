// JavaScript Document

function processb()
{
	if(confirm("Are you sure to process blocked tickets?"))
	{
		document.location.href='processbtickets.php?del='+1;
	}
}

