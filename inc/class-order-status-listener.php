<?php 
if ( !defined( 'ABSPATH' ) ) exit; // exit if accessed directly

if ( !class_exists( 'AOTFW_Order_Status_Listener' ) ) {
  class AOTFW_Order_Status_Listener {

    private static $instance = null;
    
    
    private function __construct() {
      add_action( 'woocommerce_order_status_changed', array( $this, 'action__do_tasks' ), 10, 3 );
    }
    

    public static function get_instance() {
      if ( !self::$instance ) {
        self::$instance = new AOTFW_Order_Status_Listener();
      }
      return self::$instance;
    }

    
    public function action__do_tasks( $order_id, $old_status, $new_status ) {
      $this->require_tasks(); // requiring tasks late, as the file is only necessary when executing tasks.
      
      $task_factory = AOTFW_Order_Task_Factory::get_instance();
      $settings_api = AOTFW_Settings_Api::get_instance();
      $order = wc_get_order( $order_id );

      $new_status = 'wc-' . $new_status; // add the wc prefix

      $config = $settings_api->get_config( $new_status );

      if ( !empty( $config ) && is_array( $config ) ) {
        foreach ( $config as $task_config ) {
          if ( !empty( $task_config ) && isset( $task_config['id'] ) ) {
  
            if ( $this->should_run( $order_id, $task_config ) ) {
              $task = $task_factory->get( $task_config['id'], $task_config['fields'] );
              $task->do_task( $order );
            }
          }
        }
      }
    }

    /**
     * Determines whether the action should run based on various meta settings.
     * Returns true if so.
     */
    private function should_run( $order_id, $task_config ) {
      if ( empty($task_config['metaSettings']) ) // return true if no meta setting limiters are set.
        return true;

      $meta_settings = $task_config['metaSettings'];

      if ( $meta_settings['runonce'] === true && !empty( $task_config['uniqid'] ) ) {
        $ran_tasks_pm = get_post_meta( $order_id, '_aotfw_done_runonce_tasks', true );
        $ran_tasks = empty( $ran_tasks_pm ) ? array() : explode( ',', $ran_tasks_pm );

        if ( in_array( $task_config['uniqid'], $ran_tasks ) ) {
          return false; // if already run, it should not run again.
        } else {
          // else add it to the list of already run tasks.
          $ran_tasks[] = $task_config['uniqid'];
          update_post_meta( $order_id, '_aotfw_done_runonce_tasks' , implode( ',', $ran_tasks ));
        }
      }

      return true;
    }

    private function require_tasks() {
      require_once( AOTFW_PLUGIN_PATH . 'inc/tasks/class-order-task.php' );
      require_once( AOTFW_PLUGIN_PATH . 'inc/tasks/class-order-task-factory.php' );

    }
  }
}

?>