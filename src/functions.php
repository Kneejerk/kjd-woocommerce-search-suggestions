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
