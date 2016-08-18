/** 
CSS BROWSER DETECTOR 1.0.3
By Rodolfo Berrios <inbox@rodolfoberrios.com>

Highly inspired on the work of Rafael Lima http://rafael.adm.br/css_browser_selector/
Powered by this Quirks's guide http://www.quirksmode.org/js/detect.html

--

Usage: Simply include it on your HTML and once loaded, it will add something like "chrome chrome20 windows"
to the HTML html tag (document). So you can switch CSS styles using anidation like this:

.ie7 { *display: inline; }

It also blinds helpers like is_firefox() to detect firefox, or is_chrome(20) to detect if the browser is chrome 20.
You can also use something like if(is_ie() && get_browser_version() >= 9) to detect IE9 and above.

get_browser() returns the browser name
get_broser_version() returns the browser version
get_browser_os() return the operating system
	
**/
var BrowserDetect = {
	init: function(){
		this.browser = this.searchString(this.dataBrowser);
		this.version = this.searchVersion(navigator.userAgent) || this.searchVersion(navigator.appVersion);
		this.shortversion = this.browser+this.version;
		this.OS = this.searchString(this.dataOS);
	},

	searchString: function(data){
		for (var i=0; i < data.length; i++)   
		{
			var dataString = data[i].string;
			this.versionSearchString = data[i].subString;

			if(dataString.indexOf(data[i].subString) != -1){
				return data[i].identity;
			}
		}
	},

	searchVersion: function(dataString){
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},

	dataBrowser: [
		{ 
			string: navigator.userAgent,
			subString: "Chrome", 
			identity: "chrome" 
		},
		{ 
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "ie"
		},
		{ 
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "firefox"
		},
		{ 
			string: navigator.userAgent,
			subString: "Safari",
			identity: "safari"
		},
		{
			string: navigator.userAgent,
			subString: "Opera",
			identity: "opera"
		}
	],
	
	dataOS: [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "osx"
		},
		{
			string: navigator.userAgent,
			subString: "iPhone",
			identity: "ios"
		},
		{
			string: navigator.userAgent,
			subString: "iPad",
			identity: "ios"
		},
		{
			string: navigator.userAgent,
			subString: "iPod",
			identity: "ios"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "linux"
		}
	]

};
BrowserDetect.init();

document.documentElement.className += " " + BrowserDetect.browser + " " + BrowserDetect.shortversion + " " + BrowserDetect.OS;

function is_browser(agent, version){
	if(agent == BrowserDetect.browser){
		return typeof version !== "undefined" ? version == BrowserDetect.version : true;
	} else {
		return false;
	}
}

function get_browser(){
	return BrowserDetect.browser;
}

function get_browser_version(){
	return BrowserDetect.version;
}

function get_browser_os(){
	return BrowserDetect.OS;
}

// Generate is_browser() functions
for(var i=0; i<BrowserDetect.dataBrowser.length; i++){
	eval('function is_'+BrowserDetect.dataBrowser[i].identity+'(version) { return is_browser("'+BrowserDetect.dataBrowser[i].identity+'", version); }');
}
// Generate is_os() functions
for(var i=0; i<BrowserDetect.dataOS.length; i++){
	eval('function is_'+BrowserDetect.dataOS[i].identity+'() { return "'+BrowserDetect.dataOS[i].identity+'" == "'+BrowserDetect.OS+'"; }');	
}