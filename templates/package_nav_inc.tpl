{strip}
{* New, package_page.php-only nav - deliberately a separate file from
   calendar_nav_inc.tpl (which index.php still uses unchanged) so nothing
   here can ever affect the legacy calendar pages. No "Show all/Only my
   items" or sort_mode links (single fixed content type, nothing to filter
   by user/sort here). Styled as Bootstrap pagination-bar buttons (same
   .pagination classes kernel/templates/pagination.tpl already uses
   sitewide) for visual consistency with every other button bar on the
   site, rather than plain stacked text links - Lester's own call,
   2026-08-24, after an earlier single-letter-abbreviation pass turned out
   to be a mistake (full words needed instead). Today's date/selection isn't
   spelled out separately - the "Today" button and the date-picker input
   (native browser calendar widget) already convey it between them.

   Layout/height tidying (themes/css/config.css's .calnav rule, added same
   session) mirrors .paginator's own flex-row + zero-Bootstrap's-pagination-
   margin treatment rather than the pull-left/pull-right float approach a
   first pass used - .calnav's flex + justify-content:space-between spaces
   the three groups below directly, no float/clear classes needed. *}
<div class="calnav">
	<ul class="pagination">
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.before.year}"  title="{$navigation.before.year|bit_long_date}">&laquo; {tr}Year{/tr}</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.before.month}" title="{$navigation.before.month|bit_long_date}">&laquo; {tr}Month{/tr}</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.before.week}"  title="{$navigation.before.week|bit_long_date}">&laquo; {tr}Week{/tr}</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.before.day}"   title="{$navigation.before.day|bit_long_date}">&laquo; {tr}Day{/tr}</a></li>
	</ul>

	<ul class="pagination">
		<li><a href="{$baseCalendarUrl}&amp;todate={$smarty.now}" title="{$navigation.today|bit_long_date} {$navigation.tz_flag}">{tr}Today{/tr}</a></li>
		<li>
			{* <span>, not <a> - a form control can't nest inside an anchor, and
			   Bootstrap's pagination CSS already styles li>span the same as
			   li>a for exactly this reason (see kernel/templates/pagination.tpl's
			   own current-page <span> for the same convention). *}
			<span>
				<form method="get" action="{$baseCalendarUrl|regex_replace:'/\?.*/':''}" style="display:inline">
					<input type="hidden" name="pkg" value="{$smarty.request.pkg|escape}" />
					<input type="date" name="todate" value="{$navigation.focus_date|date_format:'%Y-%m-%d'}" onchange="this.form.submit()" title="{tr}Jump to date{/tr}" />
				</form>
			</span>
		</li>
		<li class="{if $viewMode eq 'day'}active{/if}"><a href="{$baseCalendarUrl}&amp;view_mode=day&amp;todate={$navigation.focus_date}">{biticon ipackage=calendar iname=day iexplain=Day}</a></li>
		<li class="{if $viewMode eq 'week'}active{/if}"><a href="{$baseCalendarUrl}&amp;view_mode=week&amp;todate={$navigation.focus_date}">{biticon ipackage=calendar iname=week iexplain=Week}</a></li>
		<li class="{if $viewMode eq 'weeklist'}active{/if}"><a href="{$baseCalendarUrl}&amp;view_mode=weeklist&amp;todate={$navigation.focus_date}">{biticon ipackage=calendar iname=weeklist iexplain=Weeklist}</a></li>
		<li class="{if $viewMode eq 'month'}active{/if}"><a href="{$baseCalendarUrl}&amp;view_mode=month&amp;todate={$navigation.focus_date}">{biticon ipackage=calendar iname=month iexplain=Month}</a></li>
	</ul>

	<ul class="pagination">
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.after.day}"   title="{$navigation.after.day|bit_long_date}">{tr}Day{/tr} &raquo;</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.after.week}"  title="{$navigation.after.week|bit_long_date}">{tr}Week{/tr} &raquo;</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.after.month}" title="{$navigation.after.month|bit_long_date}">{tr}Month{/tr} &raquo;</a></li>
		<li><a href="{$baseCalendarUrl}&amp;todate={$navigation.after.year}"  title="{$navigation.after.year|bit_long_date}">{tr}Year{/tr} &raquo;</a></li>
	</ul>
</div>
{/strip}
