<?php
/*
Plugin Name: Woocommerce Search Suggestions
Plugin URI: https://kneejerk.dev
Description: Add "did you mean" search suggestions to your woocommerce site when customers don't find anything!
Author: Ryan "Rohjay" Oeltjenbruns from Kneejerk Development
Author URI: https://rohjay.one
Version: 0.1
Requires at least: 6.0.0
Requires PHP: 8.0
*/

define( 'KJDWSS_VERSION', '0.1' );
define( 'KJDWSS_BASEDIR', __DIR__ . DIRECTORY_SEPARATOR );
    define( 'KJDWSS_SRCDIR', KJDWSS_BASEDIR . 'src' . DIRECTORY_SEPARATOR );
    define( 'KJDWSS_TEMPLATEDIR', KJDWSS_BASEDIR . 'templates' . DIRECTORY_SEPARATOR );

require_once KJDWSS_SRCDIR . 'dym.php';
require_once KJDWSS_SRCDIR . 'functions.php';

add_action( 'woocommerce_no_products_found', function() { // add the form into our theme's added hook
    if ( empty($_GET['s'] ?? []) ) { // this way if 's' isn't set, we'll move on.
        return;
    }

    $suggestion = apply_filters('kjdwss-get-suggestion', $_GET['s'] );
    if ( $suggestion == false ) {
        return;
    }

    $content = KJDWSS_grab(KJDWSS_TEMPLATEDIR . 'dym_suggestion.php', ['suggestion' => $suggestion]);

    echo apply_filters('kjdwss-output', $content);
}, 12 );

add_filter( 'kjdwss-get-suggestion', function( $suggestion ) {
    $dym = new KJDWSS_DYM();
    $suggestion = $dym->suggestions( $suggestion );
    return $suggestion;
}, 1, 1);
