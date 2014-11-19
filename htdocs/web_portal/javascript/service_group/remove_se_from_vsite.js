/**
 * Removes an SE (identified by seId) from a VSite (vSiteId)
 * @param siteless - true or false: whether this service doesn't have
 * 					 a hosting physical site (i.e. the service was created
 * 					 directly under a service group and the siteless_services
 * 					 setting is enabled).
 */
function removeSe(seId, sgId, siteless) {
	// If the service doesn't have a physical hosting site
	if(siteless) {
		var message = "This service was created by this service " + 
			"group. Removing it will result in deletion of the service." + 
			" Continue?";
		// Check that the user understands that the service will be deleted
		// not just removed from this service group
		if(!confirm(message)) {
			// User clicked cancel, don't continue deleting this service 
			return;
		}
	}
	
	// Send the ajax request to remove the se
	var url = 'index.php?Page_Type=Remove_Service_Group_SEs';
	var parameters = { sgId: sgId, seId: seId };
	
	$.post(url, parameters, resultsTable.removeRow);
}

var resultsTable = {
	// When successful remove the SE from the table
	removeRow: function(seId){
		if(seId == "permissionError") {
			alert("You do not have permission to perform this operation");
			throw new Error("permissionError");
		}
		
		var row = document.getElementById(seId + "Row");
		row.parentNode.removeChild(row);
	}
};