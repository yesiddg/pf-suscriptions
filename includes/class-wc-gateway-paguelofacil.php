<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Paguelofacil class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Paguelofacil extends WC_Payment_Gateway {
	/**
	 * The delay between retries.
	 *
	 * @var int
	 */
	public $retry_interval;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->retry_interval = 1;
		$this->id = 'paguelofacil';
		$this->method_title = __( 'Paguelo Facil', 'woocommerce-gateway-paguelofacil' );
		$this->method_description = __( 'Pasarela de pago de paguelo fácil con soporte para woocommerce suscripciones.', 'woocommerce-gateway-paguelofacil' );
    $this->icon = "https://pfserver.net/img/VisaMC.png";
    $this->has_fields = true;
		$this->supports = array(
      'default_credit_card_form',
      'products',
      'tokenization',
      'subscriptions',
      'subscription_cancellation', 
      'subscription_suspension', 
      'subscription_reactivation',
      'subscription_amount_changes',
      'subscription_date_changes',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title = $this->settings['title'];
    $this->description = $this->settings['description'];
    $this->cclw = $this->settings['cclw'];
    $this->enabled = $this->settings['enabled'];
    $this->onsite = $this->settings['onsite'];
    $this->testmode = $this->get_option('testmode');
    
    //URL offsite
    $this->liveurl = 'https://secure.paguelofacil.com/LinkDeamon.cfm';
    $this->testurl = 'https://sandbox.paguelofacil.com/LinkDeamon.cfm';

    //URL onsite
    $this->liveurl_onsite = 'https://secure.paguelofacil.com/rest/ccprocessing/';
    $this->testurl_onsite = 'https://sandbox.paguelofacil.com/rest/ccprocessing/';
    
    $this->currency = get_woocommerce_currency();

    // Hooks.
    add_action('init', array($this, 'check_' . $this->id . '_resquest'));

    // Payment listener/API hook
    add_action('woocommerce_api_woocommerce_' . $this->id, array($this, 'check_' . $this->id . '_resquest'));
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
   * Initialize Gateway Settings Form Fields
   */
  function init_form_fields() {
    $this->form_fields = array(
      'enabled' => array(
        'title' => __('Enable/Disable', 'wc_paguelofacil'),
        'label' => __('Habilite PagueloFacil Gateway', 'wc_paguelofacil'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
      ),
      'title' => array(
        'title' => __('Title', 'wc_paguelofacil'),
        'type' => 'text',
        'default' => __('PagueloFacil', 'wc_paguelofacil')
      ),
      'cclw' => array(
        'title' => __('CCLW', 'wc_paguelofacil'),
        'type' => 'text',
        'description' => '',
        'default' => ''
      ),
      'testmode' => array(
        'title' => __('PagueloFacil sandbox', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Habilitado PagueloFacil sandbox', 'woocommerce'),
        'default' => 'no',
        'description' => sprintf(__('PagueloFacil sandbox can be used to test payments.', 'woocommerce')),
      ),
      'description' => array(
        'title' => __('Description', 'wc_paguelofacil'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'wc_paguelofacil'),
        'default' => __('', 'wc_paguelofacil'),
      ),
      'onsite' => array(
        'title' => __('Onsite PagueloFacil', 'wc_paguelofacil'),
        'label' => __('Habilitado Onsite PagueloFacil', 'woocommerce'),
        'type' => 'checkbox',
        'description' => 'This controls enable mode Onsite. Default Mode: Offsite', 'wc_paguelofacil',
        'default' => 'no'
      ),
    );
  }

	/**
   * There are no payment fields for PagueloFacil, but we want to show the description if set.
   * */
  function payment_fields() {
    //MUESTRA EN CAMPO DESCRIPCION EN EL METODO DE PAGO
    if ($this->description)
      echo wpautop(wptexturize($this->description));

    if ( 'yes' == $this->onsite) {
      $cc_form = new WC_Payment_Gateway_CC();
      $cc_form->id = $this->id;
      $cc_form->supports = $this->supports;
      $cc_form->form();
    }
  }

  /**
   * Admin Panel Options
   * CREA LAS PAGINA DE OPCIONES DEL ADMIN
   * */
  public function admin_options() {
    ?>
    <h3><?php _e('PagueloFacil Payment Gateway', 'wc_paguelofacil'); ?></h3>

    <table class="form-table">
      <?php $this->generate_settings_html(); ?>
    </table><!--/.form-table-->
    <?php
  }

  /**
   * Check for Paguelo Facil IPN Response
   * CHEKEA LA RESPUESTA DEL SERVIDOR DE PAGUELOFACIL
   * */
  function check_paguelofacil_resquest() {
    global $woocommerce;
    
    $response = $_REQUEST;

    //INICIALIZA LAS VARIABLES QUE VIENE DEL SERVIDOR POSTDEMON Y LINKDEMON
    if (isset($response['Deal']))
      $Deal = $response['Deal'];
    if (isset($response['OperNumber']))
      $OperNumber = $response['OperNumber'];
    $OperNumber = 'N/A';
    if (isset($response['Status']))
      $Status = $response['Status'];
    if (isset($response['Monto']))
      $Monto = $response['Monto'];
    if (isset($response['Estado']))
      $Estado = $response['Estado'];
    if (isset($response['Fecha']))
      $Fecha = $response['Fecha'];
    if (isset($response['Hora']))
      $Hora = $response['Hora'];
    if (isset($response['TotalPagado']))
      $TotalPagado = $response['TotalPagado'];
    if (isset($response['tipoTarjeta']))
      $Tipo = $response['tipoTarjeta'];
    if (isset($response['Oper']))
      $Oper = $response['Oper'];
    if (isset($response['Usuario']))
      $Usuario = $response['Usuario'];
    if (isset($response['Email']))
      $Email = $response['Email'];
    if (isset($response['Razon']))
      $Razon = $response['Razon'];
    if (isset($response['CMTN']))
      $CMTN = $response['CMTN'];
    if (isset($response['CDSC']))
      $CDSC = $response['CDSC'];
    if (isset($response['Order'])) {$Order = $response['Order'];} else { $Order = $woocommerce->session->paguelofacil_order_id;}

    //OBTENEMOS LA ORDEN DE WOOCOMMERCE
    $order = new WC_Order($Order);

    //SI EL STATUS == 1 Y $ESTADO ES NULO EL PAGO FUE REALIZADO CON POSTDEMON
    if ($Status == '1' && $Estado == null) {
      if ($Status == '1' && $Monto > 0) {//pago OK POSTDEMON
        // Payment completed
        $order->add_order_note(__('PagueloFacil payment completed', 'woocommerce'));
        $order->payment_complete();
        $url = $this->get_return_url($order);
        wp_redirect($url, 303);

        exit();
      } else { //PAGO NO OK CON POSTDEMON
        if ($this->debug == 'yes')
            $this->log->add('paguelofacil', 'Payment not complete.');
        $url = get_permalink(wc_get_page_id('checkout'));
        wp_redirect($url);

        exit();
      }
    } else {
      if ($Estado <> '' && $TotalPagado > 0) {//pago OK LINKDEMON
        // Payment completed
        $order->add_order_note(__('PagueloFacil payment completed', 'woocommerce'));
        $order->payment_complete();
        $url = $this->get_return_url($order);
        wp_redirect($url, 303);

        exit();
      } else { //pago NO OK LINKDEMON
        if ($this->debug == 'yes')
            $this->log->add('paguelofacil', 'Payment not complete.');

        wc_add_notice( __('Payment error: ', 'woothemes') . $Estado, 'error' );
        $order->add_order_note(__('PagueloFacil payment incomplete', 'woocommerce'));
        $url = get_permalink(woocommerce_get_page_id('checkout'));
        wp_redirect($url);

        exit();
      }
    }
  }

  /**
   * Get LA url TEST O LIVE ONSITE Y OFFSITE
   * */
  function get_url_process() {
    if ('yes' == $this->testmode) {
      if ('yes' == $this->onsite) {
        $paguelofacil_adr = $this->testurl_onsite;
      } else {
        $paguelofacil_adr = $this->testurl . '?';
      }
    } else {
      if ('yes' == $this->onsite) {
        $paguelofacil_adr = $this->liveurl_onsite;
      } else {
        $paguelofacil_adr = $this->liveurl . '?';
      }
    }

    return $paguelofacil_adr;
  }

  /**
   * Get PagueloFacil Args for passing to PAGUELOFACIL
   * */
  function get_paguelofacil_args($order) {
    //$paguelofacil_req_args = array();
    $paguelofacil_args = array();

    if ('yes' == $this->onsite) {
      $paguelofacil_args = $this->get_paguelofacil_onsite_args($order);
    } else {
      $paguelofacil_args = $this->get_paguelofacil_offsite_args($order);
    }

    //return array_merge($paguelofacil_args, $paguelofacil_req_args);
    return $paguelofacil_args;
  }

  /**
   * Get PagueloFacil Args for passing to PAGUELOFACIL OFFSITE
   * */
  function get_paguelofacil_offsite_args($order) {
    global $woocommerce;

    $CCLW = $this->cclw;
    $CMTN = $order->get_total();
    $URLOK = $this->get_return_url($order);
    $URLKO = $order->get_cancel_order_url();

    //$this->get_transaction_url($order);
    $mensaje = $_SERVER['HTTP_ORIGIN'] . ' Orden Nro.' . $order->post->ID ;

    $paguelofacil_args = array(
      'CCLW' => $CCLW,
      'CMTN' => $CMTN,
      'CDSC' => $mensaje,
      'Order' => $order->post->ID,
      'RETURN_URL' => bin2hex(wc_get_page_permalink( 'shop' ).'/wc-api/woocommerce_paguelofacil/')
    );

    return $paguelofacil_args;
  }

  /**
   * Get TARJETA DE CREDITO VALIDA PARA PAGO ONSITE
   * */
  function validarTarjeta($num_tarjeta) {
    $num_tarjeta = preg_replace("/\D|\s/", "", $num_tarjeta);
    $length = strlen($num_tarjeta);
    $parity = $length % 2;
    $sum = 0;

    for ($i = 0; $i < $length; $i++) {
      $digit = $num_tarjeta [$i];

      if ($i % 2 == $parity)
          $digit = $digit * 2;

      if ($digit > 9)
          $digit = $digit - 9;

      $sum = $sum + $digit;
    }

    return ($sum % 10 == 0);
  }

  /**
   * Get PagueloFacil TIPO DE TARJETA (VISA O MASTER) PARA ONSITE
   * */
  function getTipoTarjeta($cc) {
    $cards = array(
      "visa" => "(4\d{12}(?:\d{3})?)",
      "amex" => "(3 [47] \d{13})",
      "jcb" => "(35 [2-8] [89] \d\d\d{10})",
      "maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
      "solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
      "mastercard" => "(5 [1-5] \d{14})",
      "switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
    );

    $names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch");
    $matches = array();
    $pattern = "#^(?:" . implode("|", $cards) . ")$#";
    $result = preg_match($pattern, str_replace(" ", "", $cc), $matches);

    if ($result > 0) {
      $result = ($this->validarTarjeta($cc)) ? 1 : 0;
    }

    return ($result > 0) ? $names [sizeof($matches) - 2] : false;
  }

  function getIP(){
    $ip = null;
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
  }

	/**
	 * Process the payment
	 *
	 * @since 1.0.0
	 * @since 4.1.0 Add 4th parameter to track previous error.
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 * @param bool $force_save_source Force save the payment source.
	 * @param mix  $previous_error Any error message from previous request.
	 * @param bool $use_order_source Whether to use the source, which should already be attached to the order.
	 *
	 * @throws Exception If payment will not be accepted.
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {
    global $woocommerce;

    $order = wc_get_order($order_id);
    $woocommerce->session->paguelofacil_order_id = $order_id;
    $paguelofacil_adr = $this->get_url_process();
    $woocommerce->session->paguelofacil_args = $_REQUEST;

    if ('yes' == $this->onsite) {
      $request = $woocommerce->session->paguelofacil_args;
      $credi_card = str_replace(" ", "", $request['paguelofacil-card-number']);
      $credi_card_expiry = $request['paguelofacil-card-expiry'];
      $credi_card_cvc = str_replace(" ", "", $request['paguelofacil-card-cvc']);
      $credi_card_expiry = explode('/', $credi_card_expiry);
      $credi_card_year = str_replace(" ", "", $credi_card_expiry[1]);

      if (strlen($credi_card_year) > 2) {
        $credi_card_year = substr($credi_card_year,-2);
      }

      $credi_card_month = str_replace(" ", "", $credi_card_expiry[0]);
      $credit_card_TipoTarjeta = ($this->getTipoTarjeta($credi_card) == 'Visa') ? 'VISA' : 'MC';
      $CCLW = $this->cclw;
      $CMTN = $order->get_total();
      $Direccion = $request['billing_address_1'];
      $Nombres = $request['billing_first_name'];
      $Apellidos = $request['billing_last_name'];
      $Email = $request['billing_email'];
      $Telefono = $request['billing_phone'];
      $url = $this->get_url_process();
      $secred = $credi_card.$credi_card_cvc.$Email;
      $data = array(
        "CCLW" =>  $CCLW ,
        "txType" => 'SALE',
        "CMTN" => $CMTN,
        "CDSC" => 'Orden Nro. '. $order_id,
        "CCNum" => $credi_card,
        "ExpMonth" => $credi_card_month,
        "ExpYear" => $credi_card_year,
        "CVV2" => $credi_card_cvc,
        "Name" => $Nombres,
        "LastName" => $Apellidos,
        "Email" => $Email,
        "Address" => $Direccion,
        "Tel" => $Telefono,
        "SecretHash" => hash('sha512', $secred),
        "Ip" => $this->getIP()
      );
      $postR="";

      foreach($data as $mk=>$mv) {
        $postR .= "&".$mk."=".$mv;
      }

      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
      curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded','Accept: */*'));
      curl_setopt($ch,CURLOPT_POSTFIELDS,$postR);
      $result = curl_exec($ch);

      if ($result === FALSE) {
        if ($this->debug == 'yes')
          $this->log->add('paguelofacil', 'Datos invalidos declided.');

        wc_add_notice( __('Payment error:', 'woothemes') . 'Datos Invalidos' , 'error' );

        return ;
      } else {
        $result = json_decode($result, true);
        curl_close($ch);

        if($result['Status'] == 'Approved') {
          // Payment completed
          $order->add_order_note( sprintf( __( 'PagueloFacil payment completed, Transaction ID: %1$s', 'woocommerce' ), $result['CODOPER'] ) );
          update_post_meta( $order->get_id(), '_paguelofacil_status', $result['Status'] );
          update_post_meta( $order->get_id(), '_transaction_id', $result['CODOPER'] );
          $order->add_order_note(__('PagueloFacil payment completed', 'woocommerce'));
          $order->payment_complete();

          // Return thankyou redirect
          return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
          );
        } else {
          $msj_error =  ' '.$result['RespText'];
          $msj_error2 =  ' -'.$result['error'];

          if ($this->debug == 'yes')
            $this->log->add('paguelofacil', 'Payment not complete.');

          wc_add_notice( __('Decline :', 'woothemes') . $msj_error.$msj_error2, 'error' );

          return;
        }
      }
    } else {
      $paguelofacil_args = $this->get_paguelofacil_offsite_args($order);
      $paguelofacil_args = http_build_query($paguelofacil_args, '', '&');

      return array(
        'result' => 'success',
        'redirect' => $paguelofacil_adr . $paguelofacil_args
      );
    }
  }
  
  /**
   * Get get_client
   * */
  function get_client() {
    if (!isset($this->ws_client)) {
      require_once(dirname(__FILE__) . '/ws_client.php');
      $this->ws_client = new WS_Client($this->settings);
    }

    return $this->ws_client;
  }

	/**
	 * Retries the payment process once an error occured.
	 *
	 * @since 4.2.0
	 * @param object   $response          The response from the Stripe API.
	 * @param WC_Order $order             An order that is being paid for.
	 * @param bool     $retry             A flag that indicates whether another retry should be attempted.
	 * @param bool     $force_save_source Force save the payment source.
	 * @param mixed    $previous_error    Any error message from previous request.
	 * @param bool     $use_order_source  Whether to use the source, which should already be attached to the order.
	 * @throws WC_Stripe_Exception        If the payment is not accepted.
	 * @return array|void
	 */
	public function retry_after_error( $response, $order, $retry, $force_save_source, $previous_error, $use_order_source ) {
		if ( ! $retry ) {
			$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'woocommerce-gateway-stripe' );
			$order->add_order_note( $localized_message );
			throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.
		}

		// Don't do anymore retries after this.
		if ( 5 <= $this->retry_interval ) {
			return $this->process_payment( $order->get_id(), false, $force_save_source, $response->error, $previous_error );
		}

		sleep( $this->retry_interval );
		$this->retry_interval++;

		return $this->process_payment( $order->get_id(), true, $force_save_source, $response->error, $previous_error, $use_order_source );
	}
}
