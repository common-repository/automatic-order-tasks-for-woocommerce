<?php 

if ( !defined( 'ABSPATH' ) ) exit; // exit if accessed directly

if ( !class_exists('AOTFW_Settings_Api') ) {
  /**
   * Class responsible for saving and retrieving config data
   * 
   * Important note: The API doesn't sanitize the data. Sanitizing output
   * is the responsibility of each individual task before execution.
   *
   *
   * @since      1.0.0
   * @package    Automatic_Order_Tasks
   * @subpackage Automatic_Order_Tasks/settings
   * @author     Steven Mønsted Nielsen (contact@webirium.com)
   */
  class AOTFW_Settings_Api {
    private const SETTINGS_OPTION_KEY = '_aotfw-config';

    private static $instance;

    private function __construct() {}

    public static function get_instance() {
      if ( !self::$instance ) {
        self::$instance = new AOTFW_Settings_Api();
      }
      return self::$instance;
    }

    /**
     * Retrieve the tasks configuration for a WooCommerce Order Status.
     *
     * @param string $order_status_name The WooCommerce Order Status string name
     * @return void
     */
    public function get_config($order_status_name) {
      $configs = get_option( self::SETTINGS_OPTION_KEY );

      if ( empty( $configs ) || empty( $configs[$order_status_name] ?? null ) ) {
        return array(); // return empty array when no config saved
      }

      return json_decode ( $configs[$order_status_name], true );
    }

    /**
     * Update the tasks configuration for a WooCommerce Order Status.
     *
     * @param string $order_status_name The WooCommerce Order Status string name
     * @param array $order_status_config The configuration to be updated
     * @return void
     */
    public function update_config($order_status_name, $order_status_config) {

      $old_configs = get_option( self::SETTINGS_OPTION_KEY );
      
      $upd_configs = [];
      if ( $old_configs !== false ) {
        $upd_configs = $old_configs;
      }

      $new_config_json = json_encode( $order_status_config, JSON_UNESCAPED_SLASHES );

      $upd_configs[$order_status_name] = $new_config_json;

      update_option( self::SETTINGS_OPTION_KEY, $upd_configs, false );
    }

    /**
     * Get all available post categories, including hidden ones.
     *
     * @return array List of category objects
     */
    public function get_post_categories() {
      $args = array(
        'hide_empty' => false
      );
      return get_categories($args);
    }

    /**
     * Get all available users.
     *
     * @return array List of users
     */
    public function get_users() {
      $args = array(
        'count_total' => false,
        'fields' => array(
          'ID',
          'display_name'
        )
      );

      $users = get_users($args);

      return $users;
    }

    /**
     * Get all WooCommerce shipping methods.
     *
     * @return array List of shipping methods
     */
    public function get_shipping_methods() {
      $shipping_methods = array_map( function($x) { 
        return array( 'id' => $x->id, 'method_title' => $x->method_title );
      },
      array_values ( WC()->shipping->get_shipping_methods() ) );

      return $shipping_methods;
    }
  }
}
