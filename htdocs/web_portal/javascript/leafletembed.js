// javascript file to display a leaflet map.
// Based on https://leafletjs.com/examples/quick-start/ and
// https://switch2osm.org/using-tiles/getting-started-with-leaflet/

var map;

// Function to display a leaflet map with markers from GOCDB Site data.
function initmap() {
	// set up the map
	map = new L.Map('map');

	// create the tile layer with correct attribution.
	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {
        attribution: osmAttrib,
        // A min zoom of 2 gives you a view of most of the world. Any further out starts to look "bad".
        minZoom: 2,
        // A max zoom of 20 or more doesn't have any map tiles.
        maxZoom: 19,
    });		

	// Centre the view so that most sites can be viewed at level 2.
	map.setView(new L.LatLng(30, 10), 2);
	map.addLayer(osm);
    
    downloadUrl("index.php?Page_Type=Site_Geo_xml", function(data) {
        var markers = [];
        var xmlMarkers = data.documentElement.getElementsByTagName("Site");
        for (var i = 0; i < xmlMarkers.length; i++) {
            var latlng = [
                parseFloat(xmlMarkers[i].getAttribute("Latitude")),
                parseFloat(xmlMarkers[i].getAttribute("Longitude"))
            ];
            var shortName = xmlMarkers[i].getAttribute("ShortName");
            var officialName = xmlMarkers[i].getAttribute("OfficialName");
            var url = xmlMarkers[i].getAttribute("PortalURL");
            var description = xmlMarkers[i].getAttribute("Description");
            //Add a line break if the description actualy has anything in it
            if (description.length!==0){
                description += "<br />";
            }
            var info = "<b>" + shortName + "</b> ("+ officialName + ")<br />" + description + "<a href=\"" + url + "\">View site</a>";

            var marker = L.marker(latlng).addTo(map);
            marker.bindPopup(info)
        }
   });
}

/**
* Returns an XMLHttp instance to use for asynchronous
* downloading. This method will never throw an exception, but will
* return NULL if the browser does not support XmlHttp for any reason.
* @return {XMLHttpRequest|Null}
*/
function createXmlHttpRequest() {
 try {
   if (typeof ActiveXObject != 'undefined') {
     return new ActiveXObject('Microsoft.XMLHTTP');
   } else if (window["XMLHttpRequest"]) {
     return new XMLHttpRequest();
   }
 } catch (e) {
   changeStatus(e);
 }
 return null;
};

/**
* This functions wraps XMLHttpRequest open/send function.
* It lets you specify a URL and will call the callback if
* it gets a status code of 200.
* @param {String} url The URL to retrieve
* @param {Function} callback The function to call once retrieved.
*/
function downloadUrl(url, callback) {
 var status = -1;
 var request = createXmlHttpRequest();
 if (!request) {
   return false;
 }

 request.onreadystatechange = function() {
   if (request.readyState == 4) {
     try {
       status = request.status;
     } catch (e) {
       // Usually indicates request timed out in FF.
     }
     if (status == 200) {
       callback(request.responseXML, request.status);
       request.onreadystatechange = function() {};
     }
   }
 }
 request.open('GET', url, true);
 try {
   request.send(null);
 } catch (e) {
   changeStatus(e);
 }
};

/**
 * Parses the given XML string and returns the parsed document in a
 * DOM data structure. This function will return an empty DOM node if
 * XML parsing is not supported in this browser.
 * @param {string} str XML string.
 * @return {Element|Document} DOM.
 */
function xmlParse(str) {
  if (typeof ActiveXObject != 'undefined' && typeof GetObject != 'undefined') {
    var doc = new ActiveXObject('Microsoft.XMLDOM');
    doc.loadXML(str);
    return doc;
  }

  if (typeof DOMParser != 'undefined') {
    return (new DOMParser()).parseFromString(str, 'text/xml');
  }

  return createElement('div', null);
}

/**
 * Appends a JavaScript file to the page.
 * @param {string} url
 */
function downloadScript(url) {
  var script = document.createElement('script');
  script.src = url;
  document.body.appendChild(script);
}
