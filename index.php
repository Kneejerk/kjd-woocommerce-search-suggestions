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

add_action( 'woocommerce_no_products_found', function() {
    // nothing to do if 's' (search) isn't set...
    if ( empty($_GET['s'] ?? []) ) {
        return;
    }

    $time = microtime(true);
    $search = $_GET['s'];
    $suggestion = apply_filters( 'kjdwss-get-suggestion', $search );
    $time = round(microtime(true) - $time, 5);
    if ( $suggestion == false ) {
        return;
    }

    $normalized_search = KJDWSS_normalize_string( $search );

    // If we haven't actually suggested any changes, let's not suggest anything.
    if ( $suggestion == $normalized_search ) {
        return;
    }

    $url = KJDWSS_linkify_suggestion( $suggestion );
    $content = KJDWSS_grab(KJDWSS_TEMPLATEDIR . 'dym_suggestion.php', ['suggestion' => $suggestion, 'url' => $url]);
    echo apply_filters('kjdwss-output', $content);
    echo "\n<!-- KJDWSS: Generated suggestion in {$time} seconds -->\n";
}, 12 );

add_filter( 'kjdwss-get-suggestion', function( $search ) {
    $dym = new KJDWSS_DYM();
    return $dym->suggestions( $search, apply_filters('kjdwss-acceptance-threshold', 75 ) );
}, 10, 1);
