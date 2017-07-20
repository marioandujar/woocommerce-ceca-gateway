<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class WC_Gateway_Ceca extends WC_Payment_Gateway_CC {

    public function __construct() {
        $this->id = 'ceca';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = 'Pasarela CECABANK';
        $this->method_description = 'Pasarela de pago para pago con tarjetas de crédito.';

        $this->supports           	= array(
            'products',
            'tokenization',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer'
        );

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title                = $this->get_option( 'title' );
        $this->description          = $this->get_option( 'description' );
        $this->merchand_id          = $this->get_option( 'merchand_id' );
        $this->acquirer_bin         = $this->get_option( 'acquirer_bin' );
        $this->terminal_id          = $this->get_option( 'terminal_id' );
        $this->currency             = $this->get_option( 'currency' );
        $this->language             = $this->get_option( 'language' );
        $this->debug                = $this->get_option( 'debug' ) == 'yes';

        $this->ceca_url_debug       = $this->get_option( 'ceca_url_debug' );
        $this->password_debug       = $this->get_option( 'password_debug' );
        $this->ceca_url_production  = $this->get_option( 'ceca_url_production' );
        $this->password_production  = $this->get_option( 'password' );


        add_action( 'valid-ceca-standard-ipn-request', array( $this, 'successful_request' ) );
        add_action( 'woocommerce_receipt_ceca', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Payment listener/API hook
        add_action( 'woocommerce_api_wc_gateway_ceca', array( $this, 'check_cecabank_response' ) );

    }

    /**
     * Check for PayPal IPN Response
     *
     * @access public
     * @return void
     */
    function check_cecabank_response() {

        @ob_clean();

        $response = ! empty( $_POST ) ? $_POST : false;

        if ( $response ) {

            header( 'HTTP/1.1 200 OK' );
            do_action( "valid-ceca-standard-ipn-request", $response );

        } else {

            wp_die( "CECABANK Request Failure", "CECABANK", array( 'response' => 200 ) );

        }

    }

    function successful_request( $posted ) {

        $posted = stripslashes_deep( $posted );

        $order_id   = $posted['Num_operacion'];

        if(empty($posted['Importe'])
            || empty( $posted['Referencia'])
            || empty( $posted['Num_operacion']) ) {
            wp_die( "ERROR", "CECABANK", array( 'response' => 200 ) );
        }

        // search for this order and store the $ref
        $order = new wc_get_order($order_id);
        if ( $order ) {
            update_post_meta( $order->id, 'REF', wc_clean( $posted['Referencia'] ) );
        }
        $order->payment_complete();

        wp_die( '$*$OKY$*$', "CECABANK", array( 'response' => 200 ) );

    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Activar/Desactivar' ),
                'type' => 'checkbox',
                'label' => __('Permitir pasarela de pago CECABANK'),
                'default' => 'no'
            ),
            'debug' => array(
                'title' => __( 'Activar/Desactivar' ),
                'type' => 'checkbox',
                'label' => __('Modo de prueba'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title:', 'mrova'),
                'type'=> 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mrova'),
                'default' => __('Pago con tarjeta')
            ),
            'description' => array(
                'title' => __('Description:', 'mrova'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'mrova'),
                'default' => __('Paga de forma segura con la pasarela de pago CECABANK.')
            ),
            'merchand_id' => array(
                'title' => __('Merchant ID'),
                'type' => 'text',
                'description' => __('Identifica al comercio. Facilitado por la caja en el proceso de alta.')
            ),
            'acquirer_bin' => array(
                'title' => __('acquirer BIN'),
                'type' => 'text',
                'description' => __('Identifica a la caja. Facilitado por la caja en el proceso de alta.')
            ),
            'terminal_id' => array(
                'title' => __('Terminal ID'),
                'type' => 'text',
                'description' => __('Identifica al terminal. Facilitado por la caja en el proceso de alta')
            ),
            'ceca_url_production' => array(
                'title' => __( 'URL entorno de producción' ),
                'type' => 'text',
                'default' => __('https://comercios.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS')
            ),
            'password' => array(
                'title' => __('Clave de encriptación REAL'),
                'type' => 'text',
                'description' => __('Facilitado por la caja, a diferencia de los demás parámetros la clave cambia del entorno de pruebas al entorno real.')
            ),
            'ceca_url_debug' => array(
                'title' => __( 'URL entorno de desarrollo' ),
                'type' => 'text',
                'default' => __('http://democonsolatpvvirtual.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS')
            ),
            'password_debug' => array(
                'title' => __('Clave de encriptación PRUEBAS'),
                'type' => 'text',
                'description' => __('Facilitado por la caja, a diferencia de los demás parámetros la clave cambia del entorno de pruebas al entorno real.')
            ),
            'currency' => array(
                'title' => __('Tipo Moneda'),
                'type' => 'text',
                'description' => __('Es el código ISO-4217 correspondiente a la moneda en la que se efectúa el pago, 978 para euros, para más info ver documentación CECABANK'),
                'default' => '978'
            ),
            'language' => array(
                'title' => __('Código de idioma'),
                'type' => 'select',
                'description' => __('Selecciona el idioma en el que se mostrará la pasarela de pago.'),
                'default' => '1',
                'options' => array(
                    '1' => __('Español'),
                    '2' => __('Catalán'),
                    '3' => __('Euskera'),
                    '4' => __('Gallego'),
                    '5' => __('Valenciano'),
                    '6' => __('Inglés'),
                    '7' => __('Francés'),
                    '8' => __('Alemán'),
                    '9' => __('Portugués'),
                    '10' => __('Italiano'),
                    '14' => __('Ruso'),
                    '15' => __('Noruego')
                )
            )
        );
    }

    function receipt_page( $order ) {
        echo '<p>' . __( 'Gracias, tu orden está ahora pendiente de pago, deberías ser redirigido en unos segundos a la pasarela de pago con tarjeta de CECABANK.' ) . '</p>';

        echo $this->generate_ceca_form( $order );
    }

    function calculate_sign ( $order ) {

        // Clave_encriptacion+MerchantID+AcquirerBIN+TerminalID+Num_operacion+Importe+
        // TipoMoneda+Exponente+“SHA1”+URL_OK+URL_NOK
        $signature_str = $this->password
            .$this->merchand_id
            .$this->acquirer_bin
            .$this->terminal_id
            .$order->id
            .$order->get_total()*100
            .$this->currency
            .'2SHA1'
            .$this->get_return_url( $order )
            .get_permalink( woocommerce_get_page_id( 'checkout' ) );

        return sha1($signature_str);
    }

    function get_ceca_args( $order ) {
        $result = array();

        $result['MerchantID']       = $this->merchand_id;
        $result['AcquirerBIN']      = $this->acquirer_bin;
        $result['TerminalID']       = $this->terminal_id;
        $result['Num_operacion']    = $order->id;
        $result['Importe']          = $order->get_total()*100;
        $result['TipoMoneda']       = $this->currency;
        $result['Exponente']        = '2';
        $result['URL_OK']           = $this->get_return_url( $order );
        $result['URL_NOK']          = get_permalink( woocommerce_get_page_id( 'checkout' ) );
        $result['Idioma']           = $this->language;
        $result['Cifrado']          = 'SHA1';
        $result['Firma']            = $this->calculate_sign( $order );
        $result['Pago_soportado']   = 'SSL';


        return $result;
    }

    /**
     * Generate the CECA button link
     *
     * @access public
     * @param mixed $order_id
     * @return string
     */
    function generate_ceca_form( $order_id ) {

        $order = new wc_get_order( $order_id );

        $ceca_args = $this->get_ceca_args( $order );

        $ceca_args_array = array();


        foreach ( $ceca_args as $key => $value ) {
            $ceca_args_array[] = '<input type="hidden" name="'.esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
        }
        if($this->debug){
            $url_tpv = $this->ceca_url_debug;
        }else{
            $url_tpv = $this->ceca_url_production;
        }

        return '<form action="' . esc_url( $url_tpv ) . '" method="post" id="ceca_payment_form" target="_top">
                    ' . implode( '', $ceca_args_array ) . '
                    <!-- Button Fallback -->
                    <div class="payment_buttons">
                        <input type="submit" class="button alt" id="submit_ceca_payment_form" value="' . __( 'Pagar' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
                    </div>
                    <script type="text/javascript">
                        //jQuery(".payment_buttons").hide();
                    </script>
                </form>';

    }

    function process_payment( $order_id ) {
        global $woocommerce;

        if ( isset( $_POST['wc-ceca-payment-token'] ) && 'new' !== $_POST['wc-ceca-payment-token'] ) {
            $token_id = wc_clean( $_POST['wc-ceca-payment-token'] );
            $token    = WC_Payment_Tokens::get( $token_id );

            if ( $token->get_user_id() !== get_current_user_id() ) {

                wc_add_notice( 'CECA Invalid token ID', 'error' );

                return;

            } else {
                $this->process_token_payment( $token, $order_id );

                $order = wc_get_order( $order_id );

                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            }
        } else {

            if ( is_user_logged_in() && isset( $_POST['wc-ceca-new-payment-method'] ) && true === (bool) $_POST['wc-ceca-new-payment-method'] ) {

                update_post_meta( $order_id, '_wc_ceca_save_card', true );


            }

            $order = new wc_get_order( $order_id );

            return array(
                'result'    => 'success',
                'redirect'  => $order->get_checkout_payment_url( true )
            );
        }

    }

    /**
     * Process a token payment
     */
    public function process_token_payment( $token, $order_id ) {

        if ( $token && $order_id ) {

            if($this->debug){
                $url_tpv = $this->ceca_url_debug;
            }else{
                $url_tpv = $this->ceca_url_production;
            }

        }
    }
}