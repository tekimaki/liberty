{strip}
{jstabs}
	{foreach from=$examples.tikiwiki key=title item=tikiwiki name=tw}
		{jstab title="$title"}
			<table class="bittable">
				<tr>
					<th style="width:50%">Example</th>
					<th style="width:50%">Result</th>
				</tr>
				{foreach from=$tikiwiki key=desc item=example}
					<tr>
						<th colspan="2" style="text-align:left;">
							{tr}{$desc}{/tr}
							<br /><small>{tr}{$example.note}{/tr}</small>
						</th>
					</tr>
					<tr>
						<td><pre><code>{$example.data|escape}</code></pre></td>
						<td>{$example.result}</td>
					</tr>
				{/foreach}
			</table>
		{/jstab}
	{/foreach}

	{jstab title="Mediawiki tables"}
		<table class="bittable">
			<tr>
				<th style="width:60%">Code</th>
				<th style="width:40%">Function</th>
			</tr>
			<tr>
				<td><pre><code>{ldelim}|</code></pre></td>
				<td>Start Table</td>
			</tr>
			<tr>
				<td><pre><code>|+ caption</code></pre></td>
				<td>Optional Table caption</td>
			</tr>
			<tr>
				<td><pre><code>
					|-<br />
					! Col 1 Heading !! Col 2 Heading !! Col 3 Heading  etc.
				</code></pre></td>
				<td>Optional Table Header Row</td>
			</tr>
			<tr>
				<td><pre><code>|- optional table parameters</code></pre></td>
				<td>Table Row</td>
			</tr>
			<tr>
				<td><pre><code>
					| Value<br />
					| optional parameters | value
				</code></pre></td>
				<td>Table Cell</td>
			</tr>
			<tr>
				<td><pre><code>
					| Value || Value || Value ...<br />
					| optional parameters | value | optional parameters | value |
				</code></pre></td>
				<td>Multiple Cells on one line</td>
			</tr>
			<tr>
				<td><pre><code>|{rdelim}</code></pre></td>
				<td>End Table</td>
			</tr>
		</table>

		<br /> <br />
		<table class="bittable">
			<caption>{tr}Applied Examples{/tr}</caption>
			{foreach from=$examples.mediawiki key=title item=example}
				<tr>
					<th style="width:60%">{$title}</th>
					<th style="width:40%">Result</th>
				</tr>
				<tr>
					<td><pre><code>{$example.data}</code></pre></td>
					<td>{$example.result}</td>
				</tr>
			{/foreach}
		</table>
	{/jstab}
{/jstabs}
{/strip}
