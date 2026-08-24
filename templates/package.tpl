{strip}
{* New, package_page.php-only wrapper - deliberately NOT calendar.tpl. No
   {jstabs}/"Display Options" tab (single fixed content type, nothing to
   choose) - straight to the grid. Grid markup below is a package_page.php-
   local copy of calendar.tpl's own (kept deliberately separate rather than
   a shared include, so nothing here can ever affect the legacy calendar
   pages) - uses the plain $viewMode variable package_page.php assigns,
   not $smarty.session.calendar.view_mode (that session key is index.php's
   own, package_page.php uses its own per-package session slot instead). *}
<div class="display calendar">
	<div class="header">
		<h1>{$pkgTitle|escape}</h1>
	</div>
	<div class="body">
		{include file="bitpackage:calendar/package_nav_inc.tpl"}

		<table class="data caltable {$viewMode}">
			<caption>{tr}Selection: {$navigation.focus_date|cal_date_format:"%A %d of %B, %Y %Z"}{/tr}</caption>
			{if $viewMode eq 'day'}
				<tr>
					<th style="width:15%;">{tr}Time{/tr}</th>
					<th>{tr}Events{/tr}</th>
				</tr>
				{foreach item=time from=$calDay}
					<tr class="{cycle values="odd,even"}">
						<th>{$time.time|cal_date_format:"%H:%M"}</th>
						<td class="calitems">
							{foreach from=$time.items item=item}
								{if $item.cell_html}
									{$item.cell_html}
								{else}
									{assign var=over value=$item.over}
									<div class="cal{$item.content_type_guid}">
										<a href="{$smarty.const.BIT_ROOT_URL}index.php?content_id={$item.content_id}">
											{capture assign=itemurl}{$smarty.const.CALENDAR_PKG_URL}box.php?content_id={$item.content_id}{/capture}
											<img style="padding:0px 4px;" src="/config/images/plus-sign.png" title="{tr}Detailed Information{/tr}" alt="{tr}Detailed Information{/tr}" {if $gBitSystem->isFeatureActive('calendar_ajax_popups')}{popup fullhtml=1 sticky=1 closeclick=1 target=$itemurl}{else}{popup fullhtml=1 text=$over|escape:"javascript"|escape:"html"}{/if} /> {$item.title|escape|default:"?"}
										</a>
									</div>
								{/if}
							{/foreach}
						</td>
					</tr>
				{/foreach}
			{elseif $viewMode eq 'weeklist'}
				{foreach from=$calMonth item=week}
					{counter assign=weekday print=false start=0}
					{foreach from=$week item=day}
						<tr>
							<th style="width:10%">
								<a href="{$baseCalendarUrl}&amp;view_mode=day&amp;todate={$day.day}">
									{$dayNames.$weekday} - {$day.day|cal_date_format:"%d"}
								</a>
								{counter assign=weekday print=false}
							</th>
						</tr>
						<tr>
							<td class="calitems {if $day.day eq $navigation.display_focus_date} current{/if}{if $day.day eq $navigation.today} highlight{/if} {cycle values="odd,even"}">
								{if $day.day|cal_date_format:"%m" eq $navigation.focus_month || $viewMode eq "week"}
									{foreach from=$day.items item=item}
										{if $item.cell_html}
											{$item.cell_html}
										{else}
											{assign var=over value=$item.over}
											{capture assign=itemurl}{$smarty.const.CALENDAR_PKG_URL}box.php?content_id={$item.content_id}{/capture}
											<div class="cal{$item.content_type_guid}" style="float:left;width:50%;">
												<a href="{$smarty.const.BIT_ROOT_URL}index.php?content_id={$item.content_id}">
													<img style="padding:0px 4px;" src="/config/images/plus-sign.png" title="{tr}Detailed Information{/tr}" alt="{tr}Detailed Information{/tr}" {if $gBitSystem->isFeatureActive('calendar_ajax_popups')}{popup fullhtml=1 sticky=1 closeclick=1 target=$itemurl}{else}{popup fullhtml=1 text=$over|escape:"javascript"|escape:"html"}{/if} /> {$item.title|escape|default:"?"}
												</a>
											</div>
										{/if}
									{/foreach}
								{else}
									&nbsp;
								{/if}
							</td>
						</tr>
					{/foreach}
				{/foreach}
			{else}
				<tr>
					<th style="width:2%;"></th>
					{foreach from=$dayNames item=dayName}
						<th style="width:14%">{$dayName}</th>
					{/foreach}
				</tr>

				{foreach from=$calMonth key=week_num item=week}
					<tr>
						<th><a href="{$baseCalendarUrl}&amp;view_mode=week&amp;todate={$week.6.day}">{$week_num}</a></th>
						{foreach from=$week item=day}
							{if $viewMode eq "month"}
								{if $day.day|cal_date_format:"%m" eq $navigation.focus_month}
									{cycle values="odd,even" print=false advance=false}
								{else}
									{cycle values="notmonth" print=false advance=false}
								{/if}
							{else}
								{cycle values="odd,even" print=false advance=false}
							{/if}

							<td class="calitems {if $day.day eq $navigation.display_focus_date} current{/if}{if $day.day eq $navigation.today} highlight{/if} {cycle values="odd,even"}">
								{if $day.day|cal_date_format:"%m" eq $navigation.focus_month || $viewMode eq "week"}
									<div class="calnumber">
										<a href="{$baseCalendarUrl}&amp;view_mode=day&amp;todate={$day.day}">{$day.day|cal_date_format:"%d"}</a>
									</div>

									{* - Cell Content - *}
									{foreach from=$day.items item=item}
										{if $item.cell_html}
											{$item.cell_html}
										{else}
											{assign var=over value=$item.over}
											{capture assign=itemurl}{$smarty.const.CALENDAR_PKG_URL}box.php?content_id={$item.content_id}{/capture}
											<div class="cal{$item.content_type_guid}">
												<a href="{$smarty.const.BIT_ROOT_URL}index.php?content_id={$item.content_id}">
													<img style="padding:0px 4px;" src="/config/images/plus-sign.png" title="{tr}Detailed Information{/tr}" alt="{tr}Detailed Information{/tr}"
													{if $gBitSystem->isFeatureActive('calendar_ajax_popups')}
													{popup fullhtml=1 target=$itemurl sticky=1 closeclick=1}{else}
													{popup fullhtml=1 text=$over|escape:"javascript"|escape:"html"}{/if}
													/>
													{$item.title|escape|truncate:$trunc:"..."|default:"?"}
												</a>
											</div>
										{/if}
									{/foreach}
								{else}
									&nbsp;
								{/if}
							</td>
						{/foreach}
					</tr>
				{/foreach}
			{/if}
		</table>
	</div><!-- end .body -->
</div><!-- end .calendar -->
{/strip}
