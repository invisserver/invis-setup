<?php
/* 
 * inc/int.section.inc.php v1.0
 * portal drop-in, displaying portal wide and user specifig links
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2012 Ingo Goeppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

if (!isset($CONF)) die;
?>

<script type="text/javascript">

function linksEdit(request) {
	var data = request.responseText.evalJSON();
	
	lightbox.show(400, true);
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Links'));
	var box = new Element('table', {'id': 'linkbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'linkbox_content'}));
	box.insert(tr_content);
	
	var line_input = new Element('div');
	var input_name = new Element('input', {'id': 'link_name'});
	var input_addr = new Element('input', {'id': 'link_addr'});
	
	line_input.insert('<div class="line_key">Name:</div>');
	line_input.insert(input_name);
	line_input.insert('<br/ ><div class="line_key">URL:</div>');
	line_input.insert(input_addr);
	$('linkbox_content').insert(line_input);
	
	var link_box = new Element('select', {'size': 10, 'style': 'width: 100%; font-size: 1em;'});
	
	if (data['labeleduri']) {
		if (!Object.isArray(data['labeleduri'])) {
			var tmp = data['labeleduri'];
			data['labeleduri'] = $A();
			data['labeleduri'].push(tmp);
		}
		
		data['labeleduri'].each(function(item){
			var link = item.split(' ');
			if (link.length > 2)
				for (var i = link.length - 1; i >= 2; i--) {
					link[i - 1] = link[i - 1] + ' ' + link[i];
				}
			var option = new Element('option').update(link[1]);
			option.value = link[0];
			link_box.insert(option);
		});
	}
	
	link_box.observe('change', clickHelper);
	$('linkbox_content').insert(link_box);
	
	var button_add = new Element('button', {'style': 'width: 50px;'}).update('+');
	button_add.observe('click', function (e) {
		var tmp = new Element('option').update('neu');
		tmp.value = 'http://';
		link_box.insert(tmp);
		link_box.selectedIndex = link_box.childNodes.length - 1;
		clickHelper(link_box);
	});
	
	var button_del = new Element('button', {'style': 'width: 50px;'}).update('-');
	button_del.observe('click', function (e) {
		var ix = link_box.selectedIndex;
		if (ix < 0) return null;
		
		link_box.removeChild(link_box.childNodes[ix]);
		link_box.selectedIndex = (ix == 0)?0:ix - 1;
		clickHelper(link_box);
	});
	
	$('linkbox_content').insert(button_add);
	$('linkbox_content').insert(button_del);
	
	input_name.observe('keyup', function (e) {
		link_box.childNodes[link_box.selectedIndex].text = input_name.value;
	});
	input_addr.observe('keyup', function (e) {
		link_box.childNodes[link_box.selectedIndex].value = input_addr.value;
	});
	
	var button_save = new Element('button').update("Speichern");
	button_save.observe('click', function (e) {
		var liste = $A();
		$A(link_box.childNodes).each(
			function (node) {
				var str = node.value + ' ' + node.text;
				liste.push(str);
			}
		);
		invis.setCookie('invis-request', $H({labeleduri: liste}).toJSON());
		var tmp = invis.getCookie('invis').evalJSON();
		invis.request('script/ajax.php', linksModResponse, {c: 'user_mod', u: tmp['uid']});
	});
	lightbox.addButton(button_save);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');
	lightbox.update();
}

function clickHelper(e) {
	var box;
	if (e.target) box = e.target;
	else box = e;
	
	var entry = box.childNodes[box.selectedIndex];
	$('link_addr').value = entry.value;
	$('link_name').value = entry.text;
}

function linksModResponse(request) {
	if (request.responseText == '0') {
		lightbox.setStatus('Änderungen wurden gespeichert!');
		lightbox.hide();
		window.location.reload();
	} else {
		lightbox.setStatus('Fehler beim Speichern!' + request.responseText);
	};
}

</script>

<?php
	require_once('ldap.php');
	$conn = connect();
	$bind = bind($conn);
	
	global $LDAP_SUFFIX;
	
	// global links
	echo '<h3>Global</h3><span>Die hier aufgeführten Links können an Ihre Anforderungen angepasst werden.</span>';
	echo '<ul id="global-links">';
	$result1 = search($conn, "ou=iPortal,ou=Informationen,$LDAP_SUFFIX", 'iPortEntryPosition=Internet');
	if ($result1) {
		$groups = array();
		unset($result1['count']);
		foreach($result1 as $entry) {
			$entry = cleanup($entry);
			if ($entry['iportentryactive'] == 'FALSE') continue;
			
			$grp = $entry['iportentrylinkrubrik'];
			if (!isset($groups[$grp]))
				$groups[$grp] = array();
			array_push($groups[$grp], $entry);
		}
		
		foreach($groups as $groupname => $group) {
			echo "<li style='background-color: #eeeeee;'><b>$groupname</b>";
			echo "<ul>";
			foreach($group as $entry) {
				if ($entry['iportentryssl'] == 'FALSE'){
				    echo '<li><a href="http://'. $entry['iportentryurl'] .'" target="_blank">' . $entry['iportentrybutton'] . '</a></li>';
				} else {
				    echo '<li><a href="https://'. $entry['iportentryurl'] .'" target="_blank">' . $entry['iportentrybutton'] . '</a></li>';
				}
			}
			echo "</ul>";
			echo "</li>";
		}
	}
	echo '</ul>';
	
	// user specific links
	if (isset($_COOKIE['invis'])) {
		$data = json_decode($_COOKIE['invis']);
		$name = $data -> displayname;
		if ($name == '') $name = $data -> uid;
		echo "<h3>Links für $name</h3>";
		
		$result = search($conn, $BASE_DN_USER, "uid=" . $data -> uid, array('labeledURI'));
		if ($result) {
			$result = cleanup($result[0]);
			echo '<ul id="lokal-links">';
			if (isset($result['labeleduri'])) {
				if (count($result['labeleduri']) == 1) $result['labeleduri'] = array($result['labeleduri']);
				foreach($result['labeleduri'] as $entry) {
					$url = explode(' ', $entry, 2);
					echo '<li><a href="' . $url[0] . '" target="_blank">' . $url[1] . '</a></li>';
				}
			}
			echo '</ul>';
		}
		
		echo '<a onclick="invis.request(\'script/ajax.php\', linksEdit, {c: \'links_list\', u: \'' . $data -> uid . '\'});" style="cursor: pointer;"><img src="images/edit_img.png" /></a>';
	}
	
	unbind($conn);
?>
