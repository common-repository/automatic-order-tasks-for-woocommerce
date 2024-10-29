<?php

if ( !defined( 'ABSPATH' ) ) exit; // exit if accessed directly


if ( !class_exists( 'AOTFW_Abstract_Order_Task' ) ) {
  /**
   * Abstract base class for an Automatic Order Task
   *
   *
   * @since      1.0.0
   * @package    Automatic_Order_Tasks
   * @subpackage Automatic_Order_Tasks/settings
   * @author     Steven Mønsted Nielsen (contact@webirium.com)
   */
  abstract class AOTFW_Abstract_Order_Task {

    private $task_ID;
    private $tag_replaces_map = [];

    protected $args;
    protected $defaults = array(
      'disabled'    => false,
      'conditions'   => null
    );

    public function __construct( $task_ID, $args ) {
      $this->task_ID = $task_ID;
      $this->args = $this->sanitize_args( wp_parse_args( $args, $this->defaults ) );
    }


    public abstract function do_task( $order );

    protected abstract function sanitize_args( $args );

    protected abstract function escape_args( $args );

    public function get_task_ID() {
      return $this->task_ID;
    }

    public function get_args_sanitized() {
      return $this->args; // args already sanitized on construct
    }

    public function get_args_sanitized_escaped() {
      $args = $this->get_args_sanitized();
      $escaped_args = $this->escape_args( $args );
      return $escaped_args;
    }

    public function to_json() {
      $json = json_encode( array(
        'task_ID' => $this->task_ID,
        'args' => $this->args
      ) );
      return $json;
    }

    protected function add_tag( $context, $name, $replace_callback ) {
      $this->tag_replaces_map[$context]['{{' . $name . '}}'] = $replace_callback();
    }

    protected function remove_tag( $context, $name ) {
      unset( $this->tag_replaces_map[$context]['{{' . $name . '}}'] );
    }

    protected function add_default_tags_for_field( $context, $order ) {
      $c = $context;
      $this->add_tag( $c, 'order id', function() use ( $order ) {
        return $order->get_id();
      });
      $this->add_tag( $c, 'billing name', function() use ( $order ) {
        return $order->get_formatted_billing_full_name();
      });
      $this->add_tag( $c, 'shipping name', function() use ( $order ) {
        return $order->get_formatted_shipping_full_name();
      });
      $this->add_tag( $c, 'billing phone', function() use ( $order ) {
        return $order->get_billing_phone();
      });
      $this->add_tag( $c, 'billing company', function() use ( $order ) {
        return $order->get_billing_company();
      });
    }

    protected function add_default_tags_for_textarea( $context, $order ) {
      $c = $context;
      $this->add_tag( $c, 'order id', function() use ($order) {
        return $order->get_id();
      });
      $this->add_tag( $c, 'order details', function() use ( $order ) {
        ob_start();
        WC_Emails::instance()->order_details( $order );
        return ob_get_clean();
      });
      $this->add_tag( $c, 'billing email', function() use ( $order ) {
        return $order->get_billing_email();
      });
      $this->add_tag( $c, 'billing name', function() use ( $order) {
        return $order->get_formatted_billing_full_name();
      });
      $this->add_tag( $c, 'billing phone', function() use ( $order ) {
        return $order->get_billing_phone();
      });
      $this->add_tag( $c, 'billing company', function() use ( $order ) {
        return $order->get_billing_company();
      });
      $this->add_tag( $c, 'billing address', function() use ( $order ) {
        return $order->get_formatted_billing_address();
      });
      $this->add_tag( $c, 'shipping name', function() use ( $order ) {
        return $order->get_formatted_shipping_full_name();
      });
      $this->add_tag( $c, 'shipping address', function() use ( $order ) {
        return $order->get_formatted_shipping_address();
      });
      $this->add_tag( $c, 'order note', function() use ( $order ) {
        return $order->get_customer_note();
      });
    }

    protected function parse_tags( $context, $content ) { //TODO: Optimize this so only used tags are getting extracted
      $tags = array_keys( $this->tag_replaces_map[$context] );
      $replaces = array_values( $this->tag_replaces_map[$context] );
      return str_replace( $tags, $replaces, $content );
    }
  }
}

