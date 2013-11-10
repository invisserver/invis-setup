/*
 * js/admintools.js v1.1
 * functions for user/group/host administration
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2010,2011,2012 Stefan Schäfer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: http://forum.invis-server.org
 */

//**********************************************************************
// page-global vars
//**********************************************************************

var PAGE_SIZE = 10;
var PAGE_CURRENT = 0;
var PAGED_DATA = null;
var PAGED_DATA_UNSORTED = null;
var ACCOUNT_TYPE = new Array('Administrator', 'Benutzer', 'Mailkonto', 'Gastkonto', 'Groupware-Benutzer');
var USERLIST_MAIL_FLAG = true;
var PINGER_FLAG = false;
var PINGER_REQUEST = [];

//**********************************************************************
// helper-functions and error reporting
//**********************************************************************

// dumps an error into an alert
function show_error(request) {
	alert(request);
}

//
// sort/filter/group paged data
//

// sorting function for an array of JSON user/group objects, grouped by TYPE and sorted by uid (alphabetical)
function mysort(a, b) {
	if (a.TYPE == b.TYPE)
		return (a['uid'] < b['uid']) ? -1 : (a['uid'] > b['uid'])? 1 : 0;
	else
		return a.TYPE - b.TYPE;
} 

// sorting function for an array of JSON group objects, sorted by cn (alphabetical, ignored case)
function groupsort(a, b) {
	return (a['cn'].toUpperCase() < b['cn'].toUpperCase()) ? -1 : (a['cn'].toUpperCase() > b['cn'].toUpperCase())? 1 : 0;
} 

// sorting function for an array of JSON host objects, sorted by IP
function hostsort(a, b) {
	var stA = a['dhcpstatements'].split(" ");
	var stB = b['dhcpstatements'].split(" ");
	if ((stA.length == 2) && (stB.length == 2))
	{
	    var ipA = stA[1].split(".");
	    var ipB = stB[1].split(".");
	    if ((ipA.length == 4) && (ipB.length == 4))
	    {
		var ipANum = 0;
		var ipBNum = 0;
		ipANum = (parseInt(ipA[0]) * 256 * 256 * 256) + (parseInt(ipA[1]) * 256 * 256) + (parseInt(ipA[2]) * 256) + parseInt(ipA[3]);
		ipBNum = (parseInt(ipB[0]) * 256 * 256 * 256) + (parseInt(ipB[1]) * 256 * 256) + (parseInt(ipB[2]) * 256) + parseInt(ipB[3]);
		return (ipANum < ipBNum) ? -1 : (ipANum > ipBNum) ? 1 : 0;
	    }
	    else
		return (a['dhcpstatements'] < b['dhcpstatements']) ? -1 : (a['dhcpstatements'] > b['dhcpstatements'])? 1 : 0;
	}
	else
	    return (a['dhcpstatements'] < b['dhcpstatements']) ? -1 : (a['dhcpstatements'] > b['dhcpstatements'])? 1 : 0;
} 

// filter paged data
function filterData(data) {
	if (data != null)
		PAGED_DATA_UNSORTED = data;
	
	PAGED_DATA = new Array();
	PAGED_DATA_UNSORTED.each(
		function (item) {
			if (item['TYPE'] != 2 || (item['TYPE'] == 2 && USERLIST_MAIL_FLAG))
				PAGED_DATA.push(item);
		}
	);
	
	PAGED_DATA.sort(mysort);
}

//**********************************************************************
// display lists
//**********************************************************************

