<?php
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

// Change logo in login-page
function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/IMAGE.png);
            padding-bottom: 0px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );

// Set link to your website-url in logo (login-page)
function my_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'my_login_logo_url' );

function my_login_logo_url_title() {
    return 'COMPANY NAME';
}
add_filter( 'login_headertitle', 'my_login_logo_url_title' );

// Hide toolbar when users logged in
function splen_remove_admin_bar() {
	if( !is_super_admin() )
		add_filter( 'show_admin_bar', '__return_false' );
}
add_action('wp', 'splen_remove_admin_bar');

/*Add cart button*/
// add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_add_to_cart' );


// Display only Reviews tab
add_filter( 'woocommerce_product_tabs', 'woo_reorder_tabs', 98 );
function woo_reorder_tabs( $tabs ) {
unset( $tabs['description'] );
unset( $tabs['additional_information'] );
return $tabs;
}

// Exclude a category on the shop page 'cadeaubonnen'
add_action( 'pre_get_posts', 'custom_pre_get_posts_query' );

function custom_pre_get_posts_query( $q ) {

	if ( ! $q->is_main_query() ) return;
	if ( ! $q->is_post_type_archive() ) return;

	if ( ! is_admin() && is_shop() ) {

		$q->set( 'tax_query', array(array(
			'taxonomy' => 'product_cat',
			'field' => 'slug',
			'terms' => array( 'cadeaubonnen' ), // Don't display products in the cadeaubonnen category on the shop page
			'operator' => 'NOT IN'
		)));

	}

	remove_action( 'pre_get_posts', 'custom_pre_get_posts_query' );

}

/* Add SOLD OUT badge */
add_action( 'woocommerce_before_shop_loop_item_title', function() {
    global $product;

    if ( !$product->is_in_stock() ) {
        echo '<span class="soldout">UITVERKOCHT</span>';
    }
});

// Add script in footer for specific page (winkel pagina)
function locatie_winkel_wp_footer($pid) { // Custom Google Map in winkel pagina
	global $post;
  if(($post->ID=="624") or ($post->ID=="176")){ // only for page Id = 624 'winkel' or 176 'Contact'
    echo "<!--map-locatie-winkel-->
	<script>
      function initMap() {
  var map = new google.maps.Map(document.getElementById('map-locatie-winkel'), {
    zoom: 18,
    center: {lat: 51.231615, lng: 2.916257}
  });

  var image = 'https://lolotte.be/wp-content/themes/Lolotte/images/locatie_lolotte.png';
  var beachMarker = new google.maps.Marker({
    position: {lat: 51.231450, lng: 2.916257},
    map: map,
    icon: image
  });
}
    </script>
    <script src='https://maps.googleapis.com/maps/api/js?callback=initMap'
        async defer></script>";
  }
}

add_action( 'wp_footer', 'locatie_winkel_wp_footer' );

// The SEO Framework: Adding share image
add_filter( 'the_seo_framework_og_image_after_featured', 'my_after_featured_fallback_image' );
function my_after_featured_fallback_image() {
   // No need to escape
   return 'https://lolotte.be/wp-content/uploads/2016/03/share_logo_lolotte.png';
}

/**
 * @snippet       Hide one shipping option in one zone when Free Shipping is available
 * @compatible    WooCommerce 2.6.1
 */
add_filter( 'woocommerce_package_rates', 'wk_unset_shipping_when_free_is_available_in_zone', 10, 2 );

function wk_unset_shipping_when_free_is_available_in_zone( $rates, $package ) {

    // Only unset rates if free_shipping is available
    if ( isset( $rates['free_shipping:4'] ) ) {
    unset( $rates['flat_rate:2'] ); // shipping method with ID
    unset( $rates['service_point_shipping_method:8'] ); // shipping method with ID (BE)
    unset( $rates['service_point_shipping_method:9'] ); // shipping method with ID (NL)
}

return $rates;

}

/**
 * @snippet       Disable Free Shipping if Cart has Shipping Class (WooCommerce 2.6+)
 * @testedwith    WooCommerce 2.6.1
 */
add_filter( 'woocommerce_package_rates', 'businessbloomer_hide_free_shipping_for_shipping_class', 10, 2 );

function businessbloomer_hide_free_shipping_for_shipping_class( $rates, $package ) {
$shipping_class_target = 137; // shipping class ID (to find it, WC-> Shipping Classes -> Inspect element 'data-id=137' or on the frontend via css)
$in_cart = false;
foreach( WC()->cart->cart_contents as $key => $values ) {
 if( $values[ 'data' ]->get_shipping_class_id() == $shipping_class_target ) {
  $in_cart = true;
  break;
 }
}
if( $in_cart ) {
 unset( $rates['free_shipping:4'] ); // shipping method with ID
 unset( $rates['service_point_shipping_method:8'] ); // shipping method with ID (BE)
 unset( $rates['service_point_shipping_method:9'] ); // shipping method with ID (NL)
}
return $rates;
}


/* REMOVE 'excl btw' in emails */
function sv_change_email_tax_label( $label ) {
    $label = '';
    return $label;
}
add_filter( 'woocommerce_countries_ex_tax_or_vat', 'sv_change_email_tax_label' );

/* Add datepicker to 'aanvraagformulier geboortelijst'*/
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

/**
 * Adds a note after a product price, showing how many points can be earned.
 * @param string price_html The HTML showing the product price.
 * @param WC_Product The product for which the price is being displayed.
 * @return string The product price, with the note appended to it.
 * @author Aelia
 * @link https://aelia.co
 */
add_filter('woocommerce_get_price_html', function($price_html, $product) {
  /* Possible improvements
   * - Don't show the note if the price is zero
   * - Replace text domain placeholder with real text domain
   */
  $note = '<div class="custom_note">';
  $note .= sprintf(__('Ontvang %s punten', 'lolotte-functions'), (string)floor($product->get_price()));
  $note .= '</div>';
  return $price_html . $note;
}, 10, 2);
?>
