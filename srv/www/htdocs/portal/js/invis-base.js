/*
 * js/invis-base.js v1.0
 * base classes and methods
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
 
var InvisBase = Class.create({
	setCookie : function (cookie_name, value, hours) {
		var expires = "";
		if (hours) {
			var date = new Date();
			date.setTime( date.getTime() + ( hours * 60 * 60 * 1000 ) );
			expires = "; expires=" + date.toGMTString();
		}
		//document.cookie = cookie_name + "=" + escape(value) + expires + "; path=/";
		document.cookie = cookie_name + "=" + this.urlEncode(value) + expires + ';path=/';
	},

	getCookie : function (cookie_name) {
		cookie_name = cookie_name + "=";
		var s = document.cookie.split(';');
		for(var i = 0; i < s.length; i++) {
			//var c = unescape(s[i]);
			var c = this.urlDecode(s[i]);
			while (c.charAt(0) == ' ') c = c.substring(1, c.length);
			if (c.indexOf(cookie_name) == 0) return c.substring(cookie_name.length, c.length);
		}
		return null;
	},

	deleteCookie : function (cookie_name) {
		this.setCookie(cookie_name, "", -1);
	},

	urlEncode : function (str) {
		// http://kevin.vanzonneveld.net
		// http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urlencode/        

		var histogram = {}, tmp_arr = [];
		var ret = str.toString();
		
		var replacer = function(search, replace, str) {
		    var tmp_arr = [];
		    tmp_arr = str.split(search);
		    return tmp_arr.join(replace);
		};
		
		// The histogram is identical to the one in urldecode.
		histogram["'"]   = '%27';
		histogram['(']   = '%28';
		histogram[')']   = '%29';
		histogram['*']   = '%2A';
		histogram['~']   = '%7E';
		histogram['!']   = '%21';
		histogram['%20'] = '+';
		
		// Begin with encodeURIComponent, which most resembles PHP's encoding functions
		ret = encodeURIComponent(ret);
		
		for (search in histogram) {
		    replace = histogram[search];
		    ret = replacer(search, replace, ret) // Custom replace. No regexing
		}
		
		// Uppercase for full PHP compatibility
		return ret.replace(/(\%([a-z0-9]{2}))/g, function(full, m1, m2) {
		    return "%"+m2.toUpperCase();
		});
		
		return ret;
	},
	
	urlDecode : function (str) {
		// http://kevin.vanzonneveld.net
		// http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urldecode/
		
		var histogram = {};
		var ret = str.toString();
		
		var replacer = function(search, replace, str) {
		    var tmp_arr = [];
		    tmp_arr = str.split(search);
		    return tmp_arr.join(replace);
		};
		
		// The histogram is identical to the one in urlencode.
		histogram["'"]   = '%27';
		histogram['(']   = '%28';
		histogram[')']   = '%29';
		histogram['*']   = '%2A';
		histogram['~']   = '%7E';
		histogram['!']   = '%21';
		histogram['%20'] = '+';
		
		for (replace in histogram) {
		    search = histogram[replace]; // Switch order when decoding
		    ret = replacer(search, replace, ret) // Custom replace. No regexing   
		}
		
		// End with decodeURIComponent, which most resembles PHP's encoding functions
		ret = decodeURIComponent(ret);
		
		return ret;
	},
	
	// user-login
	// fetch uid/pass from login-form, paste to cookie and 
	// request login through AJAX call
	loginUser : function () {
		var uid = $('login');
		var pwd = $('passwort');
		
		//
		
		//this.setCookie("invis", uid.value + "/" + pwd.value, 0.1);
		this.setCookie("invis", $H({uid: uid.value, pwd: pwd.value}).toJSON(), 0.1);
		
		var myAjax = new Ajax.Request(
			"script/login.php", {
				method: 'post',
				onComplete: userLoginResponse
			}
		);
	},
	
	// generic request
	request : function(url, handler, param) {
		var myAjax = new Ajax.Request(
			url, {
				method: 'post',
				parameters: param,
				onComplete: handler
			}
		);
	},
	
	// requests user list
	requestUserList : function(handler) {
		var myAjax = new Ajax.Request(
			"script/ajax.php",
			{
				method: 'post',
				parameters: {action: 'user_list'},
				onComplete: handler
			}
		);
	},
	
	// request details for specific user
	requestUserDetails : function(uid, handler) {
		var myAjax = new Ajax.Request(
			"script/ajax.php",
			{
				method: 'post',
				parameters: {c: 'user_detail', u: uid},
				onComplete: handler
			}
		);
	},
	
	// requests group list
	requestGroupList : function(handler) {
		var myAjax = new Ajax.Request(
			"script/ajax.php",
			{
				method: 'post',
				parameters: {action: 'group_list'},
				onComplete: handler
			}
		);
	},
	
	// request details for specific group
	requestGroupDetails : function(gidnumber, handler) {
		var myAjax = new Ajax.Request(
			"script/ajax.php",
			{
				method: 'post',
				parameters: {action: 'group_detail', details: gidnumber},
				onComplete: handler
			}
		);
	},
	
	// modify an LDAP entry
	modifyEntry : function(dn, attributes, handler) {
		var myAjax = new Ajax.Request(
			"script/ajax.php",
			{
				method: 'post',
				parameters: {action: 'user_mod', basedn: dn, details: attributes.toJSON()},
				onComplete: handler
			}
		);
	},
	
	// generate SSHA key (LDAP compatible)
	ssha : function (secret) {
		var salt = this.randomSalt();
		return "{SSHA}" + Base64.encode(this.pack(SHA1(secret + salt)) + salt);
	},
	
	// generate MD4 key (SamabNT compatible)
	md4 : function (secret) {
		return MD4.hex_md4(secret).toUpperCase();
	},
	
	// generate random 4 byte
	randomSalt : function () {
		var n = 4;
		var salt = "";
		for (i = 0; i < n; i++) {
			salt += String.fromCharCode(Math.floor( Math.random() * 256 ));
		}
		return salt;
	},

	// works like PHPs pack('H*', str);
	pack : function (str) {
		var packed = "";
		var i = 0;
		while (i < str.length) {
			var h1 = str[i];
			var h2 = str[i+1];
			if (h2 == null) h2 = "0";
			i += 2;
			packed += unescape("%" + h1 + h2);
		}
		return packed;
	},
	
	//
	challengePassword : function (challenge, secret) {
		var decode = Base64.decode(secret.substr(6));
		var salt = decode.substr(decode.length - 4);
		var hash = decode.substr(0, decode.length - 4);
		
		challenge = this.pack(SHA1(challenge + salt));
		
		return (challenge == hash);
	}
	/*
	//
	// INPUT BOX SAVER
	//
	
	INPUT_TEMP : null,
	
	// generate input box
	inputBoxNew : function (event) {
		var node = event.element();
		
		// temporarly store old value
		if (node.firstChild != null)
			this.INPUT_TEMP = node.firstChild.nodeValue;
		else
			this.INPUT_TEMP = '';
		node.innerHTML = "";
		
		// remove event handler
		node.stopObserving('click');
		
		// create new input box
		var input_box = new Element('input');
		input_box.value = this.INPUT_TEMP;
		
		// set input handler
		// .bind(this) is needed for keeping 'this' referencing the invis object
		input_box.observe('blur', this.inputBoxLeave.bind(this));
		input_box.observe('keyup', this.inputBoxLeave.bind(this));
		
		// insert into page and set focus
		node.insert(input_box);
		input_box.focus();
		input_box.select();
	},

	// value evaluation on input box leave
	inputBoxLeave : function (event) {
		if (event.type == 'keyup' && !(event.keyCode == 13 || event.keyCode == 27)) {
			return null;
		}
	
		var node = event.element();
		node.stopObserving('blur');
		node.stopObserving('keyup');
	
		// cancel editing (escape)
		if (event.type == 'keyup' && event.keyCode == 27) {
			node.value = this.INPUT_TEMP;
			this.INPUT_TEMP = null;
		}
	
		node.parentNode.insert(node.value);
		node.parentNode.observe('click', this.inputBoxNew.bind(this));
		Element.remove(node);
	}
	*/
});

