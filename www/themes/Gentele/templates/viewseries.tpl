{if isset($nodata) && $nodata != ""}
	<div class="header">
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ TV Series
			</ol>
		</div>
	</div>
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Sorry!</strong>
		{$nodata}
	</div>
{else}
	<div class="header">
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ TV Series
			</ol>
		</div>
	</div>
	{if $catname != ''}<span class="text-info h5">Current category shown: {$catname|escape:"htmlall"}</span>{/if}
	<div class="well well-sm">
		<div class="tvseriesheading">
			<h1>
				<div style="text-align: center;">{$seriestitles} ({$show.publisher})</div>
			</h1>
			{if $show.image != 0}
				<div style="text-align: center;">
					<img class="shadow img img-polaroid" style="max-height:300px;" alt="{$seriestitles} Logo"
						 src="{$smarty.const.WWW_TOP}/covers/tvshows/{$show.id}.jpg"/>
				</div>
				<br/>
			{/if}
			<p>
				<span class="descinitial">{$seriessummary|escape:"htmlall"|nl2br|magicurl}</span>
			</p>
		</div>
	</div>
	<div class="btn-group">
		<a class="btn btn-sm btn-default"
		   href="{$smarty.const.WWW_TOP}/rss?show={$show.id}{if $category != ''}&amp;t={$category}{/if}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS
			for TV Show <i class="fa fa-rss"></i></a>
		{if $show.tvdb > 0}
			<a class="btn btn-sm btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$show.tvdb}"
			   title="View at TheTVDB">TheTVDB</a>
		{/if}
		{if $show.tvmaze > 0}
			<a class="btn btn-sm btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://tvmaze.com/shows/{$show.tvmaze}"
			   title="View at TVMaze">TVMaze</a>
		{/if}
		{if $show.trakt > 0}
			<a class="btn btn-sm btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$show.trakt}"
			   title="View at TraktTv">Trakt</a>
		{/if}
		{if $show.tvrage > 0}
			<a class="btn btn-sm btn-info" target="_blank"
			   href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$show.tvrage}"
			   title="View at TV Rage">TV Rage</a>
		{/if}
		{if $show.tmdb > 0}
			<a class="btn btn-sm btn-info" target="_blank"
			   href="{$site->dereferrer_link}https://www.themoviedb.org/tv/{$show.tmdb}"
			   title="View at TheMovieDB">TMDB</a>
		{/if}
	</div>
	<br/>
	<div class="box-body"
	<form id="nzb_multi_operations_form" action="get">
		<div class="well well-sm">
			<div class="nzb_multi_operations">
				With Selected:
				<div class="btn-group">
					<button type="button"
							class="nzb_multi_operations_download btn btn-sm btn-success"
							data-toggle="tooltip" data-placement="top" title data-original-title="Download NZBs">
						<i class="fa fa-cloud-download"></i></button>
					<button type="button"
							class="nzb_multi_operations_cart btn btn-sm btn-info"
							data-toggle="tooltip" data-placement="top" title
							data-original-title="Send to my Download Basket">
						<i class="fa fa-shopping-basket"></i></button>

					{if isset($sabintegrated) && $sabintegrated !=""}
						<button type="button"
								class="nzb_multi_operations_sab btn btn-sm btn-primary"
								data-toggle="tooltip" data-placement="top" title data-original-title="Send to Queue">
							<i class="fa fa-share"></i></button>
					{/if}
					{if isset($isadmin)}
						<input type="button"
							   class="nzb_multi_operations_edit btn btn-sm btn-warning"
							   value="Edit"/>
						<input type="button"
							   class="nzb_multi_operations_delete btn btn-sm btn-danger"
							   value="Delete"/>
					{/if}
				</div>
				<div>
					<a title="Manage your shows" href="{$smarty.const.WWW_TOP}/myshows">My Shows</a> :
					<div class="btn-group">
						{if $myshows.id != ''}
							<a class="myshows btn btn-sm btn-warning" data-toggle="tooltip" data-placement="top" title
							   data-original-title="Edit Categories for this show"
							   href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
							   rel="edit" name="series{$show.id}">
								<i class="fa fa-pencil"></i>
							</a>
							<a class="myshows btn btn-sm btn-danger" data-toggle="tooltip" data-placement="top" title
							   data-original-title="Remove from My Shows"
							   href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
							   rel="remove" name="series{$show.id}">
								<i class="fa fa-minus"></i>
							</a>
						{else}
							<a class="myshows btn btn-sm btn-success" data-toggle="tooltip" data-placement="top" title
							   data-original-title="Add to My Shows"
							   href="{$smarty.const.WWW_TOP}/myshows/add/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
							   rel="add" name="series{$show.id}">
								<i class="fa fa-plus"></i>
							</a>
						{/if}
					</div>
				</div>
			</div>
			<br clear="all"/>
			<a id="latest"></a>

			<div class="row">
				<div class="col-lg-12 col-sm-12 col-xs-12">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							<div class="tabbable">
								<ul class="nav nav-tabs">
									{foreach $seasons as $seasonnum => $season name = "seas"}
										<li {if $smarty.foreach.seas.first}class="active"{/if}><a
													title="View Season {$seasonnum}" href="#{$seasonnum}"
													data-toggle="tab">{$seasonnum}</a></li>
									{/foreach}
								</ul>
								<div class="tab-content">
									{foreach $seasons as $seasonnum => $season name = "tv"}
										{if empty($seasonnum)}{$seasonnum = 'Packs'}{/if}
										<div class="tab-pane{if $smarty.foreach.tv.first} active{/if} fade in"
											 id="{$seasonnum}">
											<table class="tb_{$seasonnum} data table table-striped responsive-utilities jambo-table"
												   id="browsetable">
												<thead>
												<tr>
													<th>Ep</th>
													<th>Name</th>
													<th> Select All <input id="check-all" type="checkbox" class="flat-all"/></th>
													<th>Category</th>
													<th>Posted</th>
													<th>Size</th>
													<th>Action</th>
												</tr>
												</thead>
												{foreach $season as $episodes}
													{foreach $episodes as $result}
														<tr class="{cycle values=",alt"}"
															id="guid{$result.guid}">
															{if $result@total>1 && $result@index == 0}
																<td rowspan="{$result@total}" width="30">
																	<h4>{$episodes@key}</h4></td>
															{elseif $result@total == 1}
																<td><h4>{$episodes@key}</h4></td>
															{/if}
															<td>
																<a title="View details"
																   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>

																<div>
																	{if $result.nfoid > 0}<span>
																		<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
																		   class="modal_nfo label label-primary text-muted">NFO</a>
																		</span>{/if}
																	{if $result.image == 1 && $userdata.canpreview == 1}
																	<a
																			href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
																			name="name{$result.guid}"
																			title="View Screenshot"
																			class="modal_prev label label-primary"
																			rel="preview">Preview</a>{/if}
																	<span class="label label-primary">{$result.grabs}
																		Grab{if $result.grabs != 1}s{/if}</span>
																	{if $result.firstaired != ""}<span
																		class="label label-success"
																		title="{$result.title} Aired on {$result.firstaired|date_format}">
																		Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>{/if}
																	{if $result.reid > 0}<span
																		class="mediainfo label label-primary"
																		title="{$result.guid}">Media</span>{/if}
																</div>
															</td>
															<td width="10"><input
																		id="guid{$result.guid}"
																		type="checkbox"
																		class="flat" name="table_data{$seasonnum}"
																		value="{$result.guid}"/></td>
															<td>
																<span class="label label-primary">{$result.category_name}</span>
															</td>
															<td width="40"
																title="{$result.postdate}">{$result.postdate|timeago}</td>
															<td>
																{$result.size|fsize_format:"MB"}
															</td>
															<td>
																<a href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}" class="icon_nzb text-muted"><i
																			class="fa fa-cloud-download text-muted"
																			data-toggle="tooltip" data-placement="top" title
																			data-original-title="Download NZB"></i></a>
																<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
																			class="fa fa-comments-o text-muted"
																			data-toggle="tooltip" data-placement="top" title
																			data-original-title="Comments"></i></a>
																<a href="#"><i
																			id="guid{$result.guid}"
																			class="icon_cart text-muted fa fa-shopping-basket" data-toggle="tooltip"
																			data-placement="top" title
																			data-original-title="Send to my download basket"></i></a>
																{if isset($sabintegrated) && $sabintegrated !=""}
																	<a href="#">
																		<i	id="guid{$result.guid}"
																			  class="icon_sab text-muted fa fa-share"
																			  data-toggle="tooltip"
																			  data-placement="top" title
																			  data-original-title="Send to my Queue">
																		</i>
																	</a>
																{/if}
																{if $weHasVortex}
																	<a href="#" class="icon_vortex text-muted"><i
																				class="fa fa-share" data-toggle="tooltip" data-placement="top"
																				title data-original-title="Send to NZBVortex"></i></a>
																{/if}
															</td>
														</tr>
													{/foreach}
												{/foreach}
											</table>
										</div>
									{/foreach}
								</div>
							</div>
	</form>
{/if}
