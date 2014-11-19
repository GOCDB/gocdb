function nav()
{
	var w = document.formSelect.pageSelect.selectedIndex;
	var url_add = 
		document.formSelect.pageSelect.options[w].value;
	window.location.href = url_add;
}