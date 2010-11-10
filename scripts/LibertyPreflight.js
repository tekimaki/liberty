/* Dependencies: MochiKit Base Async, BitAjax.js  */
LibertyPreflight = {
	"fileInputClones":{},
	"uploader_under_way":0,

	"uploaderSetup":function(fieldsetid){
		LibertyPreflight.fileInputClones[fieldsetid] = MochiKit.DOM.getElement(fieldsetid).cloneNode(true);
	},
	
	"uploader": function(form, action, waitmsg, frameid, pluginguid, fieldsetguid ) {
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
			if ( typeof( form.preflight_fieldset_guid ) == "undefined" ){
				var i = INPUT( {'name':'preflight_fieldset_guid', 'type':'hidden', 'value':fieldsetguid}, null );
				form.insertBefore( i, form.firstChild ); 
			}else{
				form.preflight_fieldset_guid.value = fieldsetguid;
			}
			var old_target = form.target;
			// form.target = frameid;
			var old_action = form.action;
			form.action=action;
			form.submit();
			form.target = old_target;
			form.action = old_action;
		}
	},

	"preflightCheck": function(){
	},

	"uploaderComplete": function(frmid, fieldsetid) {
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
			
			// LibertyPreflight.postflightCheck( fieldsetid, d );

			// replace the current form with the result
			var errMsg = "<div>Sorry, there was a problem retrieving results. Please report this issue to an administrator</div>";
			var divO = document.getElementById(fieldsetid); 
			divR = d.getElementById(fieldsetid);
			if (divO != null) {
				divO.innerHTML = (divR != null)?divR.innerHTML:errMsg;
			}
			LibertyPreflight.uploader_under_way = 0;
			/*
			var file = document.getElementById(fileid);
			LibertyPreflight.fileInputClones[fileid].id = fileid;
			MochiKit.DOM.swapDOM(file, LibertyPreflight.fileInputClones[fileid]);
			LibertyPreflight.uploaderSetup( fileid );
			*/
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
