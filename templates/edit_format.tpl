{strip}

{if $translationsList}
	<div class="row">
		{formfeedback error=$errors.format}
		{formlabel label="Language" for="lang_code"}
		{forminput}
			{if $translateFrom}
				<input type="hidden" name="i18n[from_id]" value="{$translateFrom->mContentId}" />
			{/if}
			<select name="i18n[lang_code]" id="lang_code">
				{foreach from=$translationsList key=langCode item=lang}
					<option value="{$langCode}" {if $smarty.request.i18n.lang_code==$langCode || $gContent->mInfo.lang_code==$langCode || ( $langCode==$gBitSystem->getConfig('bitlanguage') && !$smarty.request.i18n.lang_code && !$gContent->getField('lang_code') )}selected="selected" {/if}>{$lang.native_name}</option>
				{/foreach}
			</select>
			{formhelp note="The language of this page"}
		{/forminput}
	</div>
{/if}

{* We have to count these first because of the tikiwiki format options which may show even if it is the only format option. *}
{assign var=numformat value=0}
{foreach name=formatPlugins from=$gLibertySystem->mPlugins item=plugin key=guid}
	{if $plugin.is_active eq 'y' and $plugin.edit_field and $plugin.plugin_type eq 'format'}
		{assign var=numformat value=$numformat+1}
		{if $plugin.plugin_guid == "tikiwiki"}
			{assign var=format_options value=true}
		{/if}
	{/if}
{/foreach}
{if $numformat > 1 || $format_options}
	<div class="row">
		{formfeedback error=$errors.format}
		{formlabel label="Content Format"}
		{foreach name=formatPlugins from=$gLibertySystem->mPlugins item=plugin key=guid}
			{if $plugin.is_active eq 'y' and $plugin.edit_field and $plugin.plugin_type eq 'format'}
				{forminput}
					{if $numformat > 1}
						<label>
							<input type="radio" name="{$format_guid_variable|default:"format_guid"}" value="{$plugin.edit_field}"
							{if $gContent->mInfo.format_guid eq $plugin.plugin_guid
								} checked="checked"{
							elseif !$gContent->mInfo.format_guid and $plugin.plugin_guid eq $gBitSystem->getConfig('default_format', 'tikiwiki')
								} checked="checked"{
							/if
							} onclick="
								{if $gBitSystem->isPackageActive('quicktags')}
									{foreach from=$gLibertySystem->mPlugins item=tag key=guid}
										{if $tag.is_active eq 'y' and $tag.edit_field and $tag.plugin_type eq 'format'}
											{if $tag.plugin_guid eq $plugin.plugin_guid}
												showById
											{else}
												hideById
											{/if}
											('qt{$textarea_id}{$tag.plugin_guid}'); 
										{/if}
									{/foreach}
								{/if}
							"
						/> {$plugin.edit_label}
						</label>
					{/if}
					{if $plugin.plugin_guid == "tikiwiki"}
						{if $numformat > 1}		
							&nbsp;&nbsp;
						{/if}
						{if !$gBitSystem->isFeatureActive('content_force_allow_html')}
							{if $gBitUser->hasPermission( 'p_liberty_enter_html' ) || $gBitSystem->isFeatureActive('content_allow_html')}
								<label><input type="checkbox" name="preferences[content_enter_html]" value="y" id="html" {if $gContent->mPrefs.content_enter_html}checked="checked" {/if}/> {tr}Allow HTML{/tr}</label>
							{elseif is_object($gContent) && $gContent->getPreference( 'content_enter_html' )}
								[ {tr}HTML will remain as HTML{/tr} ]
							{else}
								[ {tr}HTML will be escaped{/tr} ]
							{/if}
						{/if}
					{/if}
					{formhelp note=`$plugin.edit_help`}
				{/forminput}
			{/if}
		{/foreach}
		{if $numformat > 1}
			{forminput}
				{formhelp note="Choose what kind of syntax you want to submit your data in."}
			{/forminput}
		{else}
			<input type="hidden" name="{$format_guid_variable|default:"format_guid"}" value="{$gBitSystem->getConfig('default_format','tikiwiki')}" />
		{/if}
	</div>
{else}
	{* if there was one format in the liberty plugins hash then use it and display a label so user knows what format is being used, otherwise use default and hide it*}
	{if $numformat eq 1}
		<div class="row">
			{formlabel label="Content Format: `$plugin.edit_label`"}
		</div>
	{/if}
	<input type="hidden" name="{$format_guid_variable|default:"format_guid"}" value="{if $numformat eq 1}{$plugin.edit_field}{else}{$gBitSystem->getConfig('default_format','tikiwiki')}{/if}" />
{/if}

{/strip}
