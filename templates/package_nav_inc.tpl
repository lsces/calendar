{strip}
{* New, package_page.php-only nav - deliberately a separate file from
   calendar_nav_inc.tpl (which index.php still uses unchanged) so nothing
   here can ever affect the legacy calendar pages. No "Show all/Only my
   items" or sort_mode links (single fixed content type, nothing to filter
   by user/sort here) - just date navigation and the view-mode switcher,
   each on its own line. *}
<table class="calnav">
	<tr>
		<td style="text-align:left;white-space:nowrap;">
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.before.year}"  title="{$navigation.before.year|bit_long_date}">&laquo;Y</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.before.month}" title="{$navigation.before.month|bit_long_date}">&laquo;M</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.before.week}"  title="{$navigation.before.week|bit_long_date}">&laquo;W</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.before.day}"   title="{$navigation.before.day|bit_long_date}">&laquo;D</a>
		</td>
		<td style="text-align:center;">
			<a href="{$baseCalendarUrl}&amp;todate={$smarty.now}" title="{$navigation.today|bit_long_date}">{tr}Today{/tr}: <strong>{$navigation.today|bit_long_date} {$navigation.tz_flag}</strong></a>
		</td>
		<td style="text-align:right;white-space:nowrap;">
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.after.day}"   title="{$navigation.after.day|bit_long_date}">D&raquo;</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.after.week}"  title="{$navigation.after.week|bit_long_date}">W&raquo;</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.after.month}" title="{$navigation.after.month|bit_long_date}">M&raquo;</a>
			<a href="{$baseCalendarUrl}&amp;todate={$navigation.after.year}"  title="{$navigation.after.year|bit_long_date}">Y&raquo;</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" style="text-align:center;">
			<form method="get" action="{$baseCalendarUrl|regex_replace:'/\?.*/':''}" style="display:inline">
				<input type="hidden" name="pkg" value="{$smarty.request.pkg|escape}" />
				<input type="date" name="todate" value="{$navigation.focus_date|date_format:'%Y-%m-%d'}" onchange="this.form.submit()" title="{tr}Jump to date{/tr}" />
			</form>
			&nbsp;&nbsp;
			<a href="{$baseCalendarUrl}&amp;view_mode=day&amp;todate={$navigation.focus_date}" class="{if $viewMode eq 'day'}highlight{/if}">{biticon ipackage=calendar iname=day iexplain=Day}</a>
			<a href="{$baseCalendarUrl}&amp;view_mode=week&amp;todate={$navigation.focus_date}" class="{if $viewMode eq 'week'}highlight{/if}">{biticon ipackage=calendar iname=week iexplain=Week}</a>
			<a href="{$baseCalendarUrl}&amp;view_mode=weeklist&amp;todate={$navigation.focus_date}" class="{if $viewMode eq 'weeklist'}highlight{/if}">{biticon ipackage=calendar iname=weeklist iexplain=Weeklist}</a>
			<a href="{$baseCalendarUrl}&amp;view_mode=month&amp;todate={$navigation.focus_date}" class="{if $viewMode eq 'month'}highlight{/if}">{biticon ipackage=calendar iname=month iexplain=Month}</a>
		</td>
	</tr>
</table>
{/strip}
