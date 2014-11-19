    
	//When passed an element id it will hide the element if visible and show it if hidden
	function showHide(id) {
		console.log("Info"+id);
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
	
	/*For use with downtimes active and imminent in the gocdb portal. Will change 
	* the user control depending on whether the user has expanded the extra information
	* table or not */	
	function toggleMessage(id) {
		console.info(id);
		content = document.getElementById(id).innerHTML;
		
		if(content.indexOf("+") !== -1){
			console.log("Found +");
			document.getElementById(id).innerHTML = "-Hide Affected Services";
		}else{
			console.log("Not Found +");
			document.getElementById(id).innerHTML = "+Show Affected Services";
		}
		
	}