// build userlist
function userListResponse(request) {
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	stopAllPingers();
	
//	PAGED_DATA = request.responseText.evalJSON().sortBy( function (item) {return item['uid'];} );
//	PAGED_DATA = request.responseText.evalJSON().sort(mysort);
	filterData(request.responseText.evalJSON());
	PAGE_CURRENT = 0;
	
	// header
	
	title.update('Benutzerliste:');
	
	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Login / UID</th><th class="type">Typ</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	populateUserList(null, 0);
	
	// add user button
	var node = new Element('table', {'onclick': 'userAdd();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/user.png" /></td><td style="vertical-align: middle;">Benutzer anlegen</td></tr>');
	content.insert(node);
}

// populate userlist
function populateUserList(event, page) {
	if (page == null) page = this.firstChild.nodeValue - 1;
	PAGE_CURRENT = page;
	
	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";
	
	// table with current page data
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var user = PAGED_DATA[i];
		
		var tr = new Element('tr');
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(user['uid']));
		td_name.insert(new Element('span', {'class': 'number'}).update(user['uidnumber']));
		
		var td_type = new Element('td');
		td_type.insert(ACCOUNT_TYPE[user.TYPE]);
		
		var td_delete = new Element('td', {'class': 'delete'});
		td_delete.insert(new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/ajax.php", userDetailsResponse, {c: "user_detail", u: "' + user['uid'] + '"});'}).update('<img src="images/edit_img.png" />'));
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('a', {'onclick': 'userDelete("' + user['uid'] + '");'}).update('<img src="images/delete_img.png" />'));
		
		tr.insert(td_name);
		tr.insert(td_type);
		tr.insert(td_delete);
		
		$('result-body').insert(tr);
	}
	
	// account type selector
	var check = new Element('input', {'type': 'checkbox'});
	check.checked = USERLIST_MAIL_FLAG;
	
	check.observe('click',
		function(e) {
			USERLIST_MAIL_FLAG = this.checked;
			filterData();
			populateUserList(null, 0);
		}
	);
	
	$('result-paging').insert(check);
	$('result-paging').insert(' Mail-Dummies einblenden<br/>');
	
	// paging links
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateUserList);
		$('result-paging').insert(a);
	}
}

// build grouplist
function groupListResponse(request) {
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	stopAllPingers();
	
	PAGED_DATA = request.responseText.evalJSON();
	PAGED_DATA.sort(groupsort);
	PAGE_CURRENT = 0;
	
	// header
	
	title.update('Gruppenliste:');
	
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	// 3 colums (name, type, delete)
	//content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Name / GID</th><th class="type">Typ</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	// 2 colums (name, delete)
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Name / GID</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	populateGroupList(null, 0);
	
	// add group button
	var node = new Element('table', {'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/group.png" /></td><td style="vertical-align: middle;">Gruppe anlegen</td></tr>');
	node.observe('click', function(){
			invis.request('script/ajax.php', groupAdd, {c: 'user_list_short'});
		}
	);
	content.insert(node);
}

// populate grouplist
function populateGroupList(event, page) {
	if (page == null) page = this.firstChild.nodeValue - 1;
	PAGE_CURRENT = page;
	
	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";
	
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateGroupList);
		$('result-paging').insert(a);
	}
	
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var item = PAGED_DATA[i];
		
		var tr = new Element('tr');
		
		// name
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(item.cn));
		td_name.insert(new Element('span', {'class': 'number'}).update(item.gidnumber));
		tr.insert(td_name);
		
		// type
		//var td_type = new Element('td');
		//tr.insert(td_type);
		
		// delete
		var td_delete = new Element('td', {'class': 'delete'});
		td_delete.insert(new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/ajax.php", groupDetailsResponse, {c: "group_detail", u: "' + item['cn'] + '"});'}).update('<img src="images/edit_img.png" />'));
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('a', {'onclick': 'groupDelete("' + item['cn'] + '");'}).update('<img src="images/delete_img.png" />'));
		tr.insert(td_delete);
		
		//td_delete.insert(new Element('a', {'onclick': 'invis.requestGroupDetails(' + item.gidnumber + ', groupDetailsResponse);'}).update('E'));
		//td_delete.insert(new Element('a', {'onclick': 'delete_group(' + item.gidnumber + ');'}).update('X'));
		
		$('result-body').insert(tr);
	}
}

//**********************************************************************
// show details
//**********************************************************************

// userdetails
function userDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerdetails'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="userMod(\'' + data['uid'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	//lightbox.addButton('<button onclick="tmpFunction(\'' + data['uid'] + '\', \'userbox_content\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
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
	
	$('userbox_content').insert(new Element('div', {'style': 'display: none;'}).update(data['dn']));
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div');
			value_div.update(data[item.key]);
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	lightbox.update();
}

