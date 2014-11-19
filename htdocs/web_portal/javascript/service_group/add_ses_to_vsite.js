// The latest search results, an array of service endpoints
var ses = null;
// Used to alternate the coloured rows in a table
var selectedColour = "";
/**
 * Searches for the entered term. Sends an AJAX request. The result of the
 * request will call resultsTable.displayResults
 */
function startSearch() {
	// get search term
	var filter = document.getElementById('filter');
	var term = filter.value;
	// if necessary unhide results table
	var tableContainer = document.getElementById('resultsContainer');
	tableContainer.style.display = 'inline';
	// clear results table
	clearSEs();
	// add a row showing a loading message / image
	var seTable = document.getElementById('seTable');
	var row = seTable.insertRow(seTable.getElementsByTagName("tr").length);
	var td=document.createElement("td");
	td.innerHTML='<img src="img/spinner.gif" style="text-align: center; width=100%;" />';
	row.appendChild(td);
	td.setAttribute("colspan", "4");
	td.setAttribute("style", "text-align: center;");
	// execute ajax query
	var ses = ajaxRequest('index.php?Page_Type=Search_SEs&term=' + term, resultsTable.displayResults);
}

/* A results table object. Displays results */  
var resultsTable = {
    displayResults: function(results) {
		// get the results in an array
		ses = eval('(' + results + ')');
		// clear the loading row
		clearSEs();
		// add results to table
		var colour = "";
		var table = document.getElementById('seTable');
		for (i = 0; i < ses.length; i++) {
			addRow(colour, ses[i], table, plusCell(ses[i]));
			// Swap the row colour
			if (colour == "") {
				colour = "1";
			} else {
				colour = "";
			}
		}
	}
}

/**
 * Clears the text in the filter text box
 */
function clearText() {
	var filter = document.getElementById('filter');
	if (filter.value == "Search") {
		filter.value = "";
	}
}

/**
 * Remove all rows in the "Service Endpoints" box Called whenever a new filter
 * is applied
 */
function clearSEs() {
	var seTable = document.getElementById('seTable');
	var rowCount = seTable.getElementsByTagName("tr").length;
	for (i = 0; i < rowCount - 1; i++) {
		seTable.deleteRow(seTable.getElementsByTagName("tr").length - 1);
	}
}

/* Adds a row to the SE Table (results). This function is called once for each
 * filter search result
 */
function addRow(colour, se, table, firstCell, rowId) {
	var seTable = table;
	var row = seTable.insertRow(seTable.getElementsByTagName("tr").length);
	row.className = "site_table_row_" + colour;
	row.setAttribute('id', rowId + "Row");

	// Cell one includes the select (plus) icon to the table
	var cell1 = row.insertCell(0);
	cell1.className = "site_table";
	cell1.appendChild(firstCell);

	// Cell two contains the service endpoint reference
	var cell2 = row.insertCell(1);
	var div = document.createElement("div");
	div.setAttribute('style', 'background-color: inherit;');
	var img = document.createElement("img");
	img.setAttribute('src', 'img/server.png');
	img.setAttribute('height', '25px');
	img.setAttribute('style', 'vertical-align: middle; padding-right: 1em;');
	var span = document.createElement("span");
	span.setAttribute('style', 'vertical-align: middle;');
	var a = document.createElement("a");
	a.setAttribute('href', 'index.php?Page_Type=Service&id=' + se.id);
	a.setAttribute('target', '_blank');
	a.innerHTML = se.serviceType.name + " - " + se.hostName;

	span.appendChild(a);
	div.appendChild(img);
	div.appendChild(span);
	cell2.appendChild(div);

	// Cell 3 is the description
	var cell3 = row.insertCell(2);
	// Check to see if the description is null
	// If it is we explicitly set the field to ""
	// If we don't explicitly set this, IE prints it out as "null"
	if(se.description != null) {
		// Description isn't null, show the description
		cell3.innerHTML = se.description;	
	} else {
		// se.DESCRIPTION is null so set the cell contents to blank
		cell3.innerHTML = "";
	}

	// Cell 4 is the hosting site
	var cell4 = row.insertCell(3);
	// If the site name is null then the service endpoint doesn't have
	// a hosting site. In this case, show a N/A message
	if(se.parentSite == null) {
		var span = document.createElement("span");
		span.setAttribute('style', 'vertical-align: middle;');
		span.innerHTML = "N/A";
		cell4.appendChild(span);
	} else {
		// This part of the else clause is fired if a sitename is passed
		// through to the script. In this case we just show
		// the site name as normal.
		var a = document.createElement("a");
		a.setAttribute('href', 'index.php?Page_Type=Site&id=' + se.parentSite.id);
		a.innerHTML = se.parentSite.shortName;
		a.setAttribute('target', '_blank');
		cell4.appendChild(a);
	}
}

/**
 * Adds the selected SE to the bottom "Selected SEs" table Also adds the SE to
 * an invisible form
 */
function add(seId) {
	var table = document.getElementById('selectedSETable');
	var existing = document.getElementById(seId);
	// If the SE has already been added, don't add it again
	if (existing) {
		return;
	}

	// Find the SE (ses is a global array defined at the top of this file)
	for (i = 0; i < ses.length; i++) {
		if (ses[i].id == seId) {
			var se = ses[i];
			break;
		}
	}
	
	// Add the endpoint to the table
	var firstCell = crossCell(se);
	addRow(selectedColour, se, table, firstCell, se.id);
	if (selectedColour == "") {
		selectedColour = "1";
	} else {
		selectedColour = "";
	}

	// Add the endpoint to the invisible form to be submitted
	var form = document.getElementById('sesToAdd');
	var input = document.createElement("input");
	input.setAttribute("id", se.id);
	input.setAttribute("type", "hidden");
	input.setAttribute("name", "endpointIds[]");
	input.setAttribute("value", se.id);
	form.appendChild(input);
	return void(0);
}

/* Creates a plus (add) cell from the passed SE */
function plusCell(se) {
	var img = document.createElement("img");
	img.setAttribute('src', 'img/add.png');
	img.setAttribute('height', '25px');
	img.setAttribute('width', '25px');
	img.setAttribute('class', "centered");
	var a = document.createElement("a");
	a.appendChild(img);
	a.setAttribute('href', "#");
	a.appendChild(img);
	a.setAttribute('onClick', 'add(' + se.id + ')');
	return a;
}

/* Creates a cross (remove) cell from the passed SE */
function crossCell(se) {
	var img = document.createElement("img");
	img.setAttribute('src', 'img/cross.png');
	img.setAttribute('height', '25px');
	img.setAttribute('width', '25px');
	img.setAttribute('class', "centered");
	var a = document.createElement("a");
	a.appendChild(img);
	a.setAttribute('href', "#");
	a.appendChild(img);
	a.setAttribute('onClick', 'removeRow(' + se.id + ')');
	return a;
}

function removeRow(seId) {
	// remove the SE from the table
	var row = document.getElementById(seId + "Row");
	row.parentNode.removeChild(row);
	// remove the hidden input field from the form
	var input = document.getElementById(seId);
	input.parentNode.removeChild(input);
}