{strip}
	{if !$textarea_noformat}
		{include file="bitpackage:liberty/edit_format.tpl"}
	{/if}

	{if $gBitSystem->isFeatureActive('package_smileys')}
		{include file="bitpackage:smileys/smileys_full.tpl"}
	{/if}

	{if $gBitSystem->isFeatureActive('package_quicktags')}
		{include file="bitpackage:quicktags/quicktags_full.tpl"}
	{/if}
{/strip}

	<div class="row rt-edit">
	{if $textarea_required}
		{formlabel label=$textarea_label for=$textarea_id required="y"}
	{else}
		{formlabel label=$textarea_label for=$textarea_id}
	{/if}
		{forminput}
			{formfeedback error=$textarea_error}
			{if !$textarea_id}{assign var=textarea_id value=$smarty.const.LIBERTY_TEXT_AREA}{/if}
			<textarea {$textarea_class} {$textarea_attributes} {if $textarea_maxchars}onkeydown="BitBase.charCounter('{$textarea_id}','{$textarea_id}Counter','{$textarea_maxchars}');" onkeyup="BitBase.charCounter('{$textarea_id}','{$textarea_id}Counter','{$textarea_maxchars}');"{/if} {spellchecker width=$cols height=$rows} id="{$textarea_id}" name="{$textarea_name|default:edit}" {$textarea_style}>{$textarea_data|escape:html}</textarea>

			{if $textarea_maxchars}
				<script type="text/javascript">//<![CDATA[
					if( typeof( BitBase ) == 'undefined' ){ldelim}
						BitBase = {ldelim}{rdelim};
					{rdelim};
					BitBase.charCounter = function( textareaId, counterId, maxChars ) {ldelim}
						document.getElementById( counterId ).value = maxChars - document.getElementById( textareaId ).value.length;
					{rdelim}
				//]]></script>
				{assign var=charCount value=$textarea_data|count_characters:true}
				<div class="formhelp">{tr}Maximum characters allowed:{/tr} <strong>{$textarea_maxchars}</strong>&nbsp;&nbsp;&#8226;&nbsp;&nbsp;
					{tr}Characters remaining:{/tr} <input readonly="readonly" type="text" id="{$textarea_id}Counter" size="5" value="{$textarea_maxchars-$charCount}" /></div>
			{/if}

			{formhelp note=$textarea_help}
			{if $gBitSystem->isPackageActive('ckeditor') &&
				($gBitSystem->isFeatureActive("ckeditor_ask") || 
				$gBitSystem->isFeatureActive("ckeditor_on_click"))}
				{formhelp note="Click in the textarea to activate the editor."}
			{/if}
		{/forminput}
	</div>
