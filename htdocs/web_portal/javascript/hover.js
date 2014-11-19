// ** Popup box hover thingy (c)2005 by Ralph Capper
// ** Free for you to use - but please credit me - www.ralpharama.co.uk
// Start trapping mouse

if (document.layers) document.captureEvents(Event.MOUSEMOVE);
document.onmousemove=mtrack;
var ent; // Our floating div
var posx=0; // Our mouseX
var posy=0; // Our mouseY
var offsetX=16; // Offset X away from mouse
var offsetY=16; // Offset Y
var popUp = false; // Is it showing right now??!

// Run upon load
function init() {
// Set up div we will use to hover our text
ent = document.createElement("div");
// Change these to customise your popup
ent.style.color = "#000000";
ent.style.font = "normal xx-small verdana";
ent.style.padding = "1px 1px 1px 1px";
ent.style.background = "#fff588";
ent.style.border = "1px solid black";
// Don't, however, change these
ent.style.left = -100;
ent.style.top = -100;
ent.style.position = 'absolute';
ent.innerHTML = '';
ent.style.zIndex = 10;
document.getElementById("thepage").appendChild(ent);
}
// Keeps mouse x and y in posx and posy

function mtrack(e) {

if (popUp) {
if (!e) var e = window.event;
if (e.pageX || e.pageY) {
posx = e.pageX;
posy = e.pageY;
if(posy > document.body.clientHeight - ent.offsetHeight)
	posy = posy - ent.offsetHeight - 10;
if(posx > document.body.clientWidth - ent.offsetWidth)
	posx = posx - ent.offsetWidth - 10;

}
else if (e.clientX || e.clientY) {
posx = e.clientX + document.body.scrollLeft;
posy = e.clientY + document.body.scrollTop;
}
ent.style.left = posx + offsetX;
ent.style.top = posy + offsetY;

}
}
// Change floating div to correct text on mouseover

function doText(t, e) {
popUp = true;
ent.innerHTML = t;
}
// Change back to nothing

function doClear() {
popUp = false;
ent.style.left = -100;
ent.style.top = -100;
ent.innerHTML = "";
}