// SEND MAIL ORDER TASK //
if ( !class_exists( 'AOTFW_Sendmail_Order_Task' ) ) {
  class AOTFW_Sendmail_Order_Task extends AOTFW_Abstract_Order_Task {

    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'subject' => 'No subject',
        'recipients' => '',
        'message' => ''
      ));

      parent::__construct( 'sendmail', $args );
    }

    public function do_task( $order ) {

      $this->set_tag_replacement_map( $order );

      $args = $this->get_args_sanitized();

      $recipients = $this->parse_tags( 'recipients', array_map( function( $recipient ) { return $recipient['value']; }, $args['recipients'] ) );
      $subject = $this->parse_tags( 'subject', $args['subject'] );
      $message = $this->parse_tags( 'message', $args['message'] );

      if ( has_filter( 'AOTFW_sendmail_order_task_message' ) ) {
        $message = apply_filters('AOTFW_sendmail_order_task_message', $message, $order );
      }

      $wc_mail = WC_Emails::instance();

      $message = $wc_mail->wrap_message( $subject, $message );

      foreach ( $recipients as $recipient ) {
        $wc_mail->send( $recipient, $subject, $message );
      }
    }

    protected function sanitize_args( $args ) {
      $s_args['subject'] = sanitize_text_field( $args['subject'] );

      $s_args['recipients'] = array_filter( $args['recipients'],
      function( $recipient ) {
        if ( !isset( $recipient['value'] ) || !isset( $recipient['label'] ) )
          return false;

        $recipient['label'] = sanitize_text_field( $recipient['label'] );
        if ( preg_match( '/^{+.+}$/iD', $recipient['value'] ) ) { // if tag
          $recipient['value'] = sanitize_text_field( $recipient['value'] ); // sanitize tag
        } else { // else sanitize email
          $recipient['value'] = sanitize_email( $recipient['value'] );
        }
        return ( !empty($recipient['label'] ) && !empty( $recipient['value'] ) ); // if all passed sanitization - keep it
      });

      $s_args['message'] = wp_kses_post( $args['message'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['subject'] = $args['subject']; // input value expected, already sanitized: sanitize_text_field

      $e_args['recipients'] = array_map( function( $recipient ) {
        if ( !isset( $recipient['value'] ) || !isset( $recipient['label'] ) )
          return '';

          $recipient['label'] = esc_html( $recipient['label'] );
          $recipient['value'] = esc_attr( $recipient['value'] );

          return $recipient;
      }, $args['recipients'] );

      $e_args['message'] = $args['message']; // html output is expected, already sanitized: wp_kses_post

      return $e_args;
    }

    private function set_tag_replacement_map( $order ) {
      $this->set_recipients_tag_map( $order );
      $this->set_subject_tag_map( $order );
      $this->set_content_tag_map( $order );
    }

    private function set_recipients_tag_map( $order ) {
      $c = 'recipients';

      $this->add_tag( $c, 'admin email', function() {
        return get_bloginfo('admin_email');
      });
      $this->add_tag( $c, 'billing email', function() use ($order) {
        return $order->get_billing_email();
      });
    }

    private function set_subject_tag_map( $order ) {
      $this->add_default_tags_for_field( 'subject', $order );
    }

    private function set_content_tag_map( $order ) {
      $this->add_default_tags_for_textarea( 'message', $order );
    }

  }
}

// CREATE POST ORDER TASK //
if ( !class_exists('AOTFW_Createpost_Order_Task') ) {
  class AOTFW_Createpost_Order_Task extends AOTFW_Abstract_Order_Task {

    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'subject' => '',
        'content' => '',
        'categories' => array(),
        'author' => '',
      ));

      parent::__construct( 'createpost', $args );
    }

    public function do_task( $order ) {
      $this->set_tag_replacement_map( $order );

      $args = $this->get_args_sanitized();

      $subject = $this->parse_tags( 'subject', $args['subject'] );
      $content = $this->parse_tags( 'content', $args['content'] );
      $categories = $args['categories'];
      $author = $this->parse_tags( 'author', $args['author'] );

      if ( has_filter( 'AOTFW_createpost_order_task_content' ) ) {
        $content = apply_filters('AOTFW_createpost_order_task_content', $content, $order );
      }

      $new_post = array(
        'post_title' => $subject,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_author' => $author,
        'post_category' => $categories
      );

      wp_insert_post( $new_post );
    }

    protected function sanitize_args( $args ) {
      $s_args['subject'] = sanitize_text_field ( $args['subject'] );
      $s_args['content'] = wp_kses_post( $args['content'] );
      $s_args['categories'] = array_filter( $args['categories'], function( $category_id ) {
        return intval( $category_id );
      } );
      $s_args['author'] = ( function() use ($args) {
        if ( preg_match( '/^{+.+}$/iD', $args['author'] ) ) {
          return sanitize_text_field( $args['author'] );
        }
        return intval( $args['author'] ) ?: '';
      })();

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['subject'] = $args['subject']; // field value expected, already sanitized: sanitize_text_field
      $e_args['content'] = $args['content']; // html output is expected, already sanitized: wp_kses_post

      $e_args['categories'] = array_map( function( $category_id ) {
        return esc_attr( $category_id );
      }, $args['categories'] );

      $e_args['author'] = $args['author']; // values expected, already sanitized: sanitize_text_field / intval

      return $e_args;
    }

    private function set_tag_replacement_map( $order ) {
      $this->add_default_tags_for_field( 'subject', $order );
      $this->add_default_tags_for_textarea( 'content', $order );

      $this->add_tag( 'author', 'customer', function() use ( $order ) {
        $customer_id = $order->get_customer_id();

        if (!$customer_id) {
          $customer_id = $this->defaults['author'];
        }
        return $customer_id;
      });
    }
  }
}

