/* Dependencies: MochiKit Base Async, BitAjax.js  */
LibertyPreflight = {
	"fileInputClones":{},
	"uploader_under_way":0,

	"uploaderSetup":function(fileid){
		LibertyPreflight.fileInputClones[fileid] = MochiKit.DOM.getElement(fileid).cloneNode(true);
	},
	
	"uploader": function(form, action, waitmsg, frameid, pluginguid ) {
		if (LibertyPreflight.uploader_under_way) {
			alert(waitmsg);
		}else{
			LibertyPreflight.uploader_under_way = 1;
			BitBase.showSpinner();
			if ( typeof( form.preflight_plugin_guid ) == "undefined" ){
				var i = INPUT( {'name':'preflight_plugin_guid', 'type':'hidden', 'value':pluginguid}, null );
				form.insertBefore( i, form.firstChild ); 
			}else{
				form.preflight_plugin_guid.value = pluginguid;
			}
			var old_target = form.target;
			form.target = frameid;
			var old_action = form.action;
			form.action=action;
			form.submit();
			form.target = old_target;
			form.action = old_action;
		}
	},

	"preflightCheck": function(){
	},

	"uploaderComplete": function(frmid, divid, fileid, cformid) {
		if (LibertyPreflight.uploader_under_way){
			BitBase.hideSpinner();
			var ifrm = document.getElementById(frmid);
			if (ifrm.contentDocument) {
				var d = ifrm.contentDocument;
			} else if (ifrm.contentWindow) {
				var d = ifrm.contentWindow.document;
			} else {
				var d = window.frames[frmid].document;
			}
			if (d.location.href == "about:blank") {
				return;
			}
			
			LibertyPreflight.postflightCheck( cformid, d );

			var errMsg = "<div>Sorry, there was a problem retrieving results.</div>";
			var divO = document.getElementById(divid);
			divR = d.getElementById('result_tab');
			if (divO != null) {
				divO.innerHTML = (divR != null)?divR.innerHTML:errMsg+"a";
			}
			divid = divid + '_tab';
			divO = document.getElementById(divid);
			var divR = d.getElementById('result_list');
			if (divO != null) {
				divO.innerHTML =  (divR != null)?divR.innerHTML:errMsg+"b";
			}
			LibertyPreflight.uploader_under_way = 0;
			var file = document.getElementById(fileid);
			LibertyPreflight.fileInputClones[fileid].id = fileid;
			MochiKit.DOM.swapDOM(file, LibertyPreflight.fileInputClones[fileid]);
			LibertyPreflight.uploaderSetup( fileid );
			// file.value = '';
		}
	},
	
	"postflightCheck": function( cformid, d ){
		var form = MochiKit.DOM.getElement(cformid);
		var cid = d.getElementById("upload_content_id").value;
		if ( typeof( form.content_id ) == "undefined" ){
			var i = INPUT( {'name':'content_id', 'type':'hidden', 'value':cid}, null );
			form.insertBefore( i, form.firstChild ); 
		}else{
			form.content_id.value = cid;
		}
	}
}