/*
 * lightbox class with utility methods
 */
var Lightbox = Class.create({
	initialize : function () {
		this.clear_flags = new Array();
		this.buttons = new Array();
		this.data = null;
	},
	
	setData : function (data) {
		this.data = data;
	},
	
	show : function (width, clear) {
		if (clear) {
			$('lightbox-title').innerHTML = '';
			$('lightbox-content').innerHTML = '';
			$('lightbox-status').innerHTML = '';
			$('lightbox-buttons').innerHTML = '';
			$('lightbox-wait').innerHTML = '';
			this.clear_flags.clear();
			this.buttons.clear();
		}
		$('overlay').show();
		$('lightbox').show();
		$('lightbox').setStyle({'width': width+'px'});//, 'height': height+'px'});
		this.update();
	},
	
	hide : function () {
		$('overlay').hide();
		$('lightbox').hide();
	},
	
	getContent : function () {
		return $('lightbox-content');
	},
	
	// only makes sense if a 'description : key : value' table is used
	getHash : function() {
		return this.data.getHash();
	},
	
	update : function () {
		var dim = document.viewport.getDimensions();
		
		var x = (dim.width - $('lightbox').getWidth()) / 2;
		var y = (dim.height - $('lightbox').getHeight()) / 2;
		
		$('lightbox').setStyle({'top': y + 'px', 'left': x + 'px'});
	},
	
	setTitle : function (title) {
		$('lightbox-title').update(title);
	},
	
	setStatus : function (text, timeout) {
		if (!timeout) timeout = 5;
		this.clear_flags.push(true);
		$('lightbox-status').update(text);
		this.update();
		window.setTimeout(this.clearStatus, timeout * 1000);
	},
	
	setWaitStatus : function (flag, txt) {
		if (flag == true) {
			if (!txt) txt = 'Bitte warten ...';
			$('lightbox-wait').update('<div align="center"><table><tr><td valign="middle" align="right"><img src="images/ajax-loader.gif" /></td><td valign="middle" align="left">' + txt + '</td></tr></table></div>');
		} else {
			$('lightbox-wait').innerHTML = '';
		}
		this.update();
	},
	
	clearStatus : function () {
		lightbox.clear_flags.pop();
		if (lightbox.clear_flags.size() > 0) {
			return;
		} else {
			$('lightbox-status').update('');
			lightbox.update();
		}
	},
	
	addButton : function (btn) {
		this.buttons.push(btn);
		$('lightbox-buttons').insert(btn);
		this.update();
	},
	
	// generate input box
	inputBoxNew : function (event) {
		var node = event.element();
		
		// temporarly store old value
		if (node.firstChild != null)
			this.INPUT_TEMP = node.firstChild.nodeValue;
		else
			this.INPUT_TEMP = '';
		node.innerHTML = "";
		
		// remove event handler
		node.stopObserving('click');
		
		// create new input box
		var input_box = new Element('input');
		if (node.readAttribute('key') == 'userpassword') {
			input_box.writeAttribute('type', 'password');
		} else
			input_box.value = this.INPUT_TEMP;
		
		// set input handler
		// .bind(this) is needed for keeping 'this' referencing the invis object
		input_box.observe('blur', this.inputBoxLeave.bind(this));
		input_box.observe('keyup', this.inputBoxLeave.bind(this));
		
		// insert into page and set focus
		node.insert(input_box);
		input_box.focus();
		input_box.select();
	},

	// value evaluation on input box leave
	inputBoxLeave : function (event) {
		if (event.type == 'keyup' && !(event.keyCode == 13 || event.keyCode == 27)) {
			return null;
		}
	
		var node = event.element();
		node.stopObserving('blur');
		node.stopObserving('keyup');
	
		// cancel editing (escape)
		if (event.type == 'keyup' && event.keyCode == 27) {
			node.value = this.INPUT_TEMP;
			this.INPUT_TEMP = null;
		} else {
			var key = node.parentNode.readAttribute('key');
			
			if (key == 'userpassword') {
				var ssha = invis.ssha(node.value);
				var md4 = invis.md4(node.value);
				node.value = ssha;
				this.data.set('sambantpassword', md4);
			}
			this.data.set(key, node.value);
		}
		
		node.parentNode.insert(node.value);
		node.parentNode.observe('click', this.inputBoxNew.bind(this));
		Element.remove(node);
	}
});

