{strip}
{if $gBitUser->hasPermission('p_liberty_attach_attachments') }
	{capture assign=upload_label}{tr}Upload {$upload_type|default:"File(s)"}{/tr}{/capture}
	{formlabel label=$upload_label class="label"}
	{forminput}
		{if $gBitSystem->getConfig("liberty_attachment_style") == "multiple"}
			<script type="text/javascript" src="{$smarty.const.UTIL_PKG_URL}javascript/libs/multifile.js"></script>
			<div id="upload_div"></div>
			<input class="fileUpload" type="file" name="{$upload_name|default:upload}" size="40" id="uploads" />
			<script type="text/javascript">
				var upload_files   = document.getElementById( 'upload_div' );
				var upload_element = document.getElementById( 'uploads' );
				var multi_selector = new MultiSelector( upload_files, {$gBitSystem->getConfig('liberty_max_multiple_attachments',10)});
				multi_selector.addNamedElement( upload_element , 'uploads');
			</script>
			{formhelp note='After selecting the file you want to upload, please return to the edit area and click the save button.'}
		{elseif $gBitSystem->getConfig("liberty_attachment_style") == "ajax"}
		<input class="fileUpload" type="file" name="{$upload_name|default:upload}" size="40" id="upload_{$form_id}" onchange="javascript:LibertyAttachment.uploader(this, '{$smarty.const.LIBERTY_PKG_URL}attachment_uploader.php','{tr}Please wait for the current upload to finish.{/tr}', 'liberty_upload_frame_{$form_id}', this.form.id);" />
			{include file="bitpackage:liberty/attachment_uploader_inc.tpl"}
			{formhelp note='After selecting the file you want it will automatically upload.'}
			<script type="text/javascript">
				LibertyAttachment.uploaderSetup( 'upload_{$form_id}' );
			</script>
		{else}
			<input class="fileUpload" type="file" name="{$upload_name|default:upload}" size="40" />
			{formhelp note='After selecting the file you want to upload, please return to the edit area and click the save button.'}
		{/if}
	{/forminput}
{/if}
{/strip}
