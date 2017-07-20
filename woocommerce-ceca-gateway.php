<?php
/*
Plugin Name: WooCommerce Ceca Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce with an Ceca gateway.
Version: 1.0
Author: juanmirod
Author URI: http://juanmirodriguez.es/
 
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 
function woocommerce_gateway_ceca_init() {
 
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
    /**
     * Localization - NOT AVAILABLE YET
     */
    // load_plugin_textdomain('wc-gateway-ceca', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    if ( class_exists( 'WC_Payment_Gateway_CC' ) ) {
        require_once dirname( __FILE__ ) . '/includes/class-ceca.php';
    } else {
        require_once dirname(__FILE__) . '/includes/class-ceca-deprecated.php';
    }
    if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {
        require_once dirname( __FILE__ ) . '/includes/class-ceca-subscriptions.php';
        if( class_exists('WC_Payment_Token_CC') ){
            require_once dirname( __FILE__ ) . '/includes/class-ceca-token.php';
        }
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_ceca' );
}
add_action('plugins_loaded', 'woocommerce_gateway_ceca_init', 0);

/**
 * Add Ceca Gateway to WooCommerce
 **/
function woocommerce_add_gateway_ceca($methods) {
    if(class_exists( 'WC_Subscriptions_Order' )){
        $methods[] = 'WC_Gateway_Ceca_Subscription';
    }else{
        $methods[] = 'WC_Gateway_Ceca';
    }
    return $methods;
}

/**
 * Display the testmode notice
 **/
function woocommerce_debug_gateway_ceca_notice(){
    $ceca_settings = get_option( 'woocommerce_ceca_settings' );
    $debug 	= $ceca_settings['debug'];
    if ( 'yes' == $debug  ) {
        ?>
        <div class="update-nag">
            El modo debug en CECA está aun activo, Click <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=ceca">aquí</a> para deshabilitarlo cuando quieras empezar a aceptar pagos reales en su sitio.
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'woocommerce_debug_gateway_ceca_notice' );