/*
 * details lightbox object for data storing und hash-fetching
 */
var DetailStorage = Class.create({
	initialize : function (json, visible) {
		this.data = $H(json);
		var changed = $H();
		var edit = $H();
		var display = $H();
		
		// set all default
		this.data.keys().each(
			function (entry) {
				changed.set(entry, false);
				edit.set(entry, false);
				display.set(entry, false);
			}
		);
		
		// set visible&editable
		visible.each(
			function (entry) {
				display.set(entry.key, true);
				if (entry.value) edit.set(entry.key, true);
			}
		);
		
		this.changed = changed;
		this.edit = edit;
		this.display = display;
	},
	
	isVisible : function (key) {
		return this.display.get(key);
	},
	
	isEditable : function (key) {
		return this.edit.get(key);
	},
	
	get : function (key) {
		return this.data.get(key);
	},
	
	set : function (key, value) {
		// default value for LDAP if no value given
		if (value == null || value == '') {
			if (key != 'memberuid') value = '-';
			else value = $A();
		}
		this.data.set(key, value);
		this.changed.set(key, true);
	},
	
	getHash : function () {
		var h = $H();
		var tmp = this.data;
		
		this.changed.each(
			function (entry) {
				if (entry.value) h.set(entry.key, tmp.get(entry.key));
			}
		);
		
		return h;
	},
	
	getHashAll : function () {
		return this.data;
	}
});

var invis = new InvisBase();
var lightbox = new Lightbox();
