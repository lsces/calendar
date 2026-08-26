<?php
/**
 * Direct, single-content-type calendar entry point for a package (health,
 * food) to link to - deliberately separate from index.php: no Calendar/
 * Display Options tab split (content type is fixed, nothing to choose), no
 * Show all/Only my items or sort_mode links, and a redesigned date-nav row
 * (package_nav_inc.tpl) instead of the stacked one calendar_nav_inc.tpl
 * uses. Built 2026-08-24 as new files throughout (package.tpl,
 * package_nav_inc.tpl, this page) rather than modifying calendar.tpl/
 * calendar_nav_inc.tpl/Calendar.php - deliberate, so index.php and the rest
 * of the existing calendar package are completely unaffected by anything
 * here, even if this page turns out to need further changes later.
 *
 * Each package gets its own $_SESSION slot (calendar_pkg_<pkg>), not the
 * $_SESSION['calendar'] index.php uses - switching between this page and
 * the normal calendar/index.php never cross-contaminates view_mode/
 * focus-date state between them.
 *
 * @package calendar
 */

namespace Bitweaver\Calendar;

use Bitweaver\KernelTools;
use Bitweaver\Food\FoodDay;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->isPackageActive( 'calendar', true );
$gBitSystem->verifyPermission( 'p_calendar_view' );

// Small fixed allowlist, not a generic "any content type" chooser - this
// page exists to give a specific package a direct, undecorated calendar
// entry point, not to become a second general-purpose calendar chooser.
// 'food' shows the FoodDay day-summary tile (one cell per day) by default -
// every meal separately was the original "too congested" month/week complaint.
// FoodDay isn't a real content type in the usual sense (see its own docblock -
// no liberty_content row for any day, computed fresh on every request) but its
// *type metadata* is registered the normal way so Calendar::getEvents() can
// discover it generically (its own method_exists('getContentList') check,
// zero food-specific code there). register() here is what seeds that the very
// first time - after that it persists in liberty_content_types like any other
// type and this call is just a cheap no-op. extra_guid/extra_label is an
// optional second type a page can let the user layer on top via a checkbox
// (see the show_extra handling below), not something every pkg needs - health
// has none configured.
FoodDay::register();
$pkgMap = [
	'health' => [ 'guid' => 'healthday', 'title' => KernelTools::tra( 'Health Calendar' ) ],
	'food'   => [ 'guid' => 'foodday',   'title' => KernelTools::tra( 'Food Calendar' ),
	              'extra_guid' => 'foodassembly', 'extra_label' => KernelTools::tra( 'Show individual meals' ) ],
];
$pkg = $_REQUEST['pkg'] ?? '';
if( !isset( $pkgMap[$pkg] ) ) {
	$gBitSystem->fatalError( KernelTools::tra( 'Unknown or missing pkg parameter.' ) );
}

$gCalendar = new Calendar();

$sessionKey = 'calendar_pkg_'.$pkg;
if( !isset( $_SESSION[$sessionKey] ) ) {
	$_SESSION[$sessionKey] = [];
}
$gCalendar->processRequestHash( $_REQUEST, $_SESSION[$sessionKey] );

$listHash = $_SESSION[$sessionKey];
$guids = [ $pkgMap[$pkg]['guid'] ];

// Persisted the same way view_mode is (in this page's own per-package session
// slot) - the checkbox's hidden-then-real input pair (see package_nav_inc.tpl)
// always sends show_extra on submit (0 or 1), so isset() alone reliably tells a
// real submission apart from a fresh/reloaded page with no submission at all.
$showExtra = false;
if( !empty( $pkgMap[$pkg]['extra_guid'] ) ) {
	if( isset( $_REQUEST['show_extra'] ) ) {
		$_SESSION[$sessionKey]['show_extra'] = !empty( $_REQUEST['show_extra'] );
	}
	$showExtra = !empty( $_SESSION[$sessionKey]['show_extra'] );
	if( $showExtra ) {
		$guids[] = $pkgMap[$pkg]['extra_guid'];
	}
}
$listHash['content_type_guid'] = $guids;

$gCalendar->buildCalendar( $listHash, $_SESSION[$sessionKey] );

// pShowContentOptions=false: no content-type checkboxes to set up, this
// page's type is fixed. Doesn't matter for package.tpl anyway (it never
// references $calContentTypes), just keeps setupCalendar()'s own work
// scoped to what's actually needed here.
$gCalendar->setupCalendar( false );

$gBitSmarty->assign( 'baseCalendarUrl', CALENDAR_PKG_URL.'package_page.php?pkg='.$pkg );
$gBitSmarty->assign( 'viewMode',        $_SESSION[$sessionKey]['view_mode'] ?? 'month' );
$gBitSmarty->assign( 'pkgTitle',        $pkgMap[$pkg]['title'] );
$gBitSmarty->assign( 'extraLabel',      $pkgMap[$pkg]['extra_label'] ?? null );
$gBitSmarty->assign( 'showExtra',       $showExtra );

$gBitSystem->display( 'bitpackage:calendar/package.tpl', $pkgMap[$pkg]['title'], [ 'display_mode' => 'display' ] );
