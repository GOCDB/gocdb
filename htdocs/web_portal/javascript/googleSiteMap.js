//function to display google map. Adapted by George Ryall from the following tutorials:
// * https://developers.google.com/maps/articles/phpsqlajax_v3
// * https://developers.google.com/maps/documentation/javascript/tutorial
// * http://www.w3schools.com/googleAPI/
// * The example code here : http://gmaps-samples-v3.googlecode.com/svn/trunk/xmlparsing/ 

google.maps.event.addDomListener(window, 'load', initialize);

var infowindow;
var map;

function initialize() {
    var mapProp = {
        //Starting position of map
        center:new google.maps.LatLng(30,0),
        zoom:2,
        //Enable zoom control, but move it to the left and make it small
        zoomControl:true,
        zoomControlOptions: {
            style:google.maps.ZoomControlStyle.SMALL,
            position:google.maps.ControlPosition.RIGHT_TOP
        },
        //Allow map type choiuce, but move to bottom left
        mapTypeControl:true,
        mapTypeControlOptions: {
            position:google.maps.ControlPosition.TOP_RIGHT   
        },
        //Turn off pan control - it clutters map and users can drag if needed
        panControl:false,
        //turn of street view controller
        streetViewControl:false,
        //Options:ROADMAP/SATELLITE/HYBRID/TERRAIN   
        mapTypeId:google.maps.MapTypeId.ROADMAP
    };

    map = new google.maps.Map(document.getElementById("GoogleMap"), mapProp);
    
    downloadUrl("index.php?Page_Type=Site_Geo_xml", function(data) {
        var markers = [];
        var xmlMarkers = data.documentElement.getElementsByTagName("Site");
        for (var i = 0; i < xmlMarkers.length; i++) {
            var latlng = new google.maps.LatLng(parseFloat(xmlMarkers[i].getAttribute("Latitude")),
                                  parseFloat(xmlMarkers[i].getAttribute("Longitude")));
            var shortName = xmlMarkers[i].getAttribute("ShortName");
            var officialName = xmlMarkers[i].getAttribute("OfficialName");
            var url = xmlMarkers[i].getAttribute("PortalURL");
            var description = xmlMarkers[i].getAttribute("Description");
            //Add a line break if the description actualy has anything in it
            if (description.length!==0){
                description += "<br />";
            }
            var info = "<b>" + shortName + "</b> ("+ officialName + ")<br />" + description + "<a href=\"" + url + "\">View site</a>";
            var marker = createMarker(info, latlng);
            markers.push(marker);
        }
        var markerCluster = new MarkerClusterer(map, markers);
   });
}

function createMarker(description, latlng) {
    var marker = new google.maps.Marker({position: latlng, map: map});
    google.maps.event.addListener(marker, "click", function() {
        if (infowindow) infowindow.close();
        infowindow = new google.maps.InfoWindow({content: description});
        infowindow.open(map, marker);
    });

    return marker;
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