// groupdetails
function groupDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON()[0];
	var users_group = request.responseText.evalJSON()[1];
	var users_not = request.responseText.evalJSON()[2];
	
	// in case we get NULL or just 1 entry
	if (!Object.isArray(users_group)) {
		var arr = $A();
		if (Object.isString(users_group)) arr.push(users_group);
		users_group = arr;
	}
		
	if (!Object.isArray(users_not)) {
		var arr = $A();
		if (Object.isString(users_not)) arr.push(users_not);
		users_not = arr;
	}
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Gruppendetails'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="groupMod(\'' + data['cn'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': false,
					'gidnumber': false,
					'displayname': true
				});
	
	var row_names = $H({"cn": "Name",
					"gidnumber": "GID",
					"displayname": "Anzeigename"
				});
	
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update(data[item.key]);
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	// grouplists table
	$('groupbox_content').insert('<table id="groupbox_table"><tr class="line"><td colspan="3" class="key">Gruppenmitglieder</td></tr><tr><td id="groupbox_left"></td><td id="groupbox_center"></td><td id="groupbox_right"></td></tr></table>');
	
	// user-in-group box
	var select_in = new Element('select', {'id': 'grouplist_in', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add group members
	users_group.each(
		function (user) {
			select_in.insert(new Element('option').update(user));
		}
	);
	$('groupbox_left').insert(select_in);
	
	// user-move arrows
	var arrow_in = new Element('img', {'src': 'images/arrow_left.png'});
	var arrow_out = new Element('img', {'src': 'images/arrow_right.png'});
	
	// observer methods for user-move arrows
	arrow_in.observe('click', function(event) {
		while ($('grouplist_out').selectedIndex >= 0) {
			var i = $('grouplist_out').selectedIndex;
			$('grouplist_out').options[i].selected = false;
			$('grouplist_in').insert($('grouplist_out').options[i]);
		}
		listSort($('grouplist_in'));
		updateMemberUID($('grouplist_in'));
	});
	
	arrow_out.observe('click', function(event) {
		while ($('grouplist_in').selectedIndex >= 0) {
			var i = $('grouplist_in').selectedIndex;
			$('grouplist_in').options[i].selected = false;
			$('grouplist_out').insert($('grouplist_in').options[i]);
		}
		listSort($('grouplist_out'));
		updateMemberUID($('grouplist_in'));
	});
	
	$('groupbox_center').insert(arrow_in);
	$('groupbox_center').insert(new Element('br'));
	$('groupbox_center').insert(arrow_out);
	
	
	// user-not-in-group box
	var select_not = new Element('select', {'id': 'grouplist_out', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add non-group members
	users_not.each(
		function (user) {
			select_not.insert(new Element('option').update(user));
		}
	);
	$('groupbox_right').insert(select_not);
	
	listSort($('grouplist_in'));
	listSort($('grouplist_out'));
	lightbox.update();
}

// sort a nodeList of <option> tags
function listSort(list) {
	var data = list.childNodes;
	var arr = new Array();
	
	for (var i = data.length - 1; i >= 0; i--) arr.push( data[i].remove() );
	
	// by numerical value
	//arr.sort(function (a, b) { return a.value - b.value; });
	
	// by textual representation
	arr.sort(function (a, b) {
		if (a.text == b.text) return 0;
		return (a.text < b.text)?-1:1;
	});
	arr.each(function (item) { list.insert(item); });
}

//
function hostDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('PC-Details'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="hostMod(\'' + data['cn'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	// editable attributes
	var rows = $H({
					'cn': true,
					'dhcpcomments': true,
					'dhcphwaddress': false,
					'dhcpstatements': false
				});
	
	// attribute description
	var row_names = $H({
					'cn': 'Name',
					'dhcpcomments': 'Standort',
					'dhcphwaddress': 'MAC',
					'dhcpstatements': 'IP'
				});
	
	$('userbox_content').insert(new Element('div', {'style': 'display: none;'}).update(data['dn']));
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div');
			if (item.key == 'dhcpstatements') {
				value_div.update(data[item.key].split(' ')[1]);
			} else if (item.key == 'dhcphwaddress') {
				var value = data[item.key].split(' ')[1].split(':');
				for (var i = 0; i < 6; i++) {
					var input = new Element('input', {'size': 2, 'maxlength': 2, 'style': 'width: 2em; text-align: center;'});
					input.value = value[i];
					value_div.insert(input);
					input.observe('blur', hostAddMAC);
					if (i < 5) value_div.insert(':');
				}
			} else {
				value_div.update(data[item.key]);
			}
			
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	lightbox.update();
}

// update memberuid data
function updateMemberUID(list) {
	var data = $A(list.childNodes);
	var arr = new Array();
	
	data.each(
		function (item) {
			arr.push(item.value);
		}
	);
	
	lightbox.data.set('memberuid', arr);
}

// entry modification request?
function doodat(request) {
	if (request == null) build_user_mod_request();
	else {
		lightbox.hide();
	} 
}

// create entry mod request
function build_user_mod_request() {
	var node = $('userbox_content');
	var dn = node.firstChild.textContent;
	
	var hash = $H();
	for (var i = 1; i < node.childNodes.length; i++) {
		var item = node.childNodes[i];
		var k = item.childNodes[1].textContent;
		var v = item.childNodes[2].textContent;
		hash.set(k, v);
	}

	request_user_mod(dn, hash.toJSON());
}

//
// DHCP/DNS STUFF
// 

function hostListResponse(request) {
	PAGED_DATA = request.responseText.evalJSON();
	PAGED_DATA.sort(hostsort);
	
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	//filterData(request.responseText.evalJSON());
	PAGE_CURRENT = 0;
	
	// header
	title.update('Hostliste:');

	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Ping</th><th class="name">Host</th><th class="name">MAC</th><th class="name">IP</th><th class="name">Typ</th><th class="name">Standort</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	populateHostList(null, 0);

	// add host button
	var node = new Element('table', {'onclick': 'hostAdd();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/host.png" /></td><td style="vertical-align: middle;">PC hinzufügen</td></tr>');
	content.insert(node);
	
	// discover hosts button
	//var node2 = new Element('table', {'onclick': 'hostDiscover();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/host.png" /></td><td style="vertical-align: middle;">PCs suchen</td></tr>');
	//content.insert(node2);
}

function populateHostList(event, page) {
	if (page == null) page = this.firstChild.nodeValue -1;
	PAGE_CURRENT = page;

	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";

	// Alle stoppen
	stopAllPingers();
	
	// Hostlist
	
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var id = "host_list_entry" + i;
		var host = PAGED_DATA[i];
		var ip = host['dhcpstatements'].split(' ');
		var mac = host['dhcphwaddress'].split(' ');
		// Location hinzugefuegt
		var location = host['location'];
		var tr = new Element('tr');
		
		if (PINGER_FLAG == true) {
		    var td_ping = new Element('td', {'id': id, 'style': 'vertical-align: middle; width: 16px;'}).update('<img src="images/ajax-loader.gif" width="16px" height="16px" />');
		    // Pinger
		    if (PINGER_REQUEST[i] == null)
		    {
			    PINGER_REQUEST[i] = new Ajax.PeriodicalUpdater(
			    id,
			    'script/ping.php',
			    { method: 'post', parameters: { ip: ip[1] }, frequency: 10, decay: 1}
			);
		    }
		} else {
		    var td_ping = new Element('td', {'id': id, 'style': 'vertical-align: middle; width: 16px;'}).update('<img src="images/cross_small.png" width="16px" height="16px" />');
		    if (PINGER_REQUEST[i])
		    {
		    	PINGER_REQUEST[i].stop();
		    	PINGER_REQUEST[i] = null;
		    }
		}
		
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(host['cn']));
		td_name.insert(host['PING']);
		
		var td_mac = new Element('td');
		td_mac.insert(mac[1]);
		
		var td_ip = new Element('td');
		td_ip.insert(ip[1]);
		
		var td_type = new Element('td');
		td_type.insert(host['TYPE']);
		
		// Versuch eine neue Spalte in die Tabelle einfuegen fuer den Wert: Standort
		// Array-Index "LOCATION" exitstiert noch nicht in der Array-Variable "host"
		var td_location = new Element('td');
		td_location.insert(host['dhcpcomments']);

		var td_delete = new Element('td', {'class': 'delete'});
		var node_edit = new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/ajax.php", hostDetailsResponse, {c: "host_detail", u: "' + host['cn'] + '"});'}).update('<img src="images/edit_img.png" />');
		td_delete.insert(node_edit);
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('span', {'onclick': 'hostDelete(\'' + host['cn'] + '\');'}).update('<img src="images/delete_img.png" />'));
		
		tr.insert(td_ping);
		tr.insert(td_name);
		tr.insert(td_mac);
		tr.insert(td_ip);
		tr.insert(td_type);
		// Spalte Location eingefuegt
		tr.insert(td_location);
		tr.insert(td_delete);
		
		$('result-body').insert(tr);
	}
	// host pinger selector
	var check = new Element('input', {'type': 'checkbox'});
	check.checked = PINGER_FLAG;
	check.observe('click',
		function(e) {
			PINGER_FLAG = this.checked;
			populateHostList(null, 0);
		}
	);

	$('result-paging').insert(check);
	$('result-paging').insert(' Ping-Test aktivieren<br/>');

	// table with current page data
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateHostList);
		$('result-paging').insert(a);
	}

	if (PINGER_FLAG == false) {
	    stopAllPingers();
	}
}

