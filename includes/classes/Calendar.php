<?php
/**
 * @version $Header$
 * @package calendar
 *
 * @copyright Copyright (c) 2004-2006, bitweaver.org
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.
 */

/**
 * Required setup
 */
namespace Bitweaver\Calendar;

use Bitweaver\BitDate;
use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyContent;

global $gBitSystem, $gBitUser;

// set week offset - start with a day other than monday
define( 'WEEK_OFFSET', !empty( $gBitUser->mPrefs['calendar_week_offset'] ) ? $gBitUser->mPrefs['calendar_week_offset'] : $gBitSystem->getConfig( 'calendar_week_offset', 0 ) );

/**
 * @package calendar
 */
class Calendar extends LibertyContent {

	public $mDate;

	public function __construct() {
		parent::__construct();
		$this->mDate = new BitDate(0);
	}

	/**
	* get a full list of content for a given time period
	* return array of items
	*
	* The output array will be a set of UTC tagged pages covering the period
	* defined in the $pListHash. Items identified from the list will be tagged to
	* the day identified in the selected timestamp from which the list was built.
	* If the user has selected a local time display, then the day will be the actual
	* UTC day that the item is in, rather than the UTC day of the item. In this way
	* the display view provides a list of locally correct entries for each day.
	**/
	public function getList( $pListHash ) {
		$ret = [];

		$pListHash['include_data'] = TRUE;
		if( !empty( $pListHash['focus_date'] ) ) {
			$calDates = $this->doRangeCalculations( $pListHash );
			// Resolved for focus_date itself, not "now" - a DST-observing Fixed-mode zone's
			// offset genuinely differs by season (found 2026-08-31, see kernel/DATETIME.md).
			$rangeOffset = $this->mDate->get_display_offset( $pListHash['focus_date'] );
			$pListHash['time_limit_start'] = $calDates['view_start'] - $rangeOffset;
			$pListHash['time_limit_stop'] = $calDates['view_end'] - $rangeOffset;
		}
//		if (  empty( $pListHash['sort_mode'] ) ) {
//			$pListHash['sort_mode'] = !empty( $_REQUEST['sort_mode'] ) ? $_REQUEST['sort_mode'] : 'event_time_asc';
//		}
		$pListHash['sort_mode'] = 'event_time_asc';
		$pListHash['time_limit_column'] = preg_replace( "/(_asc$|_desc$)/i", "", $pListHash['sort_mode'] );

		if ( empty( $pListHash['user_id'] ) ) {
			$pListHash['user_id'] = !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : NULL;
		}
		if ( !empty( $_REQUEST['order_table'] ) ) {
			$pListHash['order_table'] = $_REQUEST['order_table'];
		}

		// Don't think this is required.
		$pListHash['offset'] = 0;
		// There should at least be a preference for this.
		$pListHash['max_records'] = 500;

		$res = $this->getContentList( $pListHash );

		foreach( $res as $item ) {
			// shift all time data by user timezone offset, resolved for this item's OWN
			// date rather than a single request-wide value - a month/week view can span a
			// DST transition, and a DST-observing Fixed-mode zone's offset genuinely differs
			// either side of one (found 2026-08-31, see kernel/DATETIME.md). Using the item's
			// own primary timestamp for created/last_modified/event_time too rather than
			// resolving each separately - a pragmatic "close enough" call, not per-field
			// precision, since all four belong to the same content item.
			// and then display as a simple UTC time
			$itemOffset = $this->mDate->get_display_offset( $item[$pListHash['time_limit_column']] );
			$item['timestamp']     = $item[$pListHash['time_limit_column']] + $itemOffset;
			$item['created'] += $itemOffset;
			$item['last_modified'] += $itemOffset;
			$item['event_time'] += $itemOffset;
			$item['parsed'] = self::parseDataHash( $item );
			$dstart = $this->mDate->gmmktime( 0, 0, 0, (int)gmdate( 'n', $item['timestamp'] ), (int)gmdate( 'j', $item['timestamp'] ), (int)gmdate( 'Y', $item['timestamp'] ) );
			$ret[$dstart][] = $item;
		}
	return $ret;
	}

