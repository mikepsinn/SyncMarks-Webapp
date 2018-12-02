var menu = document.querySelector('.menu');
var bookmarks = document.querySelectorAll('.file').forEach(bookmark => bookmark.addEventListener('contextmenu',onContextMenu,false));

if (/Mobi|Android/i.test(navigator.userAgent)) {
	var ffolder = [];
	$('.ffolder').on('change', function() {
		if (this.checked) {
			ffolder.push(this.value);
		} else {
			ffolder.splice(ffolder.indexOf(this.value), 1);
			location.reload();
		}
		
		var actFolder = "f_"+ffolder[ffolder.length-1];
		
		if(actFolder != 'f_undefined') {
			$('.actFolder').removeClass('actFolder');
			$('#'+actFolder+' li').addClass('actFolder');
			$('.mainFolder > label').css('z-index',0);
			$('#'+actFolder).addClass('actFolder mainFolder');
			$('.mainFolder').addClass('actFolder');
			$('li:not(.actFolder)').hide();
		}
	});
}

$(document).bind('keydown', 'esc', function() {
	$('.bmdialog').css('visibility','hidden');
});

$("#save").on("click",function(){        
    $.ajax({
        url: document.location.href,
		type: "POST",
        data: {
            madd: true,
            folder: $('#folder').val(),
            url: $('#url').val(),
        },
        success: function(r){
             $('#bmarkadd').attr('style', 'visibility: hidden');
			 $("#bookmarks").html(r);
		}
    });  
    return false;
});

$("#userSelect").on("change",function(){
	if($(this).val() > 0) {
		$('#nuser').val($("#userSelect option:selected").text());
		$('#muadd').attr('disabled','disabled');
		$('#muedt').removeAttr('disabled','disabled');
		$('#mudel').removeAttr('disabled','disabled');
	}
});

$("#mprofile").on("click",function(){
	if($('#profileform').css('visibility') === 'hidden') {
		$('#mnguform').css('visibility','hidden');
		$('#mngcform').css('visibility','hidden');
		$('#profileform').css('visibility','visible');
		$('#bmarkadd').css('visibility','hidden');
	}
	else {
		$('#profileform').css('visibility','hidden');
	}
});

$("#mngusers").on("click",function(){
	if($('#profileform').css('visibility') === 'visible') {
		$('#mnguform').css('visibility','visible');
		$('#mngcform').css('visibility','hidden');
		$('#profileform').css('visibility','hidden');
		$('#bmarkadd').css('visibility','hidden');
	}
	else {
		$('#profileform').css('visibility','hidden');
	}
});

$("#clientedt").on("click",function(){
	if($('#profileform').css('visibility') === 'visible') {
		$('#mngcform').css('visibility','visible');
		$('#mnguform').css('visibility','hidden');
		$('#profileform').css('visibility','hidden');
		$('#bmarkadd').css('visibility','hidden');
	}
	else {
		$('#profileform').css('visibility','hidden');
	}
});

$("#footer").on("click",function(){
	if($('#bmarkadd').css('visibility') === 'hidden') {
		$('#bmarkadd').css('visibility','visible');
		$('#profileform').css('visibility','hidden');
		$(".bmarkedt").css('visibility','hidden');
		$('#mngcform').css('visibility','hidden');
		$('#mnguform').css('visibility','hidden');
		url.focus();
		url.addEventListener('input', enableSave);
	}
	else {
		$('#bmarkadd').css('visibility','hidden');
	}
});

$("#mlog").on("click",function(){
	if($('#logfile').css('visibility') === 'hidden') {
		$('#logfile').css('visibility','visible');
		$('#close').css('visibility','visible');
		$.ajax({
			url: document.location.href,
			type: "POST",
			data: {
				mlog: true,
			},
			success: function(r){
				 $("#logfile").html(r);
				moveEnd($('#logfile'));
			}
		});
	}
	else {
		$('#logfile').css('visibility','hidden');
		$('#close').css('visibility','hidden');
	}
});

$("#mclear").on("click",function(){
	if($('#logfile').css('visibility') === 'visible') {
		$('#logfile').css('visibility','hidden');
		$('#close').css('visibility','hidden');
		
		$.ajax({
			url: document.location.href,
			type: "POST",
			data: {
				mclear: true,
			},
			success: function(r){;
			}
		});
	}
});

$("#mclose").on("click",function(){
	if($('#logfile').css('visibility') === 'visible') {
		$('#logfile').css('visibility','hidden');
		$('#close').css('visibility','hidden');
	}
});

$(".client-list li div.rename").on("click", function() {	
	$.ajax({
        type: "POST",
        url: "index.php",
        data: {
            arename: true,
            cido: $(this)[0].parentElement.id,
			nname: $(this)[0].parentElement.children[0].children[0].value,
        },
        success: function(a) {
			response = JSON.parse(a);
			if(response = 1)
				location.reload(false);
			else
				console.log("Error renaming client");
        }
    });
});