// LOG TO FILE ORDER TASK //
if ( !class_exists('AOTFW_Logtofile_Order_Task') ) {
  class AOTFW_Logtofile_Order_Task extends AOTFW_Abstract_Order_Task {

    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'content' => ''
      ));

      parent::__construct( 'logtofile', $args );
    }

    public function do_task( $order ) {
      $this->set_tag_replacement_map( $order );

      $args = $this->get_args_sanitized();

      $content = $args['content'];
      $content = $this->parse_tags( 'content', $content );

      $content = str_ireplace( array("<br />","<br>","<br/>"), "\n", $content ); // convert break tags to newlines

      $this->maybe_create_folder();

      $log_id = get_option( AOTFW_LOG_ID_OPTIONS_KEY );
      $log_upload_dir = wp_normalize_path( wp_get_upload_dir()['basedir'] ) . '/' . AOTFW_LOG_FOLDER_PREFIX . $log_id;

      $current_date = current_time( 'F d, Y H:i:s' );
      $order_id = $order->get_id();

      $start_str = "------- ${current_date} | order ${order_id} ----------------" . PHP_EOL;

      $file = fopen($log_upload_dir . '/logfile.txt', 'a' );
      fwrite( $file, $start_str );
      fwrite( $file, $content . PHP_EOL . PHP_EOL );

      fclose( $file );
    }

    protected function sanitize_args( $args ) {
      $s_args['content'] = wp_kses_post( $args['content'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['content'] = $args['content']; // html output is expected, already sanitized: wp_kses_post

      return $e_args;
    }

    private function maybe_create_folder() {
      $log_id = get_option( AOTFW_LOG_ID_OPTIONS_KEY );

      $upload_dir = wp_normalize_path( wp_get_upload_dir()['basedir'] );

      if ( empty( $log_id ) || !is_dir( $upload_dir . '/' . AOTFW_LOG_FOLDER_PREFIX . $log_id ) ) {
        $log_id = uniqid();
        update_option( AOTFW_LOG_ID_OPTIONS_KEY, $log_id );

        mkdir( $upload_dir . '/' . AOTFW_LOG_FOLDER_PREFIX . $log_id );
      }
    }

    private function set_tag_replacement_map( $order ) {
      $this->add_default_tags_for_textarea( 'content', $order );
      $this->remove_tag( 'content', 'order details' );
    }
  }
}

// CUSTOM ORDER FIELD ORDER TASK //
if ( !class_exists('AOTFW_CustomOrderfield_Order_Task') ) {
  class AOTFW_CustomOrderfield_Order_Task extends AOTFW_Abstract_Order_Task {
    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'name' => '',
        'value' => ''
      ));

      parent::__construct( 'customorderfield', $args );
    }

    public function do_task( $order ) {
      $this->set_tag_replacement_map( $order );

      $args = $this->get_args_sanitized();

      $name = $args['name'];
      $name = $this->parse_tags( 'name', $name );

      $value = $args['value'];
      $value = $this->parse_tags( 'value', $value );
      $value = str_ireplace( array("<br />","<br>","<br/>"), "\n", $value ); // convert break tags to newlines

      if ( !empty( $name ) ) {
        update_post_meta( $order->get_id(), $name, $value );
      }
    }

    protected function sanitize_args( $args ) {
      $s_args['name'] = sanitize_text_field ( $args['name'] );
      $s_args['value'] = wp_kses_post( $args['value'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['name'] = $args['name']; // input value expected, already sanitized: sanitize_text_field
      $e_args['value'] = $args['value']; // // html output is expected, already sanitized: wp_kses_post

      return $e_args;
    }

    private function set_tag_replacement_map( $order ) {
      $this->add_default_tags_for_field( 'name', $order );

      $this->add_default_tags_for_textarea( 'value', $order );
      $this->remove_tag( 'value', 'order details' );
    }
  }
}

