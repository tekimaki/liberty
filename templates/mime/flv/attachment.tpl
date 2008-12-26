{strip}
{if $wrapper.output == 'desc' || $wrapper.output == 'description'}
	{if $attachment.display_url}<a {$wrapper.href_class} href="{$wrapper.display_url|default:$attachment.display_url}">{/if}
		{$wrapper.description|escape|default:$attachment.filename}
	{if $attachment.display_url}</a>{/if}
{else}
	<{$wrapper.wrapper|default:'div'} class="mimeflv {$wrapper.class|default:'att-plugin'}"{if $wrapper.style} style="{$wrapper.style}{/if}">
		{if $attachment.media_url}
			{include file="bitpackage:liberty/mime/flv/player.tpl" caller=attachment}
		{/if}
		{if $wrapper.display_url}<a {$wrapper.href_class} href="{$wrapper.display_url}">{/if}
			{$wrapper.description|escape|default:"{tr}Full Details{/tr}"}
		{if $wrapper.display_url}</a>{/if}
	</{$wrapper.wrapper|default:'div'}>
{/if}
{/strip}
