/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD4 Message
 * Digest Algorithm, as defined in RFC 1320.
 * Version 2.1 Copyright (C) Jerrad Pierce, Paul Johnston 1999 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */

var MD4 = {

	hexcase : 0,
	b64pad  : "",
	chrsz   : 16, // muß 16 sein für Windows MD4 hash (standard 8)

	hex_md4 : function (s) { return this.binl2hex(this.core_md4(this.str2binl(s), s.length * this.chrsz));},
	b64_md4 : function (s) { return this.binl2b64(this.core_md4(this.str2binl(s), s.length * this.chrsz));},
	str_md4 : function (s) { return this.binl2str(this.core_md4(this.str2binl(s), s.length * this.chrsz));},
	hex_hmac_md4 : function (key, data) { return this.binl2hex(this.core_hmac_md4(key, data)); },
	b64_hmac_md4 : function (key, data) { return this.binl2b64(this.core_hmac_md4(key, data)); },
	str_hmac_md4 : function (key, data) { return this.binl2str(this.core_hmac_md4(key, data)); },

	md4_vm_test : function () {
		return this.hex_md4("abc") == "a448017aaf21d8525fc10ae87aa6729d";
	},

	core_md4 : function (x, len) {
		x[len >> 5] |= 0x80 << (len % 32);
		x[(((len + 64) >>> 9) << 4) + 14] = len;

		var a =  1732584193;
		var b = -271733879;
		var c = -1732584194;
		var d =  271733878;

		for(var i = 0; i < x.length; i += 16) {
			var olda = a;
			var oldb = b;
			var oldc = c;
			var oldd = d;

			a = this.md4_ff(a, b, c, d, x[i+ 0], 3 );
			d = this.md4_ff(d, a, b, c, x[i+ 1], 7 );
			c = this.md4_ff(c, d, a, b, x[i+ 2], 11);
			b = this.md4_ff(b, c, d, a, x[i+ 3], 19);
			a = this.md4_ff(a, b, c, d, x[i+ 4], 3 );
			d = this.md4_ff(d, a, b, c, x[i+ 5], 7 );
			c = this.md4_ff(c, d, a, b, x[i+ 6], 11);
			b = this.md4_ff(b, c, d, a, x[i+ 7], 19);
			a = this.md4_ff(a, b, c, d, x[i+ 8], 3 );
			d = this.md4_ff(d, a, b, c, x[i+ 9], 7 );
			c = this.md4_ff(c, d, a, b, x[i+10], 11);
			b = this.md4_ff(b, c, d, a, x[i+11], 19);
			a = this.md4_ff(a, b, c, d, x[i+12], 3 );
			d = this.md4_ff(d, a, b, c, x[i+13], 7 );
			c = this.md4_ff(c, d, a, b, x[i+14], 11);
			b = this.md4_ff(b, c, d, a, x[i+15], 19);

			a = this.md4_gg(a, b, c, d, x[i+ 0], 3 );
			d = this.md4_gg(d, a, b, c, x[i+ 4], 5 );
			c = this.md4_gg(c, d, a, b, x[i+ 8], 9 );
			b = this.md4_gg(b, c, d, a, x[i+12], 13);
			a = this.md4_gg(a, b, c, d, x[i+ 1], 3 );
			d = this.md4_gg(d, a, b, c, x[i+ 5], 5 );
			c = this.md4_gg(c, d, a, b, x[i+ 9], 9 );
			b = this.md4_gg(b, c, d, a, x[i+13], 13);
			a = this.md4_gg(a, b, c, d, x[i+ 2], 3 );
			d = this.md4_gg(d, a, b, c, x[i+ 6], 5 );
			c = this.md4_gg(c, d, a, b, x[i+10], 9 );
			b = this.md4_gg(b, c, d, a, x[i+14], 13);
			a = this.md4_gg(a, b, c, d, x[i+ 3], 3 );
			d = this.md4_gg(d, a, b, c, x[i+ 7], 5 );
			c = this.md4_gg(c, d, a, b, x[i+11], 9 );
			b = this.md4_gg(b, c, d, a, x[i+15], 13);

			a = this.md4_hh(a, b, c, d, x[i+ 0], 3 );
			d = this.md4_hh(d, a, b, c, x[i+ 8], 9 );
			c = this.md4_hh(c, d, a, b, x[i+ 4], 11);
			b = this.md4_hh(b, c, d, a, x[i+12], 15);
			a = this.md4_hh(a, b, c, d, x[i+ 2], 3 );
			d = this.md4_hh(d, a, b, c, x[i+10], 9 );
			c = this.md4_hh(c, d, a, b, x[i+ 6], 11);
			b = this.md4_hh(b, c, d, a, x[i+14], 15);
			a = this.md4_hh(a, b, c, d, x[i+ 1], 3 );
			d = this.md4_hh(d, a, b, c, x[i+ 9], 9 );
			c = this.md4_hh(c, d, a, b, x[i+ 5], 11);
			b = this.md4_hh(b, c, d, a, x[i+13], 15);
			a = this.md4_hh(a, b, c, d, x[i+ 3], 3 );
			d = this.md4_hh(d, a, b, c, x[i+11], 9 );
			c = this.md4_hh(c, d, a, b, x[i+ 7], 11);
			b = this.md4_hh(b, c, d, a, x[i+15], 15);

			a = this.safe_add(a, olda);
			b = this.safe_add(b, oldb);
			c = this.safe_add(c, oldc);
			d = this.safe_add(d, oldd);
		}
		return Array(a, b, c, d);
	},

	md4_cmn : function (q, a, b, x, s, t) {
		return this.safe_add(this.rol(this.safe_add(this.safe_add(a, q), this.safe_add(x, t)), s), b);
	},
	
	md4_ff : function (a, b, c, d, x, s) {
		return this.md4_cmn((b & c) | ((~b) & d), a, 0, x, s, 0);
	},
	
	md4_gg : function (a, b, c, d, x, s) {
		return this.md4_cmn((b & c) | (b & d) | (c & d), a, 0, x, s, 1518500249);
	},
	
	md4_hh : function (a, b, c, d, x, s) {
		return this.md4_cmn(b ^ c ^ d, a, 0, x, s, 1859775393);
	},

	/*
	 * Calculate the HMAC-MD4, of a key and some data
	 */
	core_hmac_md4 : function (key, data) {
		var bkey = this.str2binl(key);
		if(bkey.length > 16) bkey = this.core_md4(bkey, key.length * this.chrsz);
		
		var ipad = Array(16), opad = Array(16);
		for(var i = 0; i < 16; i++) {
			ipad[i] = bkey[i] ^ 0x36363636;
			opad[i] = bkey[i] ^ 0x5C5C5C5C;
		}

		var hash = this.core_md4(ipad.concat(this.str2binl(data)), 512 + data.length * this.chrsz);
		return this.core_md4(opad.concat(hash), 512 + 128);
	},

	safe_add : function (x, y) {
		var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		return (msw << 16) | (lsw & 0xFFFF);
	},

	rol : function (num, cnt) {
		return (num << cnt) | (num >>> (32 - cnt));
	},

	str2binl : function (str) {
		var bin = Array();
		var mask = (1 << this.chrsz) - 1;
		for (var i = 0; i < str.length * this.chrsz; i += this.chrsz)
			bin[i>>5] |= (str.charCodeAt(i / this.chrsz) & mask) << (i%32);
		return bin;
	},

	binl2str : function (bin) {
		var str = "";
		var mask = (1 << this.chrsz) - 1;
		for(var i = 0; i < bin.length * 32; i += this.chrsz)
			str += String.fromCharCode((bin[i>>5] >>> (i % 32)) & mask);
		return str;
	},

	binl2hex : function (binarray) {
		var hex_tab = this.hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
		var str = "";
		for(var i = 0; i < binarray.length * 4; i++) {
			str += hex_tab.charAt((binarray[i>>2] >> ((i%4)*8+4)) & 0xF) +
				hex_tab.charAt((binarray[i>>2] >> ((i%4)*8  )) & 0xF);
		}
		return str;
	},

	binl2b64 : function (binarray) {
		var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
		var str = "";
		for(var i = 0; i < binarray.length * 4; i += 3) {
			var triplet = (((binarray[i   >> 2] >> 8 * ( i   %4)) & 0xFF) << 16)
						| (((binarray[i+1 >> 2] >> 8 * ((i+1)%4)) & 0xFF) << 8 )
						|  ((binarray[i+2 >> 2] >> 8 * ((i+2)%4)) & 0xFF);
			for(var j = 0; j < 4; j++) {
				if(i * 8 + j * 6 > binarray.length * 32) str += this.b64pad;
				else str += tab.charAt((triplet >> 6*(3-j)) & 0x3F);
			}
		}
		return str;
	}
}

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

