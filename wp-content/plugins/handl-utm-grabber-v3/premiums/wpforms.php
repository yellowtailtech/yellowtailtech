<?php

function handl_wpf_dev_register_smarttag( $tags ) {

    $fields = generateUTMFields();
    foreach ($fields as $field) {
        $tags['handl_'.$field] = $field;
    }
    return $tags;
}
add_filter( 'wpforms_smart_tags', 'handl_wpf_dev_register_smarttag' );



function handl_wpf_dev_process_smarttag( $content, $tag ) {

    $fields = generateUTMFields();
    foreach ($fields as $field) {
        if ('handl_'.$field === $tag) {
            $cookie_field = isset($_COOKIE[$field]) ? $_COOKIE[$field] : '';
            $content = str_replace('{handl_'.$field.'}', $cookie_field, $content);
            return $content;
        }
    }
    return $content;
}
add_filter( 'wpforms_smart_tag_process', 'handl_wpf_dev_process_smarttag', 10, 2 );