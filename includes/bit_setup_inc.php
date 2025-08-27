<?php
global $gBitSystem, $gBitSmarty, $gBitUser;

$pRegisterHash = [
	'package_name' => 'calendar',
	'package_path' => dirname( dirname( __FILE__ ) ) . '/',
	'homeable'     => true,
];

// fix to quieten down VS Code which can't see the dynamic creation of these ...
define( 'CALENDAR_PKG_NAME', $pRegisterHash['package_name'] );
define( 'CALENDAR_PKG_URL', BIT_ROOT_URL . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'CALENDAR_PKG_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'CALENDAR_PKG_INCLUDE_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/'); 
define( 'CALENDAR_PKG_CLASS_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/classes/');
define( 'CALENDAR_PKG_ADMIN_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/admin/'); 
$gBitSystem->registerPackage( $pRegisterHash );

if( $gBitSystem->isPackageActive( 'calendar' ) && $gBitUser->hasPermission( 'p_calendar_view' )) {

		$menuHash = [
		'package_name'  => CALENDAR_PKG_NAME,
		'index_url'     => CALENDAR_PKG_URL . 'index.php',
		'menu_template' => 'bitpackage:calendar/menu_calendar.tpl',
	];
		$gBitSystem->registerAppMenu( $menuHash );
	}

