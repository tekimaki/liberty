{strip}
{* we will use the LibertyMime method if available *}
{if $gLibertySystem->isPluginActive( $smarty.const.LIBERTY_DEFAULT_MIME_HANDLER )}
	{foreach from=$gLibertySystem->getAllMimeTemplates('upload') item=tpl}
		{include file=$tpl form_id=$form_id}
	{/foreach}
{/if}
{/strip}
