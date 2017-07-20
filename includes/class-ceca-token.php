<?php
/**
 * User: marioandujar
 * Date: 20/07/17
 * Time: 11:48
 */

class WC_Gateway_Ceca_Token extends WC_Payment_Token_CC {

    /** @protected string Token Type String */
    protected $type = 'ceca';


    /**
     * Get ceca idusuario
     */
    public function get_ceca_user_id() {
        return $this->get_meta( 'ceca_user_id' );
    }

    /**
     * Set ceca idusuario
     */
    public function set_ceca_user_id( $ceca_user_id ) {
        $this->add_meta_data( 'ceca_user_id', $ceca_user_id, true );
    }

    /**
     * Get ceca identificador de token
     */
    public function get_ceca_token_id() {
        return $this->get_meta( 'ceca_user_id' );
    }

    /**
     * Set ceca identificador de token
     */
    public function set_ceca_token_id( $ceca_token_id ) {
        $this->add_meta_data( 'ceca_token_id', $ceca_token_id, true );
    }

    /**
     * Get card type
     */
    public function get_ceca_card_type() {
        return $this->get_meta( 'ceca_card_type' );
    }

    /**
     * Set card type
     */
    public function set_ceca_card_type( $card_type_number ) {
        $card_types = [
            'None',
            'Visa',
            'electron',
            'MasterCard',
            'Maestro',
            'AMEX'
        ];
        $this->add_meta_data( 'ceca_card_type', $card_types[$card_type_number], true );
    }


    public function validate(){
        if (false === parent::validate()) {
            return false;
        }
        if ( ! $this->get_ceca_user_id() ) {
            return false;
        }
        return true;
    }

}