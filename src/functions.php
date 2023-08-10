<?php

// Quick view function to encapsulate the template include...
function KJDWSS_view( $template, $data = [] ) {
    $template = KJDWSS_TEMPLATEDIR . $template . '.php';
    if ( !file_exists($template) ) {
        throw new Exception('Template not found: ' . $template);
    }
    extract($data);
    include $template;
}

// Return the view instead of rendering it outright.
function KJDWSS_grab( $template, $data = [] ) {
    ob_start();
    KJDWSS_view( $template, $data );
    return ob_get_clean();
}
