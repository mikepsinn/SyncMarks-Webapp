/**
 * SyncMarks
 *
 * @version 1.2.7
 * @author Offerel
 * @copyright Copyright (c) 2020, Offerel
 * @license GNU General Public License, version 3
 */
document.addEventListener("DOMContentLoaded", function(event) {
	document.querySelector('#menu input').addEventListener('keyup', function(e) {
		var sfilter = this.value;
		var allmarks = document.querySelectorAll('#bookmarks li.file');
		var bdiv = document.getElementById('bookmarks');
		bdiv.innerHTML = '';
		allmarks.forEach(bookmark => {
			bdiv.appendChild(bookmark);
			if(bookmark.innerText.toUpperCase().includes(sfilter.toUpperCase())) {
				bookmark.style.display = 'block';
				bookmark.style.paddingLeft = '20px';
			} else {
				bookmark.style.display = 'none';
			}
		});
		if((sfilter == "") || (e.keyCode == 27)) {
			bdiv.innerHTML = document.getElementById('hmarks').innerHTML;
			document.querySelector('#menu input').value = '';
		}
	});

	document.querySelector('#menu button').addEventListener('click', function() {
		if(document.querySelector('#menu button').innerHTML == '\u00D7') {
			document.querySelector('#menu input').blur();
			document.querySelector('#menu button').innerHTML = '\u2315';
			document.querySelector('#menu button').classList.remove('asform');
			document.querySelector('#menu input').classList.remove('asform');
			document.querySelector('#menu input').classList.add('isform');
			document.getElementById('mprofile').style.display = 'block';
		}
		else {
			document.querySelector('#menu button').innerHTML = '\u00D7';
			document.querySelector('#menu button').classList.add('asform');
			document.querySelector('#menu input').classList.remove('isform');
			document.querySelector('#menu input').classList.add('asform');
			document.getElementById('mprofile').style.display = 'none';
			document.querySelector('#menu input').focus();
		}
		hideMenu();
	});

	document.getElementById("logfile").addEventListener("mousedown", function(e){
		if (e.offsetX < 3) {
			document.addEventListener("mousemove", resize, false);
		}
	}, false);
	
	document.addEventListener("mouseup", function(){
		document.removeEventListener("mousemove", resize, false);
	}, false);

	document.querySelectorAll('.file').forEach(bookmark => bookmark.addEventListener('contextmenu',onContextMenu,false));
	document.querySelectorAll('.tablinks').forEach(tab => tab.addEventListener('click',openMessages, false));
	document.querySelectorAll('.fa-trash-o').forEach(message => message.addEventListener('click',delMessage, false));
	document.querySelector('#cnoti').addEventListener('change',eNoti,false);

	if(sessionStorage.getItem('gNoti') != 1) getNotifications();

	document.addEventListener('keydown', e => {
		if (e.keyCode === 27) {
			hideMenu();
		}
	});

	document.querySelector("#mngcform input[type='text']").addEventListener('focus', function() {
		this.select();
	});

	document.getElementById("save").addEventListener('click', function(event) {
		event.preventDefault();
		hideMenu();
		let folder = document.getElementById('folder').value;
		let url = encodeURIComponent(document.getElementById('url').value);

		var xhr = new XMLHttpRequest();
		var data = "madd=" + true + "&folder=" + folder + "&url=" + url;
		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 200) {
					document.getElementById('bookmarks').innerHTML = this.responseText;
					console.log("Bookmark added successfully.");
				} else {
					alert("Error adding bookmark, please check server log.");
				}
			}
		};

		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(data);
	});

	document.getElementById("mlogout").addEventListener('click', function(event) {
		event.preventDefault();
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 401) {
					console.log("Successfully logged out...");
				}
			}
		};

		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send("logout=true");
		return false;
	});

	document.getElementById('userSelect').addEventListener('change', function() {
		if(this.value > 0) {
			document.getElementById('nuser').value = document.querySelector('#userSelect option:checked').text;
			checkuform();
			document.getElementById('muadd').value = 'Edit User';
			document.getElementById('mudel').disabled = false;
		}
	});

	document.getElementById('npwd').addEventListener('input', function() {checkuform()});
	document.getElementById('nuser').addEventListener('input', function() {checkuform()});
	document.getElementById('userLevel').addEventListener('input', function() {checkuform()});

	document.getElementById('hmenu').addEventListener('click', function() {
		var mainmenu = document.getElementById('mainmenu');
		document.querySelector('#menu button').style.background = 'transparent';
		document.querySelector('#bookmarks').addEventListener('click', hideMenu, false);

		if(mainmenu.style.display === 'block') {
			mainmenu.style.display = 'none';
		} else {
			hideMenu();
			mainmenu.style.display = 'block';
		}
	});

	document.getElementById('mngusers').addEventListener('click', function() {
		hideMenu();
		document.getElementById('mnguform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.getElementById('muser').addEventListener('click', function() {
		hideMenu();
		document.getElementById('userform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.querySelectorAll('.mdcancel').forEach(button => button.addEventListener('click', function() {
		hideMenu();
	}));

	document.getElementById('mpassword').addEventListener('click', function() {
		hideMenu();
		document.getElementById('passwordform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.getElementById('pbullet').addEventListener('click', function() {
		hideMenu();
		document.getElementById('pbulletform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.getElementById('nmessages').addEventListener('click', function() {
		hideMenu();
		document.getElementById('nmessagesform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.getElementById('clientedt').addEventListener('click', function() {
		hideMenu();
		document.getElementById('mngcform').style.display = 'block';
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
	});

	document.getElementById('bexport').addEventListener('click', function() {
		hideMenu();
		var today = new Date();
		var dd = today.getDate();
		var mm = today.getMonth()+1; 
		var yyyy = today.getFullYear();
		if(dd<10) dd='0'+dd;
		if(mm<10) mm='0'+mm;
		today = dd+'-'+mm+'-'+yyyy;

		var xhr = new XMLHttpRequest();
		var data = "export=html";

		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 200) {
					var blob = new Blob([this.responseText], { type: 'text/html' });
					var link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob);
					link.download = "bookmarks_" + today + ".html";
					link.click();
					console.log("HTML successfully, please look in your download folder.");
				} else {
					alert("Error generating export, please check server log.");
				}
			}
		};

		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(data);

		return false;
	});

	document.getElementById('footer').addEventListener('click', function() {
		hideMenu();
		document.querySelector('#bookmarks').addEventListener('click',hideMenu, false);
		document.getElementById('bmarkadd').style.display = 'block';
		url.focus();
		url.addEventListener('input', enableSave);
	});

	document.getElementById('mlog').addEventListener('click', function() {
		hideMenu();
		let logfile = document.getElementById('logfile');
		if(logfile.style.visibility === 'visible') {
			logfile.style.visibility = 'hidden';
			document.getElementById('close').style.visibility = 'hidden';
		} else {
			logfile.style.visibility = 'visible';
			document.getElementById('close').style.visibility = 'visible';
			let xhr = new XMLHttpRequest();
			let data = "mlog=true";
			xhr.onreadystatechange = function () {
				if (this.readyState == 4) {
					if(this.status == 200) {
						document.getElementById('lfiletext').innerHTML = this.responseText;
						moveEnd();
					} else {
						alert("Error loading logfile, please check server log.");
					}
				}
			};
			xhr.open("POST", document.location.href, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(data);
		}
	});

	document.getElementById('mclear').addEventListener('click', function() {
		let logfile = document.getElementById('logfile');
		if(logfile.style.visibility === 'visible') {
			logfile.style.visibility = 'hidden';
			document.getElementById('close').style.visibility = 'hidden';
			let xhr = new XMLHttpRequest();
			let data = "mclear=true";
			xhr.onreadystatechange = function () {
				if (this.readyState == 4) {
					if(this.status == 200) {
						console.log("Logfile should now be empty.");
					} else {
						console.log("Error couldnt clear logfile.");
					}
				}
			};
			xhr.open("POST", document.location.href, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(data);
		}
	});

	document.getElementById('mclose').addEventListener('click', function() {
		if(document.getElementById('logfile').style.visibility === 'visible') {
			document.getElementById('logfile').style.visibility = 'hidden';
			document.getElementById('close').style.visibility = 'hidden';
		}
	});

	document.querySelectorAll('#mngcform .clientname').forEach(function(e) {
		e.addEventListener('touchstart',function() {
			this.children[0].style.display = 'block';
		})
	});

	document.querySelectorAll("#mngcform li div.rename").forEach(function(element) {
		element.addEventListener('click', function() {
			let xhr = new XMLHttpRequest();
			let data = 'arename=true&cido=' + this.parentElement.id + '&nname=' + this.parentElement.children[0].children['cname'].value;

			xhr.onreadystatechange = function () {
				if (this.readyState == 4) {
					if(this.status == 200) {
						document.getElementById('mngcform').innerHTML = this.responseText;
					}
				}
			};

			xhr.open("POST", document.location.href, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(data);
		});
	});

	document.querySelectorAll("#mngcform li div.remove").forEach(function(element) {
		element.addEventListener('click', function() {
			let xhr = new XMLHttpRequest();
			let data = 'adel=true&cido=' + this.parentElement.id;
			xhr.onreadystatechange = function () {
				if (this.readyState == 4) {
					if(this.status == 200) {
						document.getElementById('mngcform').innerHTML = this.responseText;
					}
				}
			};

			xhr.open("POST", document.location.href, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(data);
		});
	});

	document.querySelectorAll("#mngcform li div.clientname input").forEach(function(element) {
		element.addEventListener('mouseleave', function() {
			if(this.defaultValue != this.value) {
				this.style.display = 'block';
				this.parentElement.parentElement.children[2].style.display = 'block';
				this.parentElement.parentElement.children[3].style.display = 'block';
			}
		});
	});

	document.getElementById('edtitle').addEventListener('input', function() {
		document.getElementById('edsave').disabled = false;
	});

	document.getElementById('edurl').addEventListener('input', function() {
		document.getElementById('edsave').disabled = false;
	});

	document.getElementById('mvfolder').addEventListener('change', function() {
		document.getElementById('mvsave').disabled = false;
	});

	document.getElementById('edsave').addEventListener('click', function(e) {
		e.preventDefault();
		let xhr = new XMLHttpRequest();
		let data = 'bmedt=true&title=' + document.getElementById('edtitle').value + '&url=' + document.getElementById('edurl').value + '&id=' + document.getElementById('edid').value;
		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 200) {
					if(this.responseText == 1)
						location.reload(false);
					else
						console.log("There was a problem changing that bookmark.");
				}
			}
		};
		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(data);
		return false;
	});

	document.getElementById('mvsave').addEventListener('click', function(e) {
		e.preventDefault();
		let xhr = new XMLHttpRequest();
		let data = 'bmmv=true&title=' + document.getElementById('mvtitle').value + '&folder=' + document.getElementById('mvfolder').value + '&id=' + document.getElementById('mvid').value;
		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 200) {
					if(this.responseText == 1)
						location.reload(false);
					else
						console.log("There was a problem moving that bookmark.");
				}
			}
		};
		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(data);
		return false;
	});

});

