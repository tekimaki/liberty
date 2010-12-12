{strip}
<noscript>
	{* the tr blocks are intentionally split so the second part only has to be translated once since it is duplicated in the iFrame. *}
	<div class="warning">
		{tr}JavaScript is required for AJAX uploads.{/tr}&nbsp;
		{tr}You must save the content to upload an attachment.{/tr}
	</div>
</noscript>

{* Note! iFrame MUST not be display: none or Safari pops a window instead. *}
{* I am not dynamically creating the iFrame to give a warning for browsers with no iframe support. *}
<iframe src="about:blank" id="liberty_upload_frame_{$frame_id}" name="liberty_upload_frame_{$frame_id}" onload="if(typeof(LibertyPreflight)!='undefined'){ldelim}LibertyPreflight.uploaderComplete('liberty_upload_frame_{$frame_id}', '{$frame_id}', '{$formid|default:editpageform}');{rdelim}" style="position: absolute; left: -10000px;">
	<div class="warning">
		{tr}iFrame support is required for AJAX uploads.{/tr}&nbsp;
		{tr}You must save the content to upload an attachment.{/tr}
	</div>
</iframe>

<input type="hidden" name="upload_content_id" id="upload_content_id" value="{if $gContent->mContentId}{$gContent->mContentId}{/if}" />
<input type="hidden" name="content_type_guid" id="upload_content_type_guid" value="{$gContent->mContentTypeGuid}" />
{/strip}