function stopAllPingers() {
    for (var i = 0; i < PINGER_REQUEST.length; i++)
    {
	if (PINGER_REQUEST[i])
	{
	    PINGER_REQUEST[i].stop();
	    PINGER_REQUEST[i] = null;
	}
    }
}

//
// DELETE USER / GROUP / HOST
//

function userDelete(uid) {
	if (confirm('Möchten Sie den Benutzer "' + uid + '" wirklich löschen?')) {
		var flag = (confirm('Verzeichnis /home/'+ uid + " löschen?"))?1:0;
		invis.request("script/ajax.php", userDeleteResponse, {c: 'user_delete', u: uid, t: flag});
	}
	
}
function userDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/ajax.php', userListResponse, {c: 'user_list'}); // reload user list
	else
		alert('Benutzer konnte nicht gelöscht werden!' + request.responseText);
}

function groupDelete(cn) {
	if (confirm('Möchten Sie die Gruppe "' + cn + '" wirklich löschen?'))
		invis.request("script/ajax.php", groupDeleteResponse, {c: "group_delete", u: cn});
}
function groupDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/ajax.php', groupListResponse, {c: 'group_list'}); // reload group list
	else
		alert('Gruppe konnte nicht gelöscht werden!' + request.responseText);
}

function hostDelete(cn) {
	if (confirm('Möchten Sie den PC "' + cn + '" wirklich löschen?'))
		invis.request("script/ajax.php", hostDeleteResponse, {c: 'host_delete', u: cn});
}
function hostDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/ajax.php', hostListResponse, {c: 'host_list'}); // reload host list
	else
		alert('PC konnte nicht gelöscht werden!' + request.responseText);
}