function resize(e){
	let wdt = window.innerWidth - parseInt(e.x);
	document.getElementById("logfile").style.width = wdt + "px";
}

function checkuform() {
	if(document.getElementById('nuser').value.length > 0 && document.getElementById('npwd').value.length > 0 && document.getElementById('userLevel').value.length > 0) {
		document.getElementById('muadd').disabled = false;
		document.getElementById('mudel').disabled = false;
	}
	else {
		document.getElementById('muadd').disabled = true;
		document.getElementById('mudel').disabled = true;
	}
	
	if(document.getElementById('userSelect').value.length < 1) {
		document.getElementById('mudel').disabled = true;
	}
}

function moveEnd () {
	let lfiletext = document.getElementById("lfiletext");
	lfiletext.scrollTop = lfiletext.scrollHeight;
}

function delBookmark(id, title) {
	if(confirm("Would you like to delete \"" + title + "\"?")) {
		let xhr = new XMLHttpRequest();
		let data = 'mdel=true&id=' + id;
		xhr.onreadystatechange = function () {
			if (this.readyState == 4) {
				if(this.status == 200) {
					document.getElementById('bookmarks').innerHTML = this.responseText;
					document.querySelectorAll('.file').forEach(bookmark => bookmark.addEventListener('contextmenu',onContextMenu,false));
				}
				else
					console.log("There was a problem removing that bookmark.");
			}
		};
		xhr.open("POST", document.location.href, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(data);
	}
}

function enableSave() {
	if(document.getElementById('url').value.length > 7)
		document.getElementById('save').disabled = false;
	else
		document.getElementById('save').disabled = true;
}

function showMenu(x, y){
	var menu = document.querySelector('.menu');
	var minbot = window.innerHeight - 120;
	if(y >= minbot) y = minbot;
    menu.style.left = x + 'px';
	menu.style.top = y + 'px';
	menu.style.opacity = 1;
    menu.classList.add('show-menu');
}

function hideMenu(){
	let menu = document.querySelector('.menu');
	menu.classList.remove('show-menu');
	menu.style.display = 'none';
	
	document.querySelectorAll('.mmenu').forEach(function(item) {item.style.display = 'none'});
	document.querySelectorAll('.mbmdialog').forEach(function(item) {item.style.display = 'none'});
}

function onContextMenu(e){
    e.preventDefault();
	hideMenu();
	let menu = document.querySelector('.menu');
	menu.style.display = 'block';
	document.getElementById('bmid').value = e.srcElement.attributes.id.value;
	document.getElementById('bmid').title = e.srcElement.attributes.title.value;
	showMenu(e.pageX, e.pageY);
	document.querySelector('#btnEdit').addEventListener('click', onClick, false);
	document.querySelector('#btnMove').addEventListener('click', onClick, false);
	document.querySelector('#btnDelete').addEventListener('click', onClick, false);
}

function onClick(e){
	var minleft = 155;
	var minbot = window.innerHeight - 200;
	var xpos = e.pageX;
	var ypos = e.pageY;
	if(xpos <= minleft) xpos = minleft;
	if(ypos >= minbot) ypos = minbot;
	
	switch(this.id) {
		case 'btnEdit':
			document.getElementById('edtitle').value = document.getElementById('bmid').title;
			document.getElementById('edurl').value = document.getElementById(document.getElementById('bmid').value).href;
			document.getElementById('edid').value = document.getElementById('bmid').value;
			hideMenu();
			document.getElementById('bmarkedt').style.left = xpos;
			document.getElementById('bmarkedt').style.top = ypos;
			document.getElementById('bmarkedt').style.display = 'block';
			break;
		case 'btnMove':
			document.getElementById('mvtitle').value = document.getElementById('bmid').title;
			document.getElementById('mvid').value = document.getElementById('bmid').value;
			hideMenu();
			document.getElementById('bmamove').style.left = xpos;
			document.getElementById('bmamove').style.top = ypos;
			document.getElementById('bmamove').style.display = 'block';
			break;
		case 'btnDelete':
			delBookmark(document.getElementById('bmid').value, document.getElementById('bmid').title)
			break;
		default:
			break;
	}

    document.removeEventListener('click', onClick);
}

function openMessages(element) {
	var i, tabcontent, tablinks;
	tabcontent = document.getElementsByClassName("tabcontent");
	for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].style.display = "none";
	}
	
	tablinks = document.getElementsByClassName("tablinks");
	for (i = 0; i < tablinks.length; i++) {
		tablinks[i].className = tablinks[i].className.replace(" active", "");
	}

	document.getElementById(element.target.dataset['val']).style.display = "block";
	element.currentTarget.className += " active";
}

