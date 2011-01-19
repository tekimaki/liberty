/* Dependencies: jquery  */
LibertyPreflight = {
	"fileInputClones":{},
	"uploader_under_way":0,

	"uploaderSetup":function(fieldsetid){
		LibertyPreflight.fileInputClones[fieldsetid] = BitBase.$(fieldsetid).cloneNode(true);
	},

	"expunge": function(form, action, waitmsg, frameid, pluginguid, fieldsetguid, expungefieldsetid ) {
		if (LibertyPreflight.uploader_under_way) {
			alert(waitmsg);
		}else{
			// reconfirm with end user they want to expunge
			var confirm_expunge= confirm("Are you sure you want to delete this?");
			if(confirm_expunge){
				// drop the fieldset to be deleted then run upload
				var i = BitBase.$( expungefieldsetid );	
				i.parentNode.removeChild(i);
				delete i;
				LibertyPreflight.uploader( form, action, waitmsg, frameid, pluginguid, fieldsetguid );
			}else {
				return ;
			}
		}
	},
	
	"uploader": function(form, action, waitmsg, frameid, pluginguid, fieldsetguid ) {
		if (LibertyPreflight.uploader_under_way) {
			alert(waitmsg);
		}else{
			// unset ckeditor
			if( typeof( BitCK ) != 'undefined' ){
				BitCK.unCKifyAll();
			}
			// unset any placeholders
			if( typeof( BitBase.clearPlaceholders ) != "undefined" ){
				BitBase.clearPlaceholders();
			}
			LibertyPreflight.uploader_under_way = 1;
			BitBase.showSpinner();
			if ( typeof( form.preflight_plugin_guid ) == "undefined" ){
				var i = $jq('<input type="hidden" />').attr('name', 'preflight_plugin_guid');
				i.attr('value',pluginguid);				
				form.insertBefore( i.get(0), form.firstChild ); 
			}else{
				form.preflight_plugin_guid.value = pluginguid;
			}
			if ( typeof( form.preflight_fieldset_guid ) == "undefined" ){
				var i = $jq('<input type="hidden" />').attr('name', 'preflight_fieldset_guid');
				i.attr('value',fieldsetguid);
				form.insertBefore( i.get(0), form.firstChild ); 
			}else{
				form.preflight_fieldset_guid.value = fieldsetguid;
			}
			if ( typeof( form.formid ) == "undefined" ){
				var i = $jq('<input type="hidden" />').attr('name', 'formid');
				i.attr('value',form.id);
				form.insertBefore( i.get(0), form.firstChild ); 
			}else{
				form.formid.value = form.id;
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

	"uploaderComplete": function(frameid, fieldsetid, formid) {
		if (LibertyPreflight.uploader_under_way){
			BitBase.hideSpinner();
			var ifrm = BitBase.$(frameid);
			if (ifrm.contentDocument) {
				var d = ifrm.contentDocument;
			} else if (ifrm.contentWindow) {
				var d = ifrm.contentWindow.document;
			} else {
				var d = window.frames[frameid].document;
			}
			if (d.location.href == "about:blank") {
				return;
			}
			
			console.log(d);
			LibertyPreflight.postflightCheck( formid, d );

			// replace the current form with the result
			var errMsg = "<div>Sorry, there was a problem retrieving results. Please report this issue to an administrator</div>";
			var divO = BitBase.$(fieldsetid); 
			divR = d.getElementById(fieldsetid);
			if (divO != null) {
				divO.innerHTML = (divR != null)?divR.innerHTML:errMsg;
			}
			LibertyPreflight.uploader_under_way = 0;

			// set any placeholders
			if( typeof( BitBase.setPlaceholders ) != "undefined" ){
				BitBase.setPlaceholders();
			}
			if( typeof( BitCK ) != 'undefined' ){
				BitCK.CKall();
			}
		}
	},
	
	"postflightCheck": function( formid, d ){
		var form = BitBase.$(formid);
		var cid = d.getElementById("upload_content_id").value;
		if ( typeof( form.content_id ) == "undefined" ){
			var i = $jq('<input type="hidden" />').attr('name', 'content_id');
			i.attr('value',cid);
			form.insertBefore( i.get(0), form.firstChild ); 
		}else{
			form.content_id.value = cid;
		}
	}
}