var Base64 = {

	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
		
		// auskommentiert da SSHA ohne UTF-8 generiert wird
		//input = Base64._utf8_encode(input);

		while (i < input.length) {

			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}

			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

		}

		return output;
	},

	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;

		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

		while (i < input.length) {

			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCharCode(chr1);

			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}

		}

		output = Base64._utf8_decode(output);

		return output;

	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length ) {

			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}

/*
*
*  Secure Hash Algorithm (SHA1)
*  http://www.webtoolkit.info/
*
*/

function SHA1 (msg) {

	function rotate_left(n,s) {
		var t4 = ( n<<s ) | (n>>>(32-s));
		return t4;
	};

	function lsb_hex(val) {
		var str="";
		var i;
		var vh;
		var vl;

		for( i=0; i<=6; i+=2 ) {
			vh = (val>>>(i*4+4))&0x0f;
			vl = (val>>>(i*4))&0x0f;
			str += vh.toString(16) + vl.toString(16);
		}
		return str;
	};

	function cvt_hex(val) {
		var str="";
		var i;
		var v;

		for( i=7; i>=0; i-- ) {
			v = (val>>>(i*4))&0x0f;
			str += v.toString(16);
		}
		return str;
	};


	function Utf8Encode(string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	};

	var blockstart;
	var i, j;
	var W = new Array(80);
	var H0 = 0x67452301;
	var H1 = 0xEFCDAB89;
	var H2 = 0x98BADCFE;
	var H3 = 0x10325476;
	var H4 = 0xC3D2E1F0;
	var A, B, C, D, E;
	var temp;
	
	// auskommentiert da SSHA ohne UTF-8 generiert wird
	//msg = Utf8Encode(msg);

	var msg_len = msg.length;

	var word_array = new Array();
	for( i=0; i<msg_len-3; i+=4 ) {
		j = msg.charCodeAt(i)<<24 | msg.charCodeAt(i+1)<<16 |
		msg.charCodeAt(i+2)<<8 | msg.charCodeAt(i+3);
		word_array.push( j );
	}

	switch( msg_len % 4 ) {
		case 0:
			i = 0x080000000;
		break;
		case 1:
			i = msg.charCodeAt(msg_len-1)<<24 | 0x0800000;
		break;

		case 2:
			i = msg.charCodeAt(msg_len-2)<<24 | msg.charCodeAt(msg_len-1)<<16 | 0x08000;
		break;

		case 3:
			i = msg.charCodeAt(msg_len-3)<<24 | msg.charCodeAt(msg_len-2)<<16 | msg.charCodeAt(msg_len-1)<<8	| 0x80;
		break;
	}

	word_array.push( i );

	while( (word_array.length % 16) != 14 ) word_array.push( 0 );

	word_array.push( msg_len>>>29 );
	word_array.push( (msg_len<<3)&0x0ffffffff );


	for ( blockstart=0; blockstart<word_array.length; blockstart+=16 ) {

		for( i=0; i<16; i++ ) W[i] = word_array[blockstart+i];
		for( i=16; i<=79; i++ ) W[i] = rotate_left(W[i-3] ^ W[i-8] ^ W[i-14] ^ W[i-16], 1);

		A = H0;
		B = H1;
		C = H2;
		D = H3;
		E = H4;

		for( i= 0; i<=19; i++ ) {
			temp = (rotate_left(A,5) + ((B&C) | (~B&D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B,30);
			B = A;
			A = temp;
		}

		for( i=20; i<=39; i++ ) {
			temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B,30);
			B = A;
			A = temp;
		}

		for( i=40; i<=59; i++ ) {
			temp = (rotate_left(A,5) + ((B&C) | (B&D) | (C&D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B,30);
			B = A;
			A = temp;
		}

		for( i=60; i<=79; i++ ) {
			temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B,30);
			B = A;
			A = temp;
		}

		H0 = (H0 + A) & 0x0ffffffff;
		H1 = (H1 + B) & 0x0ffffffff;
		H2 = (H2 + C) & 0x0ffffffff;
		H3 = (H3 + D) & 0x0ffffffff;
		H4 = (H4 + E) & 0x0ffffffff;

	}

	var temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);

	return temp.toLowerCase();

}
