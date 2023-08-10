<?php

// Quick view function to encapsulate the template include...
function KJDWSS_view( $template_path, $data = [] ) {
    $real_path = realpath( $template_path );
    if ( !file_exists($real_path) ) {
        throw new Exception('Template not found: ' . $real_path);
    }

    // Nope!
    if ( isset($data['real_path']) ) unset($data['real_path']);
    extract($data);

    include $real_path;
}

// Return the view instead of rendering it outright.
function KJDWSS_grab( $template, $data = [] ) {
    ob_start();
    KJDWSS_view( $template, $data );
    return ob_get_clean();
}

// Trims a string to help with search suggestions.
function KJDWSS_super_trim($word) {
    return preg_replace('/\W/', '', $word);
}

function KJDWSS_normalize_string($string) {
    $parts = explode( ' ', strtolower($string) );
    $normalized_string = [];
    foreach ( $parts as $part ) {
        $normalized_string[] = KJDWSS_super_trim( $part );
    }
    return join( ' ', $normalized_string );
}

function KJDWSS_linkify_suggestion( $suggestion ) {
    $current_url = KJDWSS_unpaginate_url(KJDWSS_get_current_url());
    return add_query_arg(array_merge($_GET, ['s'=>urlencode($suggestion)]), $current_url);
}

function KJDWSS_get_current_url() {
    global $wp;
    $current_url = home_url( $wp->request );
    return rtrim($current_url, '/') . '/';
}

function KJDWSS_unpaginate_url($url) {
    // get the position where '/page/2' text start.
    $pos = strpos($url, '/page/');
    // if it's there, remove string from the specific postion
    return $pos !== false ? substr($url, 0, $pos) : $url;
}
