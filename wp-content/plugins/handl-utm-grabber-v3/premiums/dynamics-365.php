<?php

function handl_prefill_dynamics_365_forms(){
	print "
	<script>
	if (typeof MsCrmMkt !== 'undefined'){
	    MsCrmMkt.MsCrmFormLoader.on(\"afterFormLoad\", function(event) {
			jQuery(event.formPlaceholder).find('input').each(function(id, field){
			    var thiss = jQuery(field)			
			    
			    
			    if ( typeof(thiss.attr('title')) != 'undefined' ){
			        if (thiss.attr('title') != ''){
			            //console.log('1',field,thiss.attr('title'))
			            var param = thiss.attr('title').toLowerCase().replace(/\s+/g,'_')
					    if (param != ''){
					        thiss.val(Cookies.get(param))
					    }
			        }else{
			            //console.log('2',field,thiss.attr('title'))
			        }
				}else{
			        //console.log('3',field,thiss.attr('title'))
				}
			})
	//		handl_utm_all_params.forEach(function(param) {
	//		    jQuery('input[title='+param+']').val(Cookies.get(param));
	//		})
		})
	}
	
	</script>";

}

add_action( 'wp_footer', 'handl_prefill_dynamics_365_forms' );