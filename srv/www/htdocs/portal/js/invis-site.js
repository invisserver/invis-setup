/*
 * js/invis-site.js v1.1
 * site building an general portal wide functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

// Global
var minPwdLength = 0; // disabled, enable in config
var minPwdStrength = 0; // disabled, enable in config, 0 - 100%
 
// initial setup

function init () {
	lightbox.hide();
	window.onresize = lightbox.update;
	initUserblock();
	minPwdLength = $('user_pw_min_length').value;
	minPwdStrength = $('user_pw_min_strength').value;
}

//
// site building and response
//
function initUserblock() {
	$('userblock').innerHTML = "";
	
	var cookie = invis.getCookie('invis');
	if (cookie != null) {
		cookie = cookie.evalJSON();
		var user_string = cookie.displayname;
		if (user_string == null) user_string = cookie.uid;
		
		var a_profil = new Element("a").update("Einstellungen");
		a_profil.setStyle({'cursor': 'pointer'});
		a_profil.observe("click", showProfile);
		$('userblock').insert(a_profil);
		
		$('userblock').insert('<span class="spacer">|</span>');
		
		var a_logout = new Element("a").update("Abmelden");
		a_logout.setStyle({'cursor': 'pointer'});
		a_logout.observe("click", doLogout);
		$('userblock').insert(a_logout);
		
		$('userblock').insert("<br /><b>Benutzer:</b> <i>" + user_string + "</i>");
		if (cookie.PWD_EXPIRE < 1)
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort <b style='color: #ff0000;'>ist abgelaufen!</b></span>");
		else if (cookie.PWD_EXPIRE <= 3)
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort läuft in <b style='color: #ff0000;'>" + cookie.PWD_EXPIRE + "</u> Tagen ab</span>");
		else
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort läuft in " + cookie.PWD_EXPIRE + " Tagen ab</span>");
	}
	else {
		var a = new Element("a").update("Anmelden");
		a.setStyle({'cursor': 'pointer'});
		a.observe("click", showLogin);
		$('userblock').insert(a);
	}

}

function showLogin(event) {
	lightbox.show(300, true);
	var div = new Element('div', {'id': 'login-block'});
	var div_cancel = new Element('div', {'class': 'cancel'}).update('x');
	div.insert(div_cancel);
	div_cancel.observe('click',
		function (event) {
			invis.deleteCookie('invis-login');
			lightbox.hide();
		}
	);
	
	div.insert(new Element('div', {'class': 'section-title center'}).update('invis Login'));
	
	var str = '<form onsubmit="doLogin(); return false;" autocomplete="off"><table cellspacing="0" cellpadding="0">' +
				'<tr><td class="label">login</td><td class="input"><input id="login_user" /></td></tr>' +
				'<tr><td class="label">passwort</td><td class="input"><input type="password" id="login_pwd" /></td></tr>' +
				'<tr><td colspan="2"><button type="submit">Anmelden</button></td></tr>' +
			'</table></form>';
	div.insert(str);
	
	div.insert(new Element('div', {'id': 'login-message'}));
	
	lightbox.getContent().insert(div);
	lightbox.update();
	$('login_user').focus();
}

function doLogin() {
	var uid = $('login_user');
	var pwd = $('login_pwd');
	lightbox.setWaitStatus(true);
	invis.setCookie("invis-login", $H({uid: uid.value, pwd: pwd.value}).toJSON(), 0.1);
	var myAjax = new Ajax.Request(
		"script/login.php",
		{
			method: 'post',
			onComplete: userLoginResponse
		}
	);
}

function loginComplete() {
	lightbox.hide();
	location.reload(true);
}

function doLogout(event) {
	invis.deleteCookie('invis');
	location.reload(true);
}

function userLoginResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.status == 200)  {
		lightbox.setStatus("<span class='green'>Anmeldung erfolgreich!</span>");
		invis.deleteCookie('invis-login');
		invis.setCookie('invis', request.responseText);
		window.setTimeout("loginComplete()", 1000);
	} else {
		lightbox.setStatus("<span class='red'>Anmeldung fehlgeschlagen!</span>");
	}
}

function userReLoginResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.status == 200)  {
		invis.deleteCookie('invis-login');
		invis.setCookie('invis', request.responseText);
		initUserblock();
	} else {
		lightbox.setStatus("<span class='red'>Erneute Anmeldung fehlgeschlagen!</span>");
	}
}

function showProfile(event) {
	lightbox.show(400, 400, true);
	lightbox.setWaitStatus(true);
	var data = invis.getCookie('invis').evalJSON();
	invis.request('script/ajax.php', showProfileResponse, {c: 'user_detail', u: data.uid});
}

function showProfileResponse(request) {
	lightbox.setWaitStatus(false);
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerprofil'));

	var data = request.responseText.evalJSON();
	
	// editable attributes
	var rows = $H({
					'uid': false,
					'uidnumber': false,
					'displayname': true,
					'givenname': true,
					'sn': true,
					'userpassword': true
				});
	
	// attribute description
	var row_names = $H({
					'uid': 'Login',
					'uidnumber': 'UID',
					'displayname': 'Anzeigename',
					'userpassword': 'Passwort',
					'sn': 'Nachname',
					'givenname': 'Vorname'
				});
	lightbox.setData(new DetailStorage(request.responseText.evalJSON(), rows));
	
	var table = new Element('table', {'id': 'profile-table', 'cellpadding': '2', 'cellspacing': '5'});
	lightbox.getContent().insert(table);
	
	rows.each(
		function (item) {
			//table.insert('<tr><th class="description">' + row_names.get(item.key) + '</th><td class="editable">' + data[item.key] + '</td></tr>');
			//*
			var tr = new Element('tr');
			var th = new Element('th');
			
			th.update(row_names.get(item.key));
			th.addClassName('description');
			
			// description
			var td = new Element('td');
			td.writeAttribute('key', item.key)
			if (item.key == 'userpassword') {
				var btn = new Element('button', {'id': 'btn_change_pw'}).update('Passwort ändern');
				btn.observe('click', profileRequestPasswordChange);
				td.insert(btn);
			} else {
				td.update(data[item.key]);
			}
			
			// value
			if (item.value == false)
				td.addClassName('nochange');
			else {
				td.addClassName('editable');
				if (item.key != 'userpassword') td.observe('click', lightbox.inputBoxNew.bind(lightbox));
			}
			
			tr.insert(th);
			tr.insert(td);
			table.insert(tr);
			//*/
		}
	);
	
	//lightbox.getContent().insert(new Element('div', {'id': 'lightbox_buttons'}));
	
	var btn_save = new Element('button').update('Speichern');
	btn_save.observe('click', function(e) {
		lightbox.setWaitStatus(true);
		invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
		invis.request('script/ajax.php', profileModResponse, {c: 'user_mod', u: data['uid']});
	});
	lightbox.addButton(btn_save);
	
	var btn_cancel = new Element('button').update('Abbrechen');
	btn_cancel.observe('click', lightbox.hide);
	lightbox.addButton(btn_cancel);
	lightbox.update();
}

function profileModResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.responseText == '0') {
		lightbox.hide();
	} else {
		lightbox.setStatus('Profil konnte nicht geändert werden!<br />' + request.responseText);
	}
}

function profileRequestPasswordChange(event) {
	var node = event.target.parentNode;

	var btn_accept = new Element('button').update('Ändern');
	var btn_cancel = new Element('button').update('Abbrechen');
	
	btn_accept.observe('click',
		function (event) {
			var secret = $('input_change_pw').value;
			var confirm = $('input_change_pw_confirm').value;
			var uid = invis.getCookie('invis').evalJSON().uid;
			
			if (secret != confirm) {
				lightbox.setStatus("<span class='red'>Passwörter stimmen nicht überein!</span>");
				lightbox.update();
				return;
			}
			
			if (secret.length < minPwdLength) {
				lightbox.setStatus("<span class='red'>Passwort ist zu kurz! Mindestlänge ist " + minPwdLength + " Zeichen!</span>");
				lightbox.update();
				return;
			}
			
			if ((minPwdStrength > 0) && (uid == secret)) {
				lightbox.setStatus("<span class='red'>Passwort darf nicht dem Benutzernamen entsprechen!</span>");
				lightbox.update();
				return;
			}
			
			if (chkPass(secret, minPwdLength) < minPwdStrength) {
				lightbox.setStatus("<span class='red'>Passwort ist zu einfach! Bitte Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen verwenden!</span>");
				lightbox.update();
				return;
			}
			
			var ssha = invis.ssha(secret);
			var md4 = invis.md4(secret);
			
			lightbox.setWaitStatus(true);
			invis.setCookie('invis-request', $H({'userpassword': ssha, 'sambantpassword': md4}).toJSON());
			invis.request('script/ajax.php', 
				function(request) {
					lightbox.setWaitStatus(false);
					if (request.responseText == "0") {
						table.remove();
						$('btn_change_pw').show();
						lightbox.setStatus("Passwort wurde geändert!");
						lightbox.setWaitStatus(true);
						invis.setCookie("invis-login", $H({uid: uid, pwd: secret}).toJSON(), 0.1);
						var myAjax = new Ajax.Request(
							"script/login.php",
							{
								method: 'post',
								onComplete: userReLoginResponse
							}
						);
					} else {
						lightbox.setStatus("Passwort konnte nicht geändert werden!<br>" + request.responseText);
					}
					lightbox.update();
				},
				{c: 'user_mod', u: invis.getCookie('invis').evalJSON().uid}
			);
		}
	);
	
	btn_cancel.observe('click', function (event)
		{
			table.remove();
			$('btn_change_pw').show();
		}
	);
	
	var table = new Element('table');
	
	var tr1 = new Element('tr');
	var tr2 = new Element('tr');
	var tr3 = new Element('tr');
	
	var td1_1 = new Element('td');
	var td1_2 = new Element('td', {'class': 'input-description'});
	var td2_1 = new Element('td');
	var td2_2 = new Element('td', {'class': 'input-description'});
	
	var td3 = new Element('td', {'colspan': 2});
	
	td1_1.insert('<input type="password" id="input_change_pw" />');
	td1_2.insert('neues Passwort');
	
	td2_1.insert('<input type="password" id="input_change_pw_confirm" />');
	td2_2.insert('Passwort bestätigen');
	
	td3.insert(btn_accept);
	td3.insert(btn_cancel);
	
	tr1.insert(td1_1);
	tr1.insert(td1_2);
	
	tr2.insert(td2_1);
	tr2.insert(td2_2);
	
	tr3.insert(td3);
	
	table.insert(tr1);
	table.insert(tr2);
	table.insert(tr3);
	
	$('btn_change_pw').hide();
	node.insert(table);
	
	lightbox.update();
}
