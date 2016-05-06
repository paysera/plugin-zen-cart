<?php
require_once('libwebtopay/WebToPay.php');

class mokejimai extends base {
    var $code,
        $title,
        $projectID,
        $projectPass,
        $description,
        $sort_order,
        $testMode,
        $enabled,
        $form_action_url,
        $orderID;

    function mokejimai() {
        global $order;

        $this->code            = 'mokejimai';
        $this->title           = MODULE_PAYMENT_PAYSERA_TEXT_TITLE;
        $this->projectID       = MODULE_PAYMENT_PAYSERA_PROJECT_ID;
        $this->projectPass     = MODULE_PAYMENT_PAYSERA_PROJECT_PASS;
        $this->description     = MODULE_PAYMENT_PAYSERA_TEXT_DESCRIPTION;
        $this->sort_order      = 0;
        $this->testMode        = ((MODULE_PAYMENT_PAYSERA_TEST == 'True') ? true : false);
        $this->enabled         = ((MODULE_PAYMENT_PAYSERA_STATUS == 'True') ? true : false);
        $this->form_action_url = WebToPay::PAY_URL;

        if ((int)MODULE_PAYMENT_PAYSERA_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAYSERA_ORDER_STATUS_ID;
        }
    }

    function javascript_validation() {
        return false;
    }

    function selection() {
        return array('id' => $this->code, 'module' => $this->title);
    }

    function pre_confirmation_check() {
        return false;
    }

    function confirmation() {
        
        return false;
    }

    function process_button() {

        global $order, $db;

        $last_order_id = $db->Execute("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1");
        $this->orderID = $last_order_id->fields['orders_id'] + 1;

        $acceptURL   = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=mokejimai', 'SSL');
        $cancelURL   = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
        $callbackURL = zen_href_link('mokejimai_validation.php', '', 'SSL', false, false, true);

        $acceptURL   = str_replace('&amp;', '&', $acceptURL);
        $cancelURL   = str_replace('&amp;', '&', $cancelURL);
        $callbackURL = str_replace('&amp;', '&', $callbackURL);


        try {
            $request = WebToPay::buildRequest(array(
                'projectid'     => $this->projectID,
                'sign_password' => $this->projectPass,

                'orderid'       => $this->orderID,
                'amount'        => intval(number_format($order->info['total'], 2, '', '')),
                'currency'      => $order->info['currency'],
                'lang'          => (substr($_SESSION['language'], 0, 2) !== 'lt') ? 'ENG' : 'LIT',

                'accepturl'     => $acceptURL,
                'cancelurl'     => $cancelURL,
                'callbackurl'   => $callbackURL,
                'payment'       => '',

                'logo'          => '',
                'p_firstname'   => $order->customer['firstname'],
                'p_lastname'    => $order->customer['lastname'],
                'p_email'       => $order->customer['email_address'],
                'p_street'      => $order->customer['street_address'],
                'p_city'        => $order->customer['city'],
                'p_state'       => $order->customer['state'],
                'p_zip'         => $order->customer['postcode'],
                'p_countrycode' => $order->customer['country']['iso_code_2'],
                'test'          => $this->testMode,
            ));
        } catch (WebToPayException $e) {
            echo get_class($e) . ': ' . $e->getMessage();
        }

        $html = '';

        if ($request) {
            foreach ($request as $key => $value) {
                $html .= zen_draw_hidden_field($key, $value);
            }
        }

        return $html;
    }

    function before_process() {
        return false;
    }

    function after_process() {
        return false;
    }

    function get_error() {
        return false;
    }

    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query  = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYSERA_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install() {
        global $db, $messageStack;
        if (defined('MODULE_PAYMENT_PAYSERA_STATUS')) {
            $messageStack->add_session('PAYSERA module already installed.', 'error');
            zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=paysera', 'NONSSL'));
            return 'failed';
        }
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable paysera module?', 'MODULE_PAYMENT_PAYSERA_STATUS', 'True', 'Do you wish to accept paysera payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Paysera project ID', 'MODULE_PAYMENT_PAYSERA_PROJECT_ID', '0', 'Your Paysera.com project ID', '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Paysera project password', 'MODULE_PAYMENT_PAYSERA_PROJECT_PASS', '0', 'Your Paysera.com project password', '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable test mode?', 'MODULE_PAYMENT_PAYSERA_TEST', 'True', 'Do you wish to enable test payment mode?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Unconfirmed Order Status', 'MODULE_PAYMENT_PAYSERA_ORDER_STATUS_ID', '0', 'Set the status of unconfirmed orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    }

    function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_PAYMENT_PAYSERA_STATUS', 'MODULE_PAYMENT_PAYSERA_PROJECT_ID', 'MODULE_PAYMENT_PAYSERA_PROJECT_PASS', 'MODULE_PAYMENT_PAYSERA_TEST', 'MODULE_PAYMENT_PAYSERA_ORDER_STATUS_ID');
    }
}

