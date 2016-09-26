<div class="header">
	<h2>Profile > <strong>{$user.username|escape:"htmlall"}</strong></h2>

	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Profile / {$user.username|escape:"htmlall"}
		</ol>
	</div>
</div>
<div class="row">
	<div class="box col-md-12">
		<div class="box-content">
			<div class="row">
				<div class="col-lg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							<div class="panel-body">
								<ul class="nav nav-tabs nav-primary">
									<li class="active"><a href="#tab2_1" data-toggle="tab"><i class="fa fa-user"></i>
											Main</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade active in" id="tab2_1">
										<div id="tab-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
											<table cellpadding="0" cellspacing="0" width="100%">
												<tbody>
												<tr valign="top">
													<td>
														<table class="table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-aqua-active">
																<td colspan="2" style="padding-left: 8px;"><strong>General</strong>
																</td>
															</tr>
															<tr>
																<th width="200">Username</th>
																<td>{$user.username|escape:"htmlall"}</td>
															</tr>
															{if isset($isadmin) || !$publicview}
																<tr>
																	<th width="200" title="Not public">E-mail</th>
																	<td>{$user.email}</td>
																</tr>
															{/if}
															<tr>
																<th width="200">Registered</th>
																<td>{$user.createddate|date_format}
																	({$user.createddate|timeago} ago)
																</td>
															</tr>
															<tr>
																<th width="200">Last Login</th>
																<td>{$user.lastlogin|date_format}
																	({$user.lastlogin|timeago} ago)
																</td>
															</tr>
															<tr>
																<th width="200">Role</th>
																<td>{$user.rolename}</td>
															</tr>
															{if !empty($user.rolechangedate)}
																<tr>
																	<th width="200">Role expiration date</th>
																	<td>{$user.rolechangedate|date_format:"%A, %B %e, %Y"}</td>
																</tr>
															{/if}
															</tbody>
														</table>
														<table class="data table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-aqua-active">
																<td colspan="2" style="padding-left: 8px;"><strong>UI Preferences</strong></td>
															</tr>
															<tr>
																<th>Theme:</th>
																<td>{$user.style}</td>
															</tr>
															<tr>
																<th>Cover view:</th>
																<td>
																	{if $user.movieview == "1"}View movie covers{else}View standard movie category{/if}<br/>
																	{if $user.musicview == "1"}View music covers{else}View standard music category{/if}<br/>
																	{if $user.consoleview == "1"}View console covers{else}View standard console category{/if}<br/>
																	{if $user.gameview == "1"}View games covers{else}View standard games category{/if}<br/>
																	{if $user.bookview == "1"}View book covers{else}View standard book category{/if}<br/>
																	{if $user.xxxview == "1"}View xxx covers{else}View standard xxx category{/if}<br/>
																</td>
															</tr>
															</tbody>
														</table>
														<table class="data table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-aqua-active">
																<td colspan="2" style="padding-left: 8px;"><strong>API &
																		Downloads</strong></td>
															</tr>
															<tr>
																<th>API Hits last 24 hours</th>
																<td>
																	<span id="uatd">{$apirequests}</span> {if isset($isadmin) && $apirequests > 0}
																	<a
																			onclick="resetapireq({$user.id}, 'api'); document.getElementById('uatd').innerHTML='0'; return false;"
																			href="#" class="label label-danger">
																			Reset</a>{/if}</td>
															</tr>
															<tr>
																<th>Downloads last 24 hours</th>
																<td><span id="ugrtd">{$grabstoday}</span> /
																	{if $user.grabs >= $user.downloadrequests}&nbsp;&nbsp;
																		<small>(Next DL
																		in {($grabstoday.nextdl/3600)|intval}
																		h {($grabstoday.nextdl/60) % 60}
																		m)</small> {else} {$user.downloadrequests} {/if}
																	{if isset($isadmin) && $grabstoday > 0}
																		<a onclick="resetapireq({$user.id}, 'grabs'); document.getElementById('ugrtd').innerHTML='0'; return false;"
																			href="#" class="label label-danger">
																			Reset</a>
																	{/if}</td>
															</tr>
															<tr>
																<th>Downloads Total</th>
																<td>{$user.grabs}</td>
															</tr>
															{if isset($isadmin) || !$publicview}
																<tr>
																	<th title="Not public">API/RSS Key</th>
																	<td>
																		<a href="{$smarty.const.WWW_TOP}rss?t=0&amp;dl=1&amp;i={$user.id}&amp;r={$user.rsstoken}">{$user.rsstoken}</a>
																		<a href="{$smarty.const.WWW_TOP}profileedit?action=newapikey"
																		   class="label label-danger">GENERATE NEW
																			KEY</a>
																	</td>
																</tr>
																<tr>
																	<th title="Admin Notes">Notes:</th>
																	<td>{$user.notes|escape:htmlall}{if $user.notes|count_characters > 0}<br/>{/if}<a href="{$smarty.const.WWW_TOP}/admin/user-edit.php?id={$user.id}#notes" class="label label-info">Add/Edit</a></td>
																</tr>
															{/if}
															</tbody>
														</table>
														{if ($user.id == $userdata.id || $isadmin) && $site->registerstatus == 1}
															<table class="data table table-condensed table-striped table-responsive table-hover">
																<tbody>
																<tr class="bg-aqua-active">
																	<td colspan="2" style="padding-left: 8px;"><strong>Invites</strong>
																	</td>
																</tr>
																<tr>
																<tr>
																	<th title="Not public">Send Invite:</th>
																	<td>{$user.invites}
																		{if $user.invites > 0}
																			[
																			<a id="lnkSendInvite"
																			   onclick="return false;" href="#">Send
																				Invite</a>
																			]
																			<span title="Your invites will be reduced when the invitation is claimed."
																				  class="invitesuccess"
																				  id="divInviteSuccess"></span>
																			<span class="invitefailed"
																				  id="divInviteError"></span>
																			<div style="display:none;" id="divInvite">
																				<form id="frmSendInvite" method="GET">
																					<label for="txtInvite">Email</label>:
																					<input type="text" id="txtInvite"/>
																					<input type="submit" value="Send"/>
																				</form>
																			</div>
																		{/if}
																	</td>
																</tr>
																{if $userinvitedby && $userinvitedby.username != ""}
																<tr>
																	<th width="200">Invited By</th>
																	{if $privileged || !$privateprofiles}
																		<td>
																			<a title="View {$userinvitedby.username}'s profile"
																			   href="{$smarty.const.WWW_TOP}/profile?name={$userinvitedby.username}">{$userinvitedby.username}</a>
																		</td>
																	{else}
																		<td>
																			{$userinvitedby.username}
																		</td>
																	{/if}
																</tr>
																	{/if}
																</tbody>
															</table>
														{/if}
														{if isset($isadmin) && $downloadlist|@count > 0}
														<table class="data table table-condensed table-striped table-responsive table-hover">
															<tbody>
															<tr class="bg-aqua-active">
																<td colspan="2" style="padding-left: 8px;"><strong>Downloads for user</strong>
																</td>
															</tr>
															<tr>
																<th>date</th>
																<th>release</th>
															</tr>
															{foreach $downloadlist as $download}
																{if $download@iteration == 10}
																	<tr class="more"><td colspan="3"><a onclick="$('tr.extra').toggle();$('tr.more').toggle();return false;" href="#">show all...</a></td></tr>
																{/if}
																<tr {if $download@iteration >= 10}class="extra" style="display:none;"{/if}>
																	<td width="80" title="{$download.timestamp}">{$download.timestamp|date_format}</td>
																	<td>{if $download.guid == ""}n/a{else}<a href="{$smarty.const.WWW_TOP}/details/{$download.guid}">{$download.searchname}</a>{/if}</td>
																</tr>
															{/foreach}
														</table>
														{/if}
													</td>
												</tr>
												</tbody>
											</table>
											</div>
										</div>
									</div>
								</div>
								{if isset($isadmin) || !$publicview}
									<a class="btn btn-primary" href="{$smarty.const.WWW_TOP}profileedit">Edit
										Profile</a>
								{/if}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