	/**
	* calculate the start and stop time for the current display page
	**/
	public function doRangeCalculations( $pDateHash ) {
		// GMT-based decomposition, matching focus_date's own gmmktime() convention - native
		// gmdate() calls replace BitDate::_getDate(), which only ever existed to cover a
		// historical/pre-1970 date range this package never needs (see kernel/DATETIME.md).
		$focus = [
			'mon'  => (int)gmdate( 'n', $pDateHash['focus_date'] ),
			'mday' => (int)gmdate( 'j', $pDateHash['focus_date'] ),
			'year' => (int)gmdate( 'Y', $pDateHash['focus_date'] ),
			'wday' => (int)gmdate( 'w', $pDateHash['focus_date'] ),
		];

		if( $pDateHash['view_mode'] == 'month' ) {
			$view_start = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'],     1, $focus['year'] );
			$view_end   = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'] + 1, 1, $focus['year'] ) - 1;
		} elseif( $pDateHash['view_mode'] == 'week' or $pDateHash['view_mode'] == 'weeklist') {
			// Days back from focus_date to this week's start, in the SAME WEEK_OFFSET
			// convention buildMonth()/setupDayNames() use (0 = Monday-start, matching
			// their base Monday-first day-name array before any rotation - not the
			// "Sunday=0" assumption the old formula made, which silently fetched a
			// data window one full day earlier than the displayed grid and always
			// dropped the week's actual last day. $focus['wday'] is native PHP wday
			// (0=Sunday..6=Saturday); (wday+6)%7 converts that to a Monday-first
			// index (0=Monday..6=Sunday), then +WEEK_OFFSET shifts to match whichever
			// day the grid actually starts on. Found + fixed 2026-08-26 - see
			// project_calendar_week_off_by_one memory for the empirical trace
			// (Sunday's data silently missing from every populated week tested).
			$wd = ( $focus['wday'] + 6 + WEEK_OFFSET ) % 7;

			$view_start = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] - $wd, $focus['year'] );
			$view_end   = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] - $wd + 7, $focus['year'] ) - 1;
		} else {
			$view_start = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday']    , $focus['year'] );
			$view_end   = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] + 1, $focus['year'] ) - 1;
		}

		$start_year  = (int)gmdate( 'Y', $view_start );
		if ( $start_year < 1902 ) {
			$view_start_iso = $view_start  = gmdate( 'Y-m-d', $view_start );
			$view_end_iso = $view_end  = gmdate( 'Y-m-d', $view_start );
			$view_start = 0;
			$view_end = 0;
		}

		$ret = [
			'view_start' => $view_start,
			'view_end'   => $view_end,
		];
		// Insert ISO dates if they are set - Used for dates pre 1902
		if ( !empty($view_start_iso) ) {
			$ret['view_start_iso'] = $view_start_iso;
			$ret['view_end_iso'] = $view_end_iso;
		}
		return $ret;
	}

	/**
	 * prepare ListHash to ensure errorfree usage
	 * @param array pListHash hash of parameters for any getList() function
	 * @return void
	**/
	public static function prepGetList( &$pListHash ): void {
		$pListHash['include_data'] = TRUE;
		if( !empty( $pListHash['focus_date'] ) ) {
			$calDates = $this->doRangeCalculations( $pListHash );
			// Resolved for focus_date itself, not "now" - a DST-observing Fixed-mode zone's
			// offset genuinely differs by season (found 2026-08-31, see kernel/DATETIME.md).
			$rangeOffset = $this->mDate->get_display_offset( $pListHash['focus_date'] );
			$pListHash['time_limit_start'] = $calDates['view_start'] - $rangeOffset;
			$pListHash['time_limit_stop'] = $calDates['view_end'] - $rangeOffset;
		}
		if (  empty( $pListHash['sort_mode'] ) ) {
			$pListHash['sort_mode'] = !empty( $_REQUEST['sort_mode'] ) ? $_REQUEST['sort_mode'] : 'event_time_asc';
		}
		$pListHash['time_limit_column'] = preg_replace( "/(_asc$|_desc$)/i", "", $pListHash['sort_mode'] );

		if ( empty( $pListHash['user_id'] ) ) {
			$pListHash['user_id'] = !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : NULL;
		}
		if ( !empty( $_REQUEST['order_table'] ) ) {
			$pListHash['order_table'] = $_REQUEST['order_table'];
		}

		// Don't think this is required.
		$pListHash['offset'] = 0;
		// There should at least be a preference for this.
		$pListHash['max_records'] = 500;

		LibertyContent::prepGetList( $pListHash );
	}

	public function buildDay( $pDateHash ) {
		global $gBitSystem, $gBitUser;
		// getdate() (no such BitDate method - only _getDate() exists) was an undefined-method
		// fatal waiting to happen; not currently reachable from anywhere (nothing calls
		// buildDay()), found auditing BitDate's adodb dependency. Native equivalent, matching
		// the GMT convention doRangeCalculations() above already uses for the same shape of
		// value.
		$focus = [
			'mon'  => (int)gmdate( 'n', $pDateHash['focus_date'] ),
			'mday' => (int)gmdate( 'j', $pDateHash['focus_date'] ),
			'year' => (int)gmdate( 'Y', $pDateHash['focus_date'] ),
		];

		$ret = [];
		if( $pDateHash['view_mode'] == 'day' ) {
			// calculare what the visible day view range is
			$day_start   = $gBitUser->mPrefs['calendar_day_start'] ?? $gBitSystem->getConfig( 'calendar_day_start', 0 );
			$day_end     = $gBitUser->mPrefs['calendar_day_end'] ?? $gBitSystem->getConfig( 'calendar_day_end', 24 );
			$start_time  = $this->mDate->mktime( 0, 0, 0, $focus['mon'], $focus['mday'], $focus['year'] ) + ( 60 * 60 * $day_start );
			$stop_time   = $this->mDate->mktime( 0, 0, 0, $focus['mon'], $focus['mday'] + 1, $focus['year'] ) - ( 60 * 60 * ( 24 - $day_end ) );
			$hours_count = ( $stop_time - $start_time ) / ( 60 * 60 );

			// allow for custom time intervals
			$hour_fraction = !empty( $gBitUser->mPrefs['calendar_hour_fraction'] ) ? $gBitUser->mPrefs['calendar_hour_fraction'] : $gBitSystem->getConfig( 'calendar_hour_fraction', 1 );
			$row_count = $hours_count * $hour_fraction;
			// local (not GMT) - matches the mktime() (not gmmktime()) call above building $start_time
			$hour = (int)date( 'G', $start_time ) - 1;
			$mins = 0;
			for( $i = 0; $i < $row_count; $i++ ) {
				if( !( $i % $hour_fraction ) ) {
					// set vars
					$hour++;
					$mins = 0;
				}
				$ret[$i]['time'] = $this->mDate->gmmktime( $hour, $mins, 0, $focus['mon'], $focus['mday'], $focus['year'] );
				$mins += 60 / $hour_fraction;
			}
			// calendar data is added below
		}
		return $ret;
	}

	/**
	 * build an array of unix UTC timestamps relating to the current
	 * calendar focus. This provides a fixed basis from which to apply local
	 * offsets provided by tz_offset information. Daylight saving information
	 * is not available via this route!
	 **/
	public function buildCalendarNavigation( $pDateHash ) {
		global $gBitUser, $gBitSystem;
		if ( empty( $this->mDate ) ) return [];
		// getdate() (no such BitDate method) fixed to a native GMT-based equivalent, matching
		// the gmmktime() reconstruction below and this method's own "no DST info available"
		// docblock warning - a non-GMT decomposition would have been wrong regardless of the
		// undefined-method bug. See doRangeCalculations()'s matching comment.
		$now = time();
		$today = [
			'mon'  => (int)gmdate( 'n', $now ),
			'mday' => (int)gmdate( 'j', $now ),
			'year' => (int)gmdate( 'Y', $now ),
		];
		$focus = [
			'mon'  => (int)gmdate( 'n', $pDateHash['focus_date'] ),
			'mday' => (int)gmdate( 'j', $pDateHash['focus_date'] ),
			'year' => (int)gmdate( 'Y', $pDateHash['focus_date'] ),
			0      => $pDateHash['focus_date'],
		];

		$ret = [
			'before'             => [
				'day'   => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] - 1, $focus['year'] ),
				'week'  => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] - 7, $focus['year'] ),
				'month' => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'] - 1, $focus['mday'], $focus['year'] ),
				'year'  => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'], $focus['year'] - 1 ),
			],
			'after'              => [
				'day'   => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] + 1, $focus['year'] ),
				'week'  => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'] + 7, $focus['year'] ),
				'month' => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'] + 1, $focus['mday'], $focus['year'] ),
				'year'  => $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $focus['mday'], $focus['year'] + 1 ),
			],
			'focus_month'        => $focus['mon'],
			'focus_year'         => $focus['year'],
			'focus_date'         => $focus[0],
			'today'              => $this->mDate->gmmktime( 0, 0, 0, $today['mon'], $today['mday'], $today['year'] ),
			'tz_flag'            => $gBitUser->getPreference( 'site_display_utc', "Local" ),
			'display_focus_date' => $focus[0],
		];

		return $ret;
	}

	/**
	 * build a two dimensional array of unix timestamps
	 * The timestamps are either UTC or display local time depending on the
	 * setting of the current users display time offset
	 * mktime SHOULD NOT BE USED since it offsets the times based on server
	 * timezone and daylight saving, but the USERS daylight saving information
	 * is not available, which will cause some problems!
	 **/
	public function buildMonth( $pDateHash ) {
		global $gBitSmarty;
		if ( empty( $this->mDate ) ) return [];

		// getdate() (no such BitDate method) fixed to native GMT-based equivalents - see
		// doRangeCalculations()'s matching comment.
		$focus = [
			'mon'  => (int)gmdate( 'n', $pDateHash['focus_date'] ),
			'mday' => (int)gmdate( 'j', $pDateHash['focus_date'] ),
			'year' => (int)gmdate( 'Y', $pDateHash['focus_date'] ),
			'wday' => (int)gmdate( 'w', $pDateHash['focus_date'] ),
		];

		$prev_month_end	  = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'],     0, $focus['year'] );
		$next_month_begin = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'] + 1, 1, $focus['year'] );

		$prev_month_end_info = [
			'mon'  => (int)gmdate( 'n', $prev_month_end ),
			'year' => (int)gmdate( 'Y', $prev_month_end ),
		];
		$prev_month = $prev_month_end_info['mon'];
		$prev_month_year = $prev_month_end_info['year'];

		// Build a two-dimensional array of UNIX timestamps.
		$cal = [];

		// Start the first row with the final day( s ) of the previous month.
		$week = [];
		$month_begin = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], WEEK_OFFSET, $focus['year'] );
		$month_begin_day_of_week = $this->mDate->dayOfWeek( $focus['year'], $focus['mon'], WEEK_OFFSET );
		$days_in_prev_month = $this->mDate->daysInMonth( $prev_month, $prev_month_year );

		// Fill out the first row with the last day( s ) of the previous month.
		for( $day_of_week = 0; $day_of_week < $month_begin_day_of_week; $day_of_week++ ) {
			$_day = $days_in_prev_month - $month_begin_day_of_week + $day_of_week + 1;
			$week[]['day'] = $this->mDate->mktime( 0, 0, 0, $prev_month, $_day, $prev_month_year );
		}

		// Fill in the days of the selected month.
		$days_in_month = $this->mDate->daysInMonth( $focus['mon'], $focus['year'] );
		for( $i = 1; $i <= $days_in_month; $i++ ) {
			if( $day_of_week == 7 ) {
				$calendar[$this->mDate->weekOfYear( $focus['year'], $focus['mon'], $i )] = $week;

				// re-initialize $day_of_week and $week
				$day_of_week = 0;
				$week = [];
			}
			$week[]['day'] = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'], $i, $focus['year'] );
			$day_of_week++;
		}

		// Fill out the last row with the first day( s ) of the next month.
		for( $j = 1; $day_of_week < 7; $j++, $day_of_week++ ) {
			$week[]['day'] = $this->mDate->gmmktime( 0, 0, 0, $focus['mon'] + 1, $j, $focus['year'] );
		}
		$week_num = $this->mDate->weekOfYear( $focus['year'], $focus['mon'], $days_in_month + $j );
		$calendar[$week_num] = $week;

		// Modify offset to fix roll over on week numbers
		// This is required because the week numbers are calculated for Sunday
		// Offseting the result in BitDate is the real solution
		$offset = WEEK_OFFSET == 7 ? $focus['mday'] + 1 : $focus['mday'] + 1 + WEEK_OFFSET;
		// this week number has to be calculated, since the cal start can be configured
		$week_num = $this->mDate->weekOfYear( $focus['year'], $focus['mon'], $offset );

		// if we only want to see a weeks / days worth of data, nuke all xs data
		if( $pDateHash['view_mode'] == 'week' or $pDateHash['view_mode'] == 'weeklist' ) {
			$cal = $calendar[$week_num];
			$calendar = [];
			$calendar[$week_num] = $cal;
		} elseif( $pDateHash['view_mode'] == 'day' ) {
			$calendar = [];
			$calendar[$week_num][]['day'] = $pDateHash['focus_date'];
		}

		return $calendar;
	}

	// Setup the content types for use in the calendar.
	public function setupContentTypes() {
		global $gLibertySystem, $gBitSmarty, $gBitSystem;
		foreach( $gLibertySystem->mContentTypes as $cName => $cType ) {
			if ( $gBitSystem->isPackageActive( $cType['handler_package'] ) ) {
				$contentTypes[$cType['content_type_guid']] = $gLibertySystem->getContentTypeName( $cType['content_type_guid'] );
			}
		}
		asort($contentTypes);
		$gBitSmarty->assign( 'calContentTypes', $contentTypes );
	}

	// Setup the day names for use in the calendar
	public function setupDayNames() {
		global $gBitSmarty;

		// set up daynames for the calendar
		$dayNames = [
			KernelTools::tra( "Monday" ),
			KernelTools::tra( "Tuesday" ),
			KernelTools::tra( "Wednesday" ),
			KernelTools::tra( "Thursday" ),
			KernelTools::tra( "Friday" ),
			KernelTools::tra( "Saturday" ),
			KernelTools::tra( "Sunday" ),
		];

		// depending on what day we want to view first, we need to adjust the dayNames array
		for( $i = 0; $i < WEEK_OFFSET; $i++ ) {
			$pop = array_pop( $dayNames );
			array_unshift( $dayNames, $pop );
		}
		$gBitSmarty->assign( 'dayNames', $dayNames );
	}

	function processRequestHash(&$pRequest, &$pStore) {
		global $gBitUser;
		if( !empty( $pRequest["content_type_guid"] ) ) {
			if( $gBitUser->isRegistered() ) {
				$gBitUser->storePreference( 'calendar_default_guids', serialize( $pRequest['content_type_guid'] ) );
			}
			$pStore['content_type_guid'] = $pRequest["content_type_guid"];
		} elseif( !isset( $pStore['content_type_guid'] ) && $gBitUser->getPreference( 'calendar_default_guids' ) && $gBitUser->isRegistered() ) {
			$pStore['content_type_guid'] = unserialize( $gBitUser->getPreference( 'calendar_default_guids' ) );
		} elseif( !isset( $pStore['content_type_guid'] ) ) {
			$pStore['content_type_guid'] = [];
		} elseif( isset( $pRequest["refresh"] ) && !isset( $pRequest["content_type_guid"] ) ) {
			$pStore['content_type_guid'] = [];
		}

		// set up the todate
		if( !empty( $pRequest["todate"] ) ) {
			// clean up todate. who knows where this has come from
			// native gmdate()/strtotime() replace BitDate::date()/date2() - both only ever
			// existed to cover a historical/pre-1970 date range this package never needs (see
			// kernel/DATETIME.md). A non-numeric todate is treated as a UTC-ish date string
			// (matching this file's own GMT-first convention elsewhere), falling back to now()
			// if genuinely unparseable rather than fataling.
			$todateEpoch = is_numeric( $pRequest['todate'] )
				? (int)$pRequest['todate']
				: ( strtotime( $pRequest['todate'].' UTC' ) ?: time() );
			$pStore['focus_date'] = $pRequest['todate'] = $this->mDate->gmmktime(
				0, 0, 0,
				(int)gmdate( 'n', $todateEpoch ), (int)gmdate( 'j', $todateEpoch ), (int)gmdate( 'Y', $todateEpoch )
			);
		} elseif( !empty( $pStore['focus_date'] ) ) {
			$pRequest["todate"] = $pStore['focus_date'];
		} else {
			$pStore['focus_date'] = $this->mDate->gmmktime( 0, 0, 0, (int)gmdate( 'n' ), (int)gmdate( 'j' ), (int)gmdate( 'Y' ) );
			$pRequest["todate"] = $pStore['focus_date'];
		}

		$focus = $pRequest['todate'];
		if( !empty( $pRequest["view_mode"] ) ) {
			$pStore['view_mode'] = $pRequest["view_mode"];
		} elseif( empty( $pStore['view_mode'] ) ) {
			$pStore['view_mode'] = 'month';
		}
	}

	public function getEvents(&$pListHash) {
		global $gBitSystem, $gLibertySystem;

		$bitEvents = [];
		if ( !empty( $pListHash['content_type_guid'] ) ) {
			// A registered type whose handler class implements its own
			// getContentList() (opt-in, same additive pattern the existing
			// getDayCellHtml() hook uses) supplies its own records directly
			// instead of going through the generic SQL-against-liberty_content
			// path below - e.g. FoodDay, which has no liberty_content rows at
			// all. Nothing here needs to know FoodDay exists specifically; any
			// future type can opt in the same way just by implementing the
			// method and being registered (the type must already be registered
			// for getContentClassName() to resolve it at all - see FoodDay's own
			// register()/docblock for how it gets there).
			$virtualClasses = [];
			foreach ( $pListHash['content_type_guid'] as $type ) {
				$class = $gLibertySystem->getContentClassName( $type );
				// method_exists() alone isn't enough - every real LibertyContent
				// subclass (FoodAssembly, HealthDay, ...) already inherits a
				// getContentList(), just as a non-static instance method. Only a
				// class that declares its own *static* override (FoodDay) opts into
				// this path - reflection is the only reliable way to tell the two
				// apart.
				if ( $class && method_exists( $class, 'getContentList' )
					&& (new \ReflectionMethod( $class, 'getContentList' ))->isStatic() ) {
					$virtualClasses[$type] = $class;
				}
			}

			// Virtual types never go through prepGetList()'s own time_limit_start/
			// stop setup (that only happens inside getList(), which they bypass
			// entirely) - compute it here instead, once, whenever at least one is
			// present.
			if ( $virtualClasses && empty( $pListHash['time_limit_start'] ) && !empty( $pListHash['focus_date'] ) ) {
				$calDates = $this->doRangeCalculations( $pListHash );
				// Resolved for focus_date itself, not "now" - see getList()'s matching comment.
				$rangeOffset = $this->mDate->get_display_offset( $pListHash['focus_date'] );
				$pListHash['time_limit_start'] = $calDates['view_start'] - $rangeOffset;
				$pListHash['time_limit_stop']  = $calDates['view_end'] - $rangeOffset;
			}

			// Verify that the type is still active - also drops a guid that isn't
			// registered at all (e.g. a stored calendar_default_guids preference
			// pointing at a content type since removed), which otherwise passed
			// null straight into isPackageActive()/strtoupper() further down.
			foreach ( $pListHash['content_type_guid'] as $index => $type ) {
				if ( !empty( $virtualClasses[$type] ) ) {
					// Merged in (not just assigned) since a real type selected
					// alongside it may already have entries on the same day.
					foreach ( $virtualClasses[$type]::getContentList( $pListHash ) as $dstart => $items ) {
						$bitEvents[$dstart] = array_merge( $bitEvents[$dstart] ?? [], $items );
					}
					unset( $pListHash['content_type_guid'][$index] );
					continue;
				}
				if ( empty( $gLibertySystem->mContentTypes[$type]['handler_package'] )
					|| !$gBitSystem->isPackageActive( $gLibertySystem->mContentTypes[$type]['handler_package'] ) ) {
					unset( $pListHash['content_type_guid'][$index] );
				}
			}
			if ( !empty( $pListHash['content_type_guid'] ) ) {
				foreach ( $this->getList( $pListHash ) as $dstart => $items ) {
					$bitEvents[$dstart] = array_merge( $bitEvents[$dstart] ?? [], $items );
				}
			}
		}

		return $bitEvents;
	}

	public function buildCalendar(&$pListHash, &$pDateHash) {
		global $gBitSmarty, $gBitSystem;

		if( isset($pDateHash['focus_date']) && !isset($pListHash['focus_date']) ) {
			$pListHash['focus_date'] = $pDateHash['focus_date'];
		}
		if( isset($pDateHash['view_mode']) && !isset($pListHash['view_mode']) ) {
			$pListHash['view_mode'] = $pDateHash['view_mode'];
		}
		$bitEvents = $this->getEvents($pListHash);

		$gBitSmarty->assign( 'navigation', $this->buildCalendarNavigation( $pDateHash ) );
		$calMonth = $this->buildMonth( $pDateHash );
		$calDay = $this->buildDay( $pDateHash );

		foreach( $calMonth as $w => $week ) {
			foreach( $week as $d => $day ) {
				$dayEvents = [];
				if( !empty( $bitEvents[$day['day']] ) ) {
					$i = 0;
					foreach( $bitEvents[$day['day']] as $bitEvent ) {
						$bitEvent['parsed_data'] = self::parseDataHash($bitEvent);
						$dayEvents[$i] = $bitEvent;
						if (!$gBitSystem->isFeatureActive('calendar_ajax_popups')) {
							$gBitSmarty->assign( 'cellHash', $bitEvent );
							$dayEvents[$i]["over"] = $gBitSmarty->fetch( "bitpackage:calendar/calendar_box.tpl" );
						}

						// populate $calDay array with events
						if( !empty ( $bitEvent ) && $pDateHash['view_mode'] == 'day' ) {
							foreach( $calDay as $key => $t ) {
								// special case - last item entry in array - check this first

								if( $bitEvent['timestamp'] >= $calDay[$key]['time']  && empty( $calDay[$key + 1]['time'] ) ) {
									$calDay[$key]['items'][] = $dayEvents[$i];
								} elseif( $bitEvent['timestamp'] >= $calDay[$key]['time'] && $bitEvent['timestamp'] < $calDay[$key + 1]['time'] ) {
									$calDay[$key]['items'][] = $dayEvents[$i];
								}
							}
						}

						$i++;
					}
				}
				if( !empty( $dayEvents ) ) {
					$calMonth[$w][$d]['items'] = array_values( $dayEvents );
				}
			}
		}
		$gBitSmarty->assign( 'calDay', $calDay );
		$gBitSmarty->assign( 'calMonth', $calMonth );
	}

	function setupCalendar($pShowContentOptions = TRUE) {
		global $gBitThemes, $gBitSmarty, $gBitSystem;
		if ( $pShowContentOptions ) {
			$this->setupContentTypes();
		}
		$this->setupDayNames();

		if ($gBitSystem->isFeatureActive('calendar_ajax_popups')) {
		}

		// TODO: make this a pref
		$gBitSmarty->assign( 'trunc', $gBitSystem->getConfig( 'title_truncate', 32 ) );
	}

	// Display the actual calendar doing any other work required for the template
	function display($pTitle, $pShowContentOptions = TRUE, $pBaseUrl=NULL) {
		global $gBitSystem, $gBitSmarty;

		$this->setupCalendar($pShowContentOptions);

		// A default base for the calendar
		if( empty($pBaseUrl) ){
			$pBaseUrl = CALENDAR_PKG_URL.'index.php';
		}
		// Asssign it so templates see it.
		$gBitSmarty->assign('baseCalendarUrl', $pBaseUrl);

		$gBitSystem->display( 'bitpackage:calendar/calendar.tpl', $pTitle , [ 'display_mode' => 'display' ]);

	}
}
