<?php

function cookie_notice_ok_to_go( $good2go ){
    if (class_exists('Cookie_Notice') && $good2go['good2go'] === 1){
        if (isset( $_COOKIE['cookie_notice_accepted'] ) && $_COOKIE['cookie_notice_accepted'] === 'false'){
            $good2go['good2go'] = 0;
        }else{
            $good2go['good2go'] = 1;
        }
    }
    return $good2go;
}
add_filter( 'is_ok_to_capture_utms', 'cookie_notice_ok_to_go', 10, 1 );
