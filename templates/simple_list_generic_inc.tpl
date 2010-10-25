<div class="listing {$contentType|strtolower}">
	{foreach from=$contentList item=item name=content_list}
	<div class="row">
		<h2 class="title">{$item.display_link}</h2>
		<div class="date">
			{$item.last_modified|bit_short_date}
		</div>
		<div class="body">
			{$item.summary}
		</div>
		<a href="{$item.display_url}">Read More &raquo;</a>
	</div>
	{if !$smarty.foreach.content_list.last}
	<div class="hr"></div>
	{/if}
	{/foreach}
</div>