// CHANGE SHIPPING ORDER TASK //
if ( !class_exists('AOTFW_Changeshipping_Order_Task') ) {
  class AOTFW_Changeshipping_Order_Task extends AOTFW_Abstract_Order_Task {
    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'new_shipping_name' => '',
        'new_shipping_method' => '',
      ));

      parent::__construct( 'changeshipping', $args );
    }

    public function do_task( $order ) {
      $this->set_tag_replacement_map( $order );

      $args = $this->get_args_sanitized();

      $new_shipping_name = $args['new_shipping_name'];
      $new_shipping_name = $this->parse_tags( 'new_shipping_name', $new_shipping_name );

      $new_shipping_method = $args['new_shipping_method'];

      foreach ( $order->get_items('shipping') as $item_id => $item ) {

        $shipping_methods = WC()->shipping->get_shipping_methods();

        foreach ( $shipping_methods as $id => $shipping_method ) {
          if ( $shipping_method->id === $new_shipping_method ) {
            $item->set_method_id( $shipping_method->get_rate_id() );

            if ( !empty( $new_shipping_name ) ) {
              $item->set_method_title( $new_shipping_name );
            } else { // if no custom title set, use the default one
              $item->set_method_title( $shipping_method->get_method_title() );
            }

            $item->save();
          }
        }
      }
    }

    protected function sanitize_args( $args ) {
      $s_args['new_shipping_name'] = sanitize_text_field( $args['new_shipping_name'] );
      $s_args['new_shipping_method'] = sanitize_key( $args['new_shipping_method'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['new_shipping_name'] = $args['new_shipping_name']; // input value expected, already sanitized: sanitize_text_field
      $e_args['new_shipping_method'] = esc_attr( $args['new_shipping_method'] );

      return $e_args;
    }

    private function set_tag_replacement_map( $order ) {
      $this->add_default_tags_for_field( 'new_shipping_name', $order );
    }
  }
}

// SEND WEBHOOK ORDER TASK //
if ( !class_exists('AOTFW_Sendwebhook_Order_Task') ) {
  class AOTFW_Sendwebhook_Order_Task extends AOTFW_Abstract_Order_Task {
    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'delivery_url' => '',
        'secret' => '',
      ));

      parent::__construct( 'sendwebhook', $args );
    }

    public function do_task( $order ) {

      $args = $this->get_args_sanitized();

      $delivery_url = $args['delivery_url'];
      $secret = $args['secret'];

      if ( !empty( $delivery_url ) ) {
        $webhook = new WC_Webhook();
        $webhook->set_delivery_url( $delivery_url );
        $webhook->set_secret( $secret );
        $webhook->set_topic( 'action.wc_order-' . $order->get_status() );

        $webhook->deliver( $order->get_data() );
      }
    }

    protected function sanitize_args( $args ) {
      $s_args['delivery_url'] = esc_url_raw( $args['delivery_url'] );
      $s_args['secret'] = sanitize_text_field( $args['secret'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['delivery_url'] = $args['delivery_url']; // field value expected, already sanitized: esc_url_raw
      $e_args['secret'] = $args['secret']; // input value expected, already sanitized: sanitize_text_field

      return $e_args;
    }
  }
}


// TRASH ORDER ORDER TASK //
if ( !class_exists('AOTFW_Trashorder_Order_Task') ) {
  class AOTFW_Trashorder_Order_Task extends AOTFW_Abstract_Order_Task {
    public function __construct( $args ) {
      $this->defaults = array_merge( $this->defaults, array(
        'reason' => '',
      ));

      parent::__construct( 'trashorder', $args );
    }

    public function do_task( $order ) {
      $reason = $this->args['reason'];
      $reason = sanitize_text_field( $reason );

      if ( !empty( $reason ) ) {
        update_post_meta( $order->get_id(), 'trash_reason', $reason );
      }

      add_action( 'shutdown', function() use ( $order ) { // delayed execution
        wp_trash_post( $order->get_id() );
      } );
    }

    protected function sanitize_args( $args ) {
      $s_args['reason'] = sanitize_text_field( $args['reason'] );

      return $s_args;
    }

    protected function escape_args( $args ) {
      $e_args['reason'] = $args['reason']; // field value expected, already sanitized: sanitize_text_field

      return $e_args;
    }
  }
}