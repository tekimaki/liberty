<div class="section {if !empty($gContent)}{$gContent->getContentTypeName()|strtolower} {/if}{$smarty.request.section}">
{foreach from=$gBitSystem->getPackagePluginHandlers('tpl','content_display_section') item=service}
	{if !empty($gContent) && $gContent->hasService('content_display_section_tpl')}
		{include file=$service.plugin_handler}
	{/if}
{/foreach}
</div>