function delMessage(message) {
	let loop = message.target.parentElement.parentElement.parentElement.parentElement.parentElement.id;
	let xhr = new XMLHttpRequest();
	let data = 'caction=rmessage&lp=' + loop + '&message=' + message.target.dataset['message'];
	xhr.onreadystatechange = function () {
		if (this.readyState == 4) {
			if(this.status == 200) {
				document.querySelector('#'+loop+' .NotiTable .NotiTableBody').innerHTML = this.responseText;
			}
		}
	};
	xhr.open("POST", document.location.href, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(data);
}

function eNoti(e) {
	var nval = e.target.checked;
	if(nval) {
		if (!("Notification" in window)) {
			alert("This browser does not support desktop notification");
			setOption("notifications",0);
		}
		else if (Notification.permission === "granted") {
			var notification = new Notification("Syncmarks", {
				body: "Notifications will be enabled for Syncmarks.",
				icon: './images/bookmarks192.png'
			});
			setOption("notifications",1);
		}
		else if (Notification.permission !== "denied") {
			Notification.requestPermission().then(function (permission) {
				if (permission === "granted") {
					var notification = new Notification("Syncmarks", {
						body: "Notifications will be enabled for Syncmarks.",
						icon: './images/bookmarks192.png'
					});
					setOption("notifications",1);
				}
			});
		}
	} else {
		setOption("notifications",0);
	}
}

function setOption(option,val) {
	let xhr = new XMLHttpRequest();
	let data = 'caction=soption&option=' + option + '&value=' + val;
	xhr.onreadystatechange = function () {
		if (this.readyState == 4) {
			if(this.status == 200) {
				if(this.responseText === "1") {
					console.log("Option saved.");
				} else {
					alert("Error saving option, please check server log");
				}
			}
		}
	};
	xhr.open("POST", document.location.href, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(data);
}

function rNot(noti) {
	let xhr = new XMLHttpRequest();
	let url = new URL(document.location.href);
	url.searchParams.set('durl', noti);
	xhr.onreadystatechange = function () {
		if (this.readyState == 4) {
			if(this.status == 200) {
				if(this.responseText === "1") {
					console.log("Notification removed");
				} else {
					alert("Problem removing notification, please check server log.");
				}
			}
		}
	};
	xhr.open("GET", url, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send();
}

function show_noti(noti) {
	if (Notification.permission !== 'granted')
		Notification.requestPermission();
	else {
		let notification = new Notification(noti.title, {
			body: noti.url,
			icon: './images/bookmarks192.png',
			requireInteraction: true
		});
		
		notification.onclick = function() {
			window.open(noti.url);
			rNot(noti.nkey);
		};
	}
}

function getNotifications() {
	let xhr = new XMLHttpRequest();
	let url = new URL(document.location.href);
	url.searchParams.set('gurls', '1');

	xhr.onreadystatechange = function () {
		if (this.readyState == 4) {
			if(this.status == 200) {
				if(this.responseText) {
					let notifications = JSON.parse(this.responseText);
					if(notifications[0]['nOption'] == 1) {
						notifications.forEach(function(notification){
							show_noti(notification);
						});
					}
				}
			}
		}
	};

	xhr.open("GET", url, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send();

	sessionStorage.setItem('gNoti', '1');
}