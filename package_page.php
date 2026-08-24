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

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->isPackageActive( 'calendar', true );
$gBitSystem->verifyPermission( 'p_calendar_view' );

// Small fixed allowlist, not a generic "any content type" chooser - this
// page exists to give a specific package a direct, undecorated calendar
// entry point, not to become a second general-purpose calendar chooser.
$pkgMap = [
	'health' => [ 'guid' => 'healthday',    'title' => KernelTools::tra( 'Health Calendar' ) ],
	'food'   => [ 'guid' => 'foodassembly', 'title' => KernelTools::tra( 'Food Calendar' ) ],
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
$listHash['content_type_guid'] = [ $pkgMap[$pkg]['guid'] ];

$gCalendar->buildCalendar( $listHash, $_SESSION[$sessionKey] );

// pShowContentOptions=false: no content-type checkboxes to set up, this
// page's type is fixed. Doesn't matter for package.tpl anyway (it never
// references $calContentTypes), just keeps setupCalendar()'s own work
// scoped to what's actually needed here.
$gCalendar->setupCalendar( false );

$gBitSmarty->assign( 'baseCalendarUrl', CALENDAR_PKG_URL.'package_page.php?pkg='.$pkg );
$gBitSmarty->assign( 'viewMode',        $_SESSION[$sessionKey]['view_mode'] ?? 'month' );
$gBitSmarty->assign( 'pkgTitle',        $pkgMap[$pkg]['title'] );

$gBitSystem->display( 'bitpackage:calendar/package.tpl', $pkgMap[$pkg]['title'], [ 'display_mode' => 'display' ] );
