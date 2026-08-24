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
		<li class="calnav-picker">
			{* Plain form as the li's direct child, no <span> wrapper - a <span>
			   here picks up Bootstrap's .pagination>li>span rule, which sets
			   position:relative alongside float:left, and that position:relative
			   ancestor is what was pushing the native date-picker's popup out of
			   place (rendering over the bar and the grid's day-header row below
			   instead of anchored under the input). .calnav-picker below gives
			   this li its own float:left (needed to keep it inline with its
			   siblings - without any float here it drops to the end of the
			   set) without the position:relative side effect. *}
			<form method="get" action="{$baseCalendarUrl|regex_replace:'/\?.*/':''}">
				<input type="hidden" name="pkg" value="{$smarty.request.pkg|escape}" />
				<input type="date" name="todate" value="{$navigation.focus_date|date_format:'%Y-%m-%d'}" onchange="this.form.submit()" title="{tr}Jump to date{/tr}" />
			</form>
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
