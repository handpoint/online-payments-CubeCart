<?php
	class Gateway {

        /**
         * Gateway Hosted API Endpoint
         */
        const API_ENDPOINT_HOSTED = 'https://gateway.example.com/hosted/';

		private $_module;
		private $_basket;

		public function __construct($module = false, $basket = false) {
			$this->_module	= $module;
			$this->_basket =& $GLOBALS['cart']->basket;
		}

		##################################################

		public function transfer() {
            $submitMethod = 'auto';
            $target = '_self';
            $action = (filter_var($this->_module['payment_page_url'], FILTER_VALIDATE_URL)) ? $this->_module['payment_page_url'] : self::API_ENDPOINT_HOSTED;

            if ($this->_module['integration_type'] === 'hosted_v2') {
                $submitMethod = 'manual';
                $target = 'paymentgatewayframe';
            }

            if ($this->_module['integration_type'] === 'hosted_v3') {
                $action .= 'modal/';
            }

            return array(
                'action'	=> $action,
                'method'	=> 'post',
                'target'	=> $target,
                'submit'	=> $submitMethod,
            );
		}

        public function fixedVariables() {
            $GLOBALS['smarty']->assign("DISPLAY_3DS", true);

            $req = $this->prepareReqParams();

            $redirect = $GLOBALS['storeURL'] . '/index.php?_g=rm&type=gateway&cmd=process&module=PaymentNetwork';
            $callback = $GLOBALS['storeURL'] . '/index.php?_g=rm&type=gateway&cmd=process&module=PaymentNetwork&callback=true';

            $req = array_merge($req, [
                'redirectURL' => $redirect,
                'callbackURL' => $callback,
            ]);

            if (isset($this->_module['merchant_passphrase'])) {
                $req['signature'] = self::createSignature($req, $this->_module['merchant_passphrase']);
			}

			unset($req['token']);

            return $req;
		}

        /**
         * @return array
         */
        protected function prepareReqParams(): array
        {
            $address = '';

            if (isset($this->_basket['billing_address']['line1'])) {
                $address .= $this->_basket['billing_address']['line1'];
            }

            if (!empty($this->_basket['billing_address']['line2'])) {
                $address .= " " . $this->_basket['billing_address']['line2'];
            }

            if (!empty($this->_basket['billing_address']['town'])) {
                $address .= " " . $this->_basket['billing_address']['town'];
            }

            // Fields for hash
            return array(
                'action' => 'SALE',
                'amount' => (int)(round($this->_basket['total'], 2) * 100),
                'countryCode' => 826,
                'currencyCode' => 826,
                'customerAddress' => $address,
                'customerEmail' => $this->_basket['billing_address']['email'],
                'customerName' => $this->_basket['billing_address']['first_name'] . ' ' . $this->_basket['billing_address']['last_name'],
                'customerPhone' => $this->_basket['billing_address']['phone'],
                'customerPostCode' => $this->_basket['billing_address']['postcode'],
                'merchantID' => $this->_module['merchant_id'],
                'orderRef' => $this->_basket['cart_order_id'],
                'transactionUnique' => md5($this->_basket['cart_order_id'] . time()),
                'token' => $_SESSION['__system']['token'],
                'threeDSVersion' => 2,
            );
        }

        ##################################################

		public function call() {
			return false;
		}

        public function process() {
			$order				= Order::getInstance();
			$cart_order_id		= $_POST['orderRef'];
			$order_summary		= $order->getSummary($cart_order_id);

			if((int)$order_summary['status'] != (int)Order::ORDER_PROCESS) {


				if ( isset( $_POST['signature'] ) ) {
					$check = $_POST;
					unset( $check['signature'] );
					ksort( $check );
					$build_query = http_build_query( $check, '', '&' );
					$build_query = preg_replace( '/%0D%0A|%0A%0D|%0A|%0D/i', '%0A', $build_query );
					$sig_check   = ( $_POST['signature'] == hash( "SHA512", $build_query . $this->_module['merchant_passphrase'] ) );
				} else {
					$sig_check = true;
				}

				if ( $_POST['responseCode'] == '0' && $sig_check ) {
					$order->orderStatus( Order::ORDER_PROCESS, $cart_order_id );
					$order->paymentStatus( Order::PAYMENT_SUCCESS, $cart_order_id );
				}elseif($_POST['responseCode'] == '5'){
					$order->orderStatus(Order::ORDER_DECLINED, $cart_order_id);
					$order->paymentStatus( Order::PAYMENT_DECLINE, $cart_order_id );
				}elseif($_POST['responseCode'] != '0'){
					$order->orderStatus(Order::ORDER_DECLINED, $cart_order_id);
					$order->paymentStatus( Order::PAYMENT_FAILED, $cart_order_id );
				}

				$transData['notes']       = $sig_check == true ? 'response signature check verified' : 'response signature check failed';
				$transData['gateway']     = 'PaymentNetwork';
				$transData['order_id']    = $_POST['orderRef'];
				$transData['trans_id']    = $_POST['xref'];
				$transData['amount']      = ( $_POST['amountReceived'] > 0 ) ? ( $_POST['amountReceived'] / 100 ) : '';
				$transData['status']      = $_POST['responseMessage'];
				$transData['customer_id'] = $order_summary['customer_id'];
				$transData['extra']       = '';
				$order->logTransaction( $transData );
			}

			if(!isset($_GET['callback'])){
				// ensure the module path is not in the url, had a bug with order emails having weird links
				$url = explode('/modules/gateway/PaymentNetwork',$GLOBALS['storeURL']);

                $redirectUrl = $url[0] . '/index.php?_a=complete';

                if ($this->_module['integration_type'] === 'hosted_v2') {
                    echo sprintf("<script>window.top.location = \"%s\";</script>", $redirectUrl);
                } else {
                    httpredir($redirectUrl);
                }
            } else {
				$transData['notes']       =  'callback processed' ;
				$transData['gateway']     = 'PaymentNetwork';
				$transData['order_id']    = $_POST['orderRef'];
				$transData['trans_id']    = $_POST['xref'];
				$transData['amount']      = ( $_POST['amountReceived'] > 0 ) ? ( $_POST['amountReceived'] / 100 ) : '';
				$transData['status']      = $_POST['responseMessage'];
				$transData['customer_id'] = $order_summary['customer_id'];
				$transData['extra']       = '';
				$order->logTransaction( $transData );
			}

			return false;
		}

        public function form() {
		    if ($this->_module['integration_type'] === 'hosted_v2') {
                $str = <<<HTML
<iframe id="gateway-frame" name="paymentgatewayframe" frameBorder="0" seamless="seamless" style="width:699px; height:1100px;margin: 0 auto;display:block;"></iframe>

<script>
	// detects if jquery is loaded and adjusts the form for mobile devices
	document.body.addEventListener('load', function() {
		if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
			const frame = document.querySelector('#gateway-frame');
			frame.style.height = '1280px';
			frame.style.width = '50%';
		}
	});

    window.setTimeout(function () {
        document.forms['gateway-transfer'].submit();
    }, 0);
</script>
HTML;

            }

            return isset($str) ? $str : false;
		}

        /**
         * Sign requests with a SHA512 hash
         * @param array $data Request data
         *
         * @param $key
         * @return string|null
         */
        protected static function createSignature(array $data, $key) {
            if (!$key || !is_string($key) || $key === '' || !$data || !is_array($data)) {
                return null;
            }

            ksort($data);

            // Create the URL encoded signature string
            $ret = http_build_query($data, '', '&');

            // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
            $ret = preg_replace('/%0D%0A|%0A%0D|%0A|%0D/i', '%0A', $ret);
            // Hash the signature string and the key together
            return hash('SHA512', $ret . $key);
        }
    }
