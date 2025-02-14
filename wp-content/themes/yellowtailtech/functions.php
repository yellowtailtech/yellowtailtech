<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.65' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style('hello-elementor-child-style',get_stylesheet_directory_uri() . '/style.css',['hello-elementor-theme-style',],HELLO_ELEMENTOR_CHILD_VERSION);

	wp_enqueue_style('owl-carousel-style',get_stylesheet_directory_uri() . '/assets/css/owl.carousel.min.css',array(),HELLO_ELEMENTOR_CHILD_VERSION);
	wp_enqueue_style('owl-theme-style',get_stylesheet_directory_uri() . '/assets/css/owl.theme.default.min.css',array(),HELLO_ELEMENTOR_CHILD_VERSION);

	wp_enqueue_style('slick-style', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',array(),'1.8.1');

    wp_enqueue_script( 'vimeo-js', 'https://player.vimeo.com/api/player.js', array('jquery'), '1.8.1', true );
	wp_enqueue_script( 'slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true );
	wp_enqueue_script( 'packery-js', 'https://cdnjs.cloudflare.com/ajax/libs/packery/2.1.2/packery.pkgd.min.js', array('jquery'), '2.1.2', true );
	wp_enqueue_script( 'imagesloaded-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.4/imagesloaded.pkgd.min.js', array('jquery'), '2.1.2', true );

    wp_enqueue_script( 'popperjs-js', 'https://unpkg.com/@popperjs/core@2', array('jquery'), '2.0', true );
    wp_enqueue_script( 'tippy-js', 'https://unpkg.com/tippy.js@6', array('jquery'), '6.0', true );
	

	//Main JS
	wp_enqueue_script( 'script-main', get_stylesheet_directory_uri() . '/main.js', array(), HELLO_ELEMENTOR_CHILD_VERSION, true );

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

function prefix_nav_description( $item_output, $item, $depth, $args ) {
    if ( !empty( $item->description ) ) {
        $item_output = str_replace( $args->link_after . '</a>', '<div class="menu-item-description">' . $item->description . '</div>' . $args->link_after . '</a>', $item_output );
    }
 
    return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'prefix_nav_description', 10, 4 );

function mySearchFilter($query) {
	if ($query->is_search) {
		$query->set('post_type', 'post');
	}
	return $query;
}
//add_filter('pre_get_posts','mySearchFilter');

/**
 * Disable the email address suggestion.
 *
 * @link  https://wpforms.com/developers/how-to-disable-the-email-suggestion-on-the-email-form-field/
 */
 
add_filter( 'wpforms_mailcheck_enabled', '__return_false' );

/**
 * Open. redirect in a new window
 *
 * @link https://wpforms.com/developers/how-to-open-redirect-in-a-new-window/
 */
 
function ytt_dev_open_redirect_new( ) {
?>
<script type="text/javascript">
    jQuery(function(){
        jQuery( "form#wpforms-form-30915" ).attr( "target", "_blank" );
        jQuery( "#wpforms-form-30915" ).on( 'submit', function(){
            location.reload(true);  
        });
    }); 
    </script>
<?php
}
add_action( 'wpforms_wp_footer_end', 'ytt_dev_open_redirect_new', 30 );

add_action('wp_footer', 'ytt_footer_scripts'); 
function ytt_footer_scripts() { 
    if ($_SERVER['GEOIP_COUNTRY_NAME'] != 'United States') {
    ?>
    <script>
    /*Your location is <?php echo $_SERVER['GEOIP_COUNTRY_NAME']; ?>*/
    window.onload = function() {   
        elementorProFrontend.modules.popup.showPopup( { id: 33117 } ); 
    }  
    </script>
    <?php
    }
}

function ytt_process_redirect_url( $url, $form_id, $fields, $form_data, $entry_id ) {
      
// Only run on my form with ID = 879.
    if ( absint( $form_data[ 'id' ] ) == 30927 ) {

        // Assign the checkbox field that shows the room number to a variable
        $name = $fields[1][ 'value' ]; 
        $email = $fields[2][ 'value' ];
        $utm_source = $fields[4][ 'value' ];
        $utm_medium = $fields[5][ 'value' ];
        $utm_campaign = $fields[6][ 'value' ];
        $utm_term = $fields[7][ 'value' ];
        $utm_content = $fields[8][ 'value' ]; 
         
        $url = $url.'?name='.$name.'&email='.$email.'&utm_source='.$utm_source.'&utm_medium='.$utm_medium.'&utm_campaign='.$utm_campaign.'&utm_term='.$utm_term.'&utm_content='.$utm_content;
    }

    if ( absint( $form_data[ 'id' ] ) == 30929 ) {
        $name = $fields[166][ 'value' ]; 
        $email = $fields[167][ 'value' ];

        $url = $url.'?name='.$name.'&email='.$email;
    }

    if ( absint( $form_data[ 'id' ] ) == 37365 ) {
        $fname = $fields[1]['first']; 
        $lname = $fields[1]['last']; 
        $email = $fields[2][ 'value' ];
        $phone = $fields[3][ 'value' ];

        $url = $url.'?first_name='.$fname.'&last_name='.$lname.'&email='.$email.'&a1='.$phone;
    }

    if ( absint( $form_data[ 'id' ] ) == 41606 ) {
        $fname = $fields[1]['first']; 
        $lname = $fields[1]['last']; 
        $email = $fields[4][ 'value' ];
        $phone = $fields[5][ 'value' ];

        $url = $url.'&first='.$fname.'&last='.$lname.'&email='.$email;
    }

    if ( absint( $form_data[ 'id' ] ) == 41721 ) {
        $referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
        $subdirectory = parse_url( $referer, PHP_URL_PATH );
        $subdirectory = rtrim( $subdirectory, '/' );


        $url = $url . '?origin=' . urlencode( $subdirectory );
    }
    
    return $url;


    
}
add_filter( 'wpforms_process_redirect_url', 'ytt_process_redirect_url', 10, 5 );






function ytt_set_author( $query ) {

    $current_url = explode("/", $_SERVER['REQUEST_URI']);

    $query->set( 'author_name', $current_url[2] );
}
add_action( 'elementor/query/set_author_article', 'ytt_set_author' );

add_action( 'rest_api_init', function () {
    register_rest_route( 'ytt/v1', '/vsl-no-answer/', array(
        'methods' => 'GET',
        'callback' => 'ytt_vsl_no_answer',
    ) );

    register_rest_route( 'ytt/v1', '/vsl-answered-but-not-interested/', array(
        'methods' => 'GET',
        'callback' => 'ytt_vsl_answered_but_not_interested',
    ) );
} );

function ytt_vsl_no_answer( $data ) {
    // Your logic to handle the request like returning a JSON response
    $username='api_7735Om5qFQR91tJN8mYXdz.3vewnJzwquVQ6TcAxX0Vmh';
    $password='';
    $URL='https://api.close.com/api/v1/data/search/';
    $ch = curl_init();

    $headers = array(
        'Content-Type:application/json'
    );

    $search_query = '{
        "limit": null,
        "query": {
            "negate": false,
            "queries": [
                {
                    "negate": false,
                    "object_type": "lead",
                    "type": "object_type"
                },
                {
                    "negate": false,
                    "queries": [
                        {
                            "negate": false,
                            "related_object_type": "opportunity",
                            "related_query": {
                                "negate": false,
                                "queries": [
                                    {
                                        "condition": {
                                            "object_ids": [
                                                "stat_McEODR79uGuxnJfEVRVRYaBKohwEmyi8R38ZuyU1MHA"
                                            ],
                                            "reference_type": "status.opportunity",
                                            "type": "reference"
                                        },
                                        "field": {
                                            "field_name": "status_id",
                                            "object_type": "opportunity",
                                            "type": "regular_field"
                                        },
                                        "negate": false,
                                        "type": "field_condition"
                                    },
                                    {
                                        "condition": {
                                            "before": {
                                                "direction": "past",
                                                "moment": {
                                                    "type": "now"
                                                },
                                                "offset": {
                                                    "days": 1,
                                                    "hours": 0,
                                                    "minutes": 0,
                                                    "months": 0,
                                                    "seconds": 0,
                                                    "weeks": 0,
                                                    "years": 0
                                                },
                                                "type": "offset",
                                                "which_day_end": "end"
                                            },
                                            "on_or_after": {
                                                "direction": "past",
                                                "moment": {
                                                    "type": "now"
                                                },
                                                "offset": {
                                                    "days": 1,
                                                    "hours": 0,
                                                    "minutes": 0,
                                                    "months": 0,
                                                    "seconds": 0,
                                                    "weeks": 0,
                                                    "years": 0
                                                },
                                                "type": "offset",
                                                "which_day_end": "start"
                                            },
                                            "type": "moment_range"
                                        },
                                        "field": {
                                            "field_name": "date_updated",
                                            "object_type": "opportunity",
                                            "type": "regular_field"
                                        },
                                        "negate": false,
                                        "type": "field_condition"
                                    }
                                ],
                                "type": "and"
                            },
                            "this_object_type": "lead",
                            "type": "has_related"
                        }
                    ],
                    "type": "and"
                }
            ],
            "type": "and"
        },
        "results_limit": null,
        "sort": []
    }';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $search_query);

    $response = curl_exec($ch);

    $dataSet = json_decode($response, true);

    

    foreach ($dataSet['data'] as $lead) {

        $url = "https://hooks.zapier.com/hooks/catch/20360963/25fupxn/";

        //The data you want to send via POST
        $fields = [
            'lead_id' => $lead['id'],
        ];

        //url-ify the data for the POST
        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

        //execute post
        $result = curl_exec($ch);
    }

    curl_close($ch);
    
}

function ytt_vsl_answered_but_not_interested( $data ) {
    // Your logic to handle the request like returning a JSON response
    $username='api_7735Om5qFQR91tJN8mYXdz.3vewnJzwquVQ6TcAxX0Vmh';
    $password='';
    $URL='https://api.close.com/api/v1/data/search/';
    $ch = curl_init();

    $headers = array(
        'Content-Type:application/json'
    );

    $search_query = '{
        "limit": null,
        "query": {
            "negate": false,
            "queries": [
                {
                    "negate": false,
                    "object_type": "lead",
                    "type": "object_type"
                },
                {
                    "negate": false,
                    "queries": [
                        {
                            "negate": false,
                            "related_object_type": "opportunity",
                            "related_query": {
                                "negate": false,
                                "queries": [
                                    {
                                        "condition": {
                                            "object_ids": [
                                                "stat_o34K4plwF2UaCIs9eb0g7pa2sOb8C2rK6cNbgXNPuly"
                                            ],
                                            "reference_type": "status.opportunity",
                                            "type": "reference"
                                        },
                                        "field": {
                                            "field_name": "status_id",
                                            "object_type": "opportunity",
                                            "type": "regular_field"
                                        },
                                        "negate": false,
                                        "type": "field_condition"
                                    },
                                    {
                                        "condition": {
                                            "before": {
                                                "direction": "past",
                                                "moment": {
                                                    "type": "now"
                                                },
                                                "offset": {
                                                    "days": 1,
                                                    "hours": 0,
                                                    "minutes": 0,
                                                    "months": 0,
                                                    "seconds": 0,
                                                    "weeks": 0,
                                                    "years": 0
                                                },
                                                "type": "offset",
                                                "which_day_end": "end"
                                            },
                                            "on_or_after": {
                                                "direction": "past",
                                                "moment": {
                                                    "type": "now"
                                                },
                                                "offset": {
                                                    "days": 1,
                                                    "hours": 0,
                                                    "minutes": 0,
                                                    "months": 0,
                                                    "seconds": 0,
                                                    "weeks": 0,
                                                    "years": 0
                                                },
                                                "type": "offset",
                                                "which_day_end": "start"
                                            },
                                            "type": "moment_range"
                                        },
                                        "field": {
                                            "field_name": "date_updated",
                                            "object_type": "opportunity",
                                            "type": "regular_field"
                                        },
                                        "negate": false,
                                        "type": "field_condition"
                                    }
                                ],
                                "type": "and"
                            },
                            "this_object_type": "lead",
                            "type": "has_related"
                        }
                    ],
                    "type": "and"
                }
            ],
            "type": "and"
        },
        "results_limit": null,
        "sort": []
    }';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $search_query);

    $response = curl_exec($ch);

    $dataSet = json_decode($response, true);

    

    foreach ($dataSet['data'] as $lead) {

        $url = "https://hooks.zapier.com/hooks/catch/7864477/2rkahcv/";

        //The data you want to send via POST
        $fields = [
            'lead_id' => $lead['id'],
        ];

        //url-ify the data for the POST
        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

        //execute post
        $result = curl_exec($ch);
    }

    curl_close($ch);
    
}