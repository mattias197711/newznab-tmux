<ul class="breadcrumb">
	{if isset($catname)}
		{assign var="catsplit" value=">"|explode:$catname}
	{/if}
	<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
	/ {if isset($catsplit[0])} {$catsplit[0]}{/if} / {if isset($catsplit[1])} {$catsplit[1]}{/if}
</ul>

{$site->adbrowse}

{if isset($shows)}
<div style="text-align: center;">
<div class="btn-group">
	<a class="btn btn-small" href="{$smarty.const.WWW_TOP}/series" title="View available TV series">Series List</a> |
	<a class="btn btn-small" title="Manage your shows" href="{$smarty.const.WWW_TOP}/myshows">Manage My Shows</a> |
	<a class="btn btn-small" title="All releases in your shows as an RSS feed" href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss <i class="fa fa-rss"></i></a>
</div>
</div>
<br/>
{/if}

{if $results|@count > 0}

<form id="nzb_multi_operations_form" action="get">

	<div class="well well-sm">
		<div class="nzb_multi_operations">
			<table width="100%">
				<tr>
					<td width="30%">
						With Selected:
						<div class="btn-group">
							<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
							<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
							{if isset($sabintegrated) && $sabintegrated !=""}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
						</div>
						{if isset($covgroup) && $covgroup != ''}View:
							<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">Covers
							</a>
							|
							<b>List</b>
							<br/>
						{/if}
					</td>
					<td width="50%">
						<div style="text-align: center;">
							{$pager}
						</div>
					</td>
					<td width="20%">
						<div class="pull-right">
						<a class="btn btn-small" title="All releases in your shows as an RSS feed" href="{$smarty.const.WWW_TOP}/rss?t={$category}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss <i class="fa fa-rss"></i></a>
						{if isset($isadmin)}
							Admin:
							<div class="btn-group">
								<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
								<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
							</div>
						{/if}
						{if isset($section) && $section != ''}
							<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}"><i class="fa fa-th-list"></i></a>
						{/if}
						&nbsp;
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<table style="100%" class="data highlight icons table" id="browsetable">
		<tr>
			<th style="padding-top:0; padding-bottom:0;">
				<input id="chkSelectAll" type="checkbox" class="nzb_check_all" />
				<label for="chkSelectAll" style="display:none;">Select All</label>
			</th>
			<th>Name</th>
			<th>Category</th>
			<th>Posted</th>
			<th>Size</th>
			<th>Files</th>
			<th>Grabs</th>
			<th>Action</th>
		</tr>
		{foreach $results as $result}
		<tr class="{cycle values=",alt"}" id="guid{$result.guid}">
			{if (strpos($category, '60') !== false)}
					<td class="check" width="25%"><input id="chk{$result.guid|substr:0:7}"
					 type="checkbox" class="nzb_check"
					 value="{$result.guid}"/>
					{if $result.jpgstatus == 1}
						<img width="300" height="200" src="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg" />
					{else}
						{if $result.haspreview == 1}
							<img width="300" height="200" src="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" />
						{/if}
					{/if}
					</td>
				{else}
				<td class="check"><input id="chk{$result.guid|substr:0:7}"
				 type="checkbox" class="nzb_check"
				 value="{$result.guid}"/></td>
			{/if}
			<td class="item">
				<label for="chk{$result.guid|substr:0:7}">
					<a class="title" title="View details"  href="{$smarty.const.WWW_TOP}/details/{$result.guid}"><h5>{$result.searchname|escape:"htmlall"|replace:".":" "} {if $lastvisit|strtotime < $result.adddate|strtotime} <a href="#" class="badge badge-success">New</a>{/if}</h5></a>
				</label>
				{if $result.passwordstatus == 2}
				<i class="fa fa-lock"></i>
				{elseif $result.passwordstatus == 1}
				<i class="fa fa-lock"></i>
				{/if}
				{if isset($result.nuketype) && $result.nuketype != ''}
				&nbsp;<img title="{$result.nuketype}" src="{$smarty.const.WWW_THEMES}/shared/images/icons/nuke.png" width="10" height="10" alt="{$result.nuketype}" />
				{/if}
				<div class="resextra">
					<div class="btns">{strip}
						{if $result.nfoid > 0}
						<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo badge halffade" rel="nfo">Nfo</a>
						{/if}
						{if $result.imdbid > 0}
						<a href="{$smarty.const.WWW_TOP}/movies?imdb={$result.imdbid}" title="View movie info" class="badge badge-inverse halffade" rel="movie" >Movie</a>
						{/if}
						{if $result.jpgstatus == 1&& $userdata.canpreview == 1}
							<a href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
							title="Thumbnail" class="modal_prev badge badge-success halffade" rel="Thumbnail">Thumbnail</a>
							{else}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}
									<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}"
									title="Screenshot" class="modal_prev badge badge-success halffade" rel="preview">Preview</a>
								{/if}
						{/if}
						{if $result.haspreview == 2 && $userdata.canpreview == 1}
						<a href="#" name="audio{$result.guid}" title="Listen to Preview" class="audioprev badge badge-success halffade" rel="audio">Listen</a>
						<audio id="audprev{$result.guid}" src="{$smarty.const.WWW_TOP}/covers/audio/{$result.guid}.mp3" preload="none"></audio>
						{/if}
						{if $result.musicinfo_id > 0}
						<a href="#" name="name{$result.musicinfo_id}" title="View music info" class="modal_music badge badge-success halffade" rel="music" >Cover</a>
						{/if}
						{if $result.consoleinfo_id > 0}
						<a href="#" name="name{$result.consoleinfo_id}" title="View console info" class="modal_console badge badge-success halffade" rel="console" >Cover</a>
						{/if}
						{if $result.bookinfo_id > 0}
						<a href="#" name="name{$result.bookinfo_id}" title="View book info" class="modal_book badge badge-success halffade" rel="console" >Cover</a>
						{/if}
						{if $result.videos_id > 0}
						<a class="badge badge-inverse halffade" href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}">View Series</a>
						{/if}
						{if $result.anidbid > 0}
						<a class="badge badge-inverse halffade" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}" title="View all episodes">View Anime</a>
						{/if}
						{if !empty($result.firstaired)}
							<span class="seriesinfo badge badge-success halffade" title="{$result.guid}"> Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>
						{/if}
						{if $result.videostatus > 0}
							<span class="badge badge-inverse halffade" id="{$result.guid}" title="Release has video sample">Sample</span>
						{/if}
						{if $result.reid > 0}
						<span class="mediainfo badge badge-inverse halffade" title="{$result.guid}">Media</span>
						{/if}
						{if $result.predb_id > 0}
						<span class="preinfo badge badge-inverse halffade" title="{$result.predb_id}">PreDb</span>
						{/if}
							{if !empty($result.failed)}
								<span class="badge badge-inverse"><i class ="fa fa-thumbs-o-up"></i> {$result.grabs} Grab{if $result.grabs != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$result.failed} Failed Download{if $result.failed != 1}s{/if}</span>
							{/if}

						{/strip}
					</div>
				</div>
			</td>

			<td class="less">
				<a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categories_id}">{$result.category_name}</a>
			</td>

			<td class="less mid" title="{$result.postdate}">{$result.postdate|timeago}</td>

			<td class="less right">
				{$result.size|fsize_format:"MB"}
				{if $result.completion > 0}<br />
				{if $result.completion < 100}
				<span class="label label-important">{$result.completion}%</span>
				{else}
				<span class="label label-success">{$result.completion}%</span>
				{/if}
				{/if}
			</td>
			<td class="less mid">
				<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a> <i class="fa fa-file"></i>

				{if $result.rarinnerfilecount > 0}
				<div class="rarfilelist">
					<img src="{$smarty.const.WWW_THEMES}/shared/images/icons/magnifier.png" alt="{$result.guid}" class="tooltip" />
				</div>
				{/if}
			</td>
			<td class="less mid"><span class="label label-default">{$result.grabs} Grab{if $result.grabs != 1}s{/if}</span></td>
			<td class="icons" style='width:100px;'>
				<ul class="inline">
					<li>
						<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"></a>
					</li>
					<li>
						<a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket">
						</a>
					</li>
					{if isset($sabintegrated) && $sabintegrated !=""}
					<li>
						<a class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  href="#" title="Send to queue">
						</a>
					</li>
					{/if}
                    {if $weHasVortex}
                        <li>
                            <a class="icon icon_nzb fa fa-cloud-downloadvortex" href="#" title="Send to NZBVortex">
                                <img class="icon icon_nzb fa fa-cloud-downloadvortex" alt="Send to my NZBVortex" src="{$smarty.const.WWW_THEMES}/shared/images/icons/vortex/bigsmile.png">
                            </a>
                        </li>
                    {/if}
				</ul>
			</td>
		</tr>
		{/foreach}
	</table>

	{if $results|@count > 10}
	<div class="well well-sm">
		<div class="nzb_multi_operations">
			<table width="100%">
				<tr>
					<td width="30%">
						With Selected:
						<div class="btn-group">
							<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
							<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
							{if isset($sabintegrated) && $sabintegrated !=""}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
						</div>
						{if isset($section) && $section != ''}View:
							<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}">Covers</a>
							|
							<b>List</b>
							<br/>
						{/if}
					</td>
					<td width="50%">
						<div style="text-align: center;">
							{$pager}
						</div>
					</td>
					<td width="20%">
						<div class="pull-right">
						{if isset($isadmin)}
							Admin:
							<div class="btn-group">
								<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
								<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
							</div>
						{/if}
						{if isset($section) && $section != ''}
							<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}"><i class="fa fa-th-list"></i></a>
						{/if}
						{if (strpos($category, '60')  !== false)}
								<a href="{$smarty.const.WWW_TOP}/xxx"><i class="fa fa-lg fa-camera-retro"></i></a>
						{/if}
						{if (strpos($category, '20') !== false)}
							<a href="{$smarty.const.WWW_TOP}/movies"><i class="fa fa-lg fa-camera-retro"></i></a>
						{/if}
						&nbsp;
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>

	{/if}

</form>

{else}
<div class="alert">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<strong>Sorry!</strong> There is nothing here at the moment.
</div>
{/if}
