{foreach from=$gLibertySystem->mServices item=service key=service_guid}
	{if $service.services.content_display_section_tpl && !empty($gContent) && $gContent->hasService( $service_guid )}
		<div class="section {$gContent->getContentTypeName()|strtolower} {$smarty.request.section}">
		{include file=$service.services.content_display_section_tpl}
		</div>
	{/if}
{/foreach}
