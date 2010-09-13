<div class="section {if !empty($gContent)}{$gContent->getContentTypeName()|strtolower} {/if}{$smarty.request.section}">
{foreach from=$gLibertySystem->mServices item=service key=service_guid}
	{if $service.services.content_display_section_tpl && !empty($gContent) && $gContent->hasService( $service_guid )}
		{include file=$service.services.content_display_section_tpl}
	{/if}
{/foreach}
</div>