//
// MODIFY USER / GROUP / HOST
//

function userMod(uid) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/ajax.php', userModResponse, {c: 'user_mod', u: uid});
}
function userModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '0') lightbox.setStatus('Änderungen wurden gespeichert!');
	else {
		lightbox.setStatus("Änderungen konnte nicht gespeichert werden!<br>" + request.responseText);
	}
}

function groupMod(cn) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/ajax.php', groupModResponse, {c: 'group_mod', u: cn});
}
function groupModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '0') lightbox.setStatus('Änderungen wurden gespeichert!');
	else {
		lightbox.setStatus("Änderungen konnte nicht gespeichert werden!<br>" + request.responseText);
	}
}

function hostMod(cn) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/ajax.php', hostModResponse, {c: 'host_mod', u: cn});
}
function hostModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '0') {
		lightbox.setStatus('Änderungen wurden gespeichert!');
		window.setTimeout("invis.request('script/ajax.php', hostListResponse, {c: 'host_list'}); lightbox.hide();", 1000);
	}
	else {
		lightbox.setStatus("Änderungen konnte nicht gespeichert werden!<br>" + request.responseText);
	}
}
//
// ADD USER / GROUP / HOST
//

// show user add box
function userAdd() {
	var account_type = 0; // 0: user, 1: admin, 2: gast, 3: mail, 4: groupware
	lightbox.show(500, true);
	//var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerdetails'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	
	var tmp_btn = new Element('button').update('Speichern');
	tmp_btn.observe('click', function () {
		var uid = lightbox.data.get('uid');
		invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
		invis.request('script/ajax.php', userAddResponse, {c: 'user_create', u: uid, t: account_type});
	});
	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');
	
	// editable attributes
	var rows = $H({
					'uid': true,
//					'uidnumber': false,
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
	
	lightbox.setData(new DetailStorage('{}', rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute key
			line.insert(new Element('div', {'style': 'display: none;'}).update(item.key));
			// attribute editable
			line.insert(new Element('div', {'style': 'display: none;'}).update(item.value));
			
			// attribute value
			var value_div = new Element('div');
			value_div.update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Account-Typ'));
	var sel = new Element('select', {'style': 'width: 30%'});
	sel.insert(new Element('option', {'value': 0}).update('Benutzer'));
	sel.insert(new Element('option', {'value': 1}).update('Administrator'));
	sel.insert(new Element('option', {'value': 2}).update('Gastkonto'));
	sel.insert(new Element('option', {'value': 3}).update('Mailkonto'));
	sel.insert(new Element('option', {'value': 4}).update('Groupware-Benutzer'));
	sel.observe('change', function(e) { account_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('userbox_content').insert(line);
	
	lightbox.update();
}

function userAddRequest(type) {
	var uid = lightbox.data.get('uid');
	invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
	invis.request('script/ajax.php', userAddResponse, {c: 'user_create', u: uid, t: type});
}
function userAddResponse(request) {
	if (request.responseText == '0') {
		invis.request('script/ajax.php', userListResponse, {c: 'user_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('Benutzer konnte nicht erstellt werden!<br />' + request.responseText);
	}
}

// show user add box
function groupAdd(request) {
	lightbox.show(500, true);
	var users_not = request.responseText.evalJSON();
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Gruppendetails'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="groupAddRequest();">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': true,
					'displayname': true
				});
	
	var row_names = $H({"cn": "Name",
					"displayname": "Anzeigename"
				});
	
	lightbox.setData(new DetailStorage('{}', rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	// grouplists table
	$('groupbox_content').insert('<table id="groupbox_table"><tr class="line"><td colspan="3" class="key">Gruppenmitglieder</td></tr><tr><td id="groupbox_left"></td><td id="groupbox_center"></td><td id="groupbox_right"></td></tr></table>');
	
	// user-in-group box
	var select_in = new Element('select', {'id': 'grouplist_in', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	$('groupbox_left').insert(select_in);
	
	// user-move arrows
	var arrow_in = new Element('img', {'src': 'images/arrow_left.png'});
	var arrow_out = new Element('img', {'src': 'images/arrow_right.png'});
	
	// observer methods for user-move arrows
	arrow_in.observe('click', function(event) {
		while ($('grouplist_out').selectedIndex >= 0) {
			var i = $('grouplist_out').selectedIndex;
			$('grouplist_out').options[i].selected = false;
			$('grouplist_in').insert($('grouplist_out').options[i]);
		}
		listSort($('grouplist_in'));
		updateMemberUID($('grouplist_in'));
	});
	
	arrow_out.observe('click', function(event) {
		while ($('grouplist_in').selectedIndex >= 0) {
			var i = $('grouplist_in').selectedIndex;
			$('grouplist_in').options[i].selected = false;
			$('grouplist_out').insert($('grouplist_in').options[i]);
		}
		listSort($('grouplist_out'));
		updateMemberUID($('grouplist_in'));
	});
	
	$('groupbox_center').insert(arrow_in);
	$('groupbox_center').insert(new Element('br'));
	$('groupbox_center').insert(arrow_out);
	
	
	// user-not-in-group box
	var select_not = new Element('select', {'id': 'grouplist_out', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add non-group members
	users_not.each(
		function (user) {
			select_not.insert(new Element('option').update(user));
		}
	);
	$('groupbox_right').insert(select_not);
	
	listSort($('grouplist_in'));
	listSort($('grouplist_out'));
	lightbox.update();
}

function groupAddRequest() {
	var cn = lightbox.data.get('cn');
	invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
	invis.request('script/ajax.php', groupAddResponse, {c: 'group_create', u: cn});
}
function groupAddResponse(request) {
	if (request.responseText == '0') {
		invis.request('script/ajax.php', groupListResponse, {c: 'group_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('Benutzer konnte nicht erstellt werden!<br />' + request.responseText);
	}
}

// show host add box
function hostAdd() {
	var host_type = 0;
	
	lightbox.show(500, true);
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('PC hinzufügen'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);
	
	var tmp_btn = new Element('button').update('Speichern');
	tmp_btn.observe('click', function () {
		var data = lightbox.data.getHash();
		invis.setCookie('invis-request', data.toJSON());
		invis.request('script/ajax.php', hostAddResponse, {c: 'host_create', u: data.get('cn'), t: host_type})
	});
	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': true,
					// Eingabefeld location editierbar
					'location': true
				});
	
	var row_names = $H({"cn": "Name",
			// Eingabefeld Standort hinzugefuegt
			    "location": "Standort"
				});
	
	lightbox.setData(new DetailStorage('{}', rows));
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Typ'));
	var sel = new Element('select', {'style': 'width: 30%'});
	sel.insert(new Element('option', {'value': 0}).update('PC'));
	sel.insert(new Element('option', {'value': 1}).update('Drucker'));
	sel.insert(new Element('option', {'value': 2}).update('Server'));
	sel.insert(new Element('option', {'value': 3}).update('IP-Gerät'));
	sel.observe('change', function(e) { host_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('groupbox_content').insert(line);
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('MAC-Adresse'));
	for (var i = 0; i < 6; i++) {
		var input = new Element('input', {'size': 2, 'maxlength': 2, 'style': 'width: 2em; text-align: center;'});
		line.insert(input);
		input.observe('blur', hostAddMAC);
		if (i < 5) line.insert(':');
	}
	$('groupbox_content').insert(line);
	
	lightbox.update();
}

function hostAddMAC(e) {
	var node = e.target.parentNode;
	var mac = $A();
	var str = '';
	
	$A(node.childNodes).each(
		function (item) {
			if (item.tagName == 'INPUT') {
				var str = item.value.toLowerCase();
				value = parseInt(str, 16);
				if ((value >= 0 && value <= 255) || str == '') item.setStyle({backgroundColor: 'white'});
				else item.setStyle({backgroundColor: 'red'});
				mac.push(str);
			}
		}
	);
	
	for (var i = 0; i < mac.length; i++) {
		str += mac[i];
		if (i < mac.length - 1) str += ':';
	}
	lightbox.data.set('dhcphwaddress', 'ethernet ' + str);
}

function hostAddRequest() {
	lightbox.setWaitStatus(true);
	var data = lightbox.data.getHash();
	invis.setCookie('invis-request', data.toJSON());
	invis.request('script/ajax.php', hostAddResponse, {c: 'host_create', u: data.get('cn')})
}
function hostAddResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.responseText == '0') {
		invis.request('script/ajax.php', hostListResponse, {c: 'host_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('PC konnte nicht erstellt werden!<br />' + request.responseText);
	}
}

function hostDiscover() {
	lightbox.show(500, true);
	lightbox.setWaitStatus(true);
	invis.request('script/dhcpleases.php', hostDiscoverResponse, {});
}
function hostDiscoverResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON(true);
	
	lightbox.setTitle(new Element('div', {
		'class': 'section-title'
	}).update('PCs suchen'));
	
	var box = new Element('table', {
		'id': 'host_discover',
		'cellpadding': '0',
		'cellspacing': '0'
	});
	lightbox.getContent().insert(box);
	
	box.insert('<tr><th width="100px">MAC</th><th width="50px">Status</th><th width="50px"></th><th>Hinzufügen</th><th>Name</th><th>Typ</th></tr>');
	for (var i = 0; i < data.length; i++) {
		var item = data[i];
		box.insert('<tr><td>' + item['mac'] + '</td><td valign="middle" id="' + item['mac'] + '"><img src="images/ajax-loader.gif" width="16px" height="16px" /></td>' +
		'<td><input type="checkbox" /></td><td><input size="5" /></td><td><select size="1"><option>PC</option><option>Server</option><option>Drucker</option><option>IP-Gerät</option></select></td></tr>');
		new Ajax.PeriodicalUpdater(item['mac'], 'script/ping.php', {
			method: 'post',
			parameters: {
				ip: item['ip']
			},
			frequency: 10,
			decay: 1
		});
	}
	if (data.length == 0) box.insert('<tr><td colspan="5"><b>Keine Rechner gefunden.</b></td></tr>');
	
	var tmp_btn = new Element('button').update('Hinzufügen2');
	tmp_btn.observe('click', function () {
		lightbox.setWaitStatus(true);
		var data = lightbox.data.getHash();
		invis.setCookie('invis-request', data.toJSON());
		invis.request('script/ajax.php', hostDiscover, {c: 'host_create', u: data.get('cn'), t: host_type})
	});
	
	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Beenden</button>');
	lightbox.update();
}
