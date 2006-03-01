{strip}
<div class="floaticon">{bithelp}</div>

<div class="admin liberty">
	<div class="header">
		<h1>{tr}Comment Settings{/tr}</h1>
	</div>

	<div class="body">
		{form legend="Comment Settings"}
			{foreach from=$commentSettings key=item item=output}
				<div class="row">
					{formlabel label=`$output.label` for=$item}
					{forminput}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
						{formhelp note=`$output.note` page=`$output.page`}
					{/forminput}
				</div>
			{/foreach}

			<div class="row">
				{formlabel label="Comments per Page" for="comments_per_page"}
				{forminput}
					<select name="comments_per_page" id="comments_per_page">
						<option value="5" {if $gBitSystem->getConfig('comments_per_page') eq 5}selected="selected"{/if}>5</option>
						<option value="10" {if $gBitSystem->getConfig('comments_per_page') eq 10}selected="selected"{/if}>10</option>
						<option value="20" {if $gBitSystem->getConfig('comments_per_page') eq 20}selected="selected"{/if}>20</option>
						<option value="50" {if $gBitSystem->getConfig('comments_per_page') eq 50}selected="selected"{/if}>50</option>
						<option value="100" {if $gBitSystem->getConfig('comments_per_page') eq 100}selected="selected"{/if}>100</option>
						<option value="999999" {if $gBitSystem->getConfig('comments_per_page') eq 999999}selected="selected"{/if}>All</option>
					</select>
					{formhelp note="Default number of comments per page."}
				{/forminput}
			</div>

			<div class="row">
				{formlabel label="Default Sort Mode" for="comments_default_ordering"}
				{forminput}
					<select name="comments_default_ordering" id="comments_default_ordering">
						<option value="commentDate_desc" {if $gBitSystem->getConfig('comments_default_ordering') eq 'commentDate_desc'}selected="selected"{/if}>{tr}Newest first{/tr}</option>
						<option value="commentDate_asc" {if $gBitSystem->getConfig('comments_default_ordering') eq 'commentDate_asc'}selected="selected"{/if}>{tr}Oldest first{/tr}</option>
						{*<option value="points_desc" {if $gBitSystem->getConfig('comments_default_ordering') eq 'points_desc'}selected="selected"{/if}>{tr}Points{/tr}</option>*}
					</select>
					{formhelp note="Select the default sort mode for comments."}
				{/forminput}
			</div>

			<div class="row">
				{formlabel label="Comments default display mode" for="comments_default_display_mode"}
				{forminput}
					<select name="comments_default_display_mode" id="comments_default_display_mode">
						<option value="threaded" {if $gBitSystem->getConfig('comments_default_display_mode') eq 'threaded'}selected="selected"{/if}>{tr}Threaded{/tr}</option>
						<option value="flat" {if $gBitSystem->getConfig('comments_default_display_mode') eq 'flat'}selected="selected"{/if}>{tr}Flat{/tr}</option>
					</select>
				{/forminput}
			</div>

			<div class="row submit">
				<input type="submit" name="change_prefs" value="{tr}Change preferences{/tr}" />
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .liberty -->
{/strip}