$(".client-list li div.remove").on("click", function() {
	console.log("delete");
	console.log($(this)[0].parentElement.id);

	$.ajax({
        type: "POST",
        url: "index.php",
        data: {
            adel: true,
            cido: $(this)[0].parentElement.id,
        },
        success: function(a) {
			response = JSON.parse(a);
			if(response = 1)
				location.reload(false);
			else
				console.log("Error removing client");
        }
    });
	
});

$(".client-list li div.clientname input").on('mouseleave',function() {
	if($(this)[0].defaultValue != $(this)[0].value) {
		$("#"+$(this)[0].parentElement.parentElement.id+" div.clientname input").css("display","block");
		
	}
});

$('#edtitle').on('input', function() {
	$('#edsave').prop('disabled',false);
});

$('#edurl').on('input', function() {
	$('#edsave').prop('disabled',false);
});

$('#mvfolder').on('change', function() {
	$('#mvsave').prop('disabled',false);
});

$('#edsave').on('click', function(e) {
	e.preventDefault();
	$.ajax({
        url: document.location.href,
		type: "POST",
        data: {
            bmedt: true,
            title: $('#edtitle').val(),
            url: $('#edurl').val(),
			id: $('#edid').val(),
        },
        success: function(r){
			if(r == 1) {
				location.reload();
			}
			else {
				console.log("There was a problem changing that bookmark.");
			}
		}
    });  
    return false;
});

$('#mvsave').on('click', function(e) {
	e.preventDefault();
	$.ajax({
        url: document.location.href,
		type: "POST",
        data: {
            bmmv: true,
            title: $('#mvtitle').val(),
			folder: $('#mvfolder').val(),
			id: $('#mvid').val(),
        },
        success: function(r){
			if(r == 1) {
				location.reload();
			}
			else {
				console.log("There was a problem moving that bookmark.");
			}
		}
    });  
    return false;
});

function moveEnd (content) {
	var char = 3000;
	content.focus();
	var sel = window.getSelection();
	sel.collapse(content.firstChild, char);
}

function delBookmark(id, title) {
	if(confirm("Would you like to delete \"" + title + "\"?")) {
		$.ajax({
			url: document.location.href,
			type: "POST",
			data: {
				mdel: true,
				id: id,
			},
			success: function(r){
				 $("#bookmarks").html(r);
				 var bookmarks = document.querySelectorAll('.file').forEach(bookmark => bookmark.addEventListener('contextmenu',onContextMenu,false));
			}
		});
	}
}

function enableSave() {
	var url = document.getElementById('url');
	var save = document.getElementById('save');

	if(url.value.length > 7) {
		save.disabled = false;
	}
	else {
		save.disabled = true;
	}
}

function removeMenu() {
	console.log("click");
}

function showMenu(x, y){
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.classList.add('show-menu');
}

function hideMenu(){
    menu.classList.remove('show-menu');
}

function onContextMenu(e){
    e.preventDefault();
	$('#profileform').css('visibility','hidden');
	$('#bmarkadd').css('visibility','hidden');
	$('#bmid').prop('value',e.srcElement.attributes.id.value);
	$('#bmid').prop('title',e.srcElement.attributes.title.value);
    showMenu(e.pageX, e.pageY);
	document.querySelector('#btnEdit').addEventListener('click', onClick, false);
	document.querySelector('#btnMove').addEventListener('click', onClick, false);
	document.querySelector('#btnDelete').addEventListener('click', onClick, false);
}

function onClick(e){
	switch(this.id) {
		case 'btnEdit':
			$('#bmarkedt #edtitle').val($('#bmid').prop('title'));
			$('#bmarkedt #edurl').val($('#'+$('#bmid').prop('value')).attr('href'));
			$('#bmarkedt #edid').val($('#bmid').prop('value'));
			$("#bmarkedt").css('visibility','visible');
			$("#bmarkedt").css('left',e.pageX);
			$("#bmarkedt").css('top',e.pageY);
			break;
		case 'btnMove':
			$("#bmamove").css('visibility','visible');
			$("#bmarkedt").css('visibility','hidden');
			$('#bmamove #mvtitle').val($('#bmid').prop('title'));
			$('#bmamove #mvid').val($('#bmid').prop('value'));
			$("#bmamove").css('left',e.pageX);
			$("#bmamove").css('top',e.pageY);
			break;
		case 'btnDelete':
			delBookmark($('#bmid').prop('value'), $('#bmid').prop('title'))
			break;
		default:
			break;
	}

    hideMenu();
    document.removeEventListener('click', onClick);
}