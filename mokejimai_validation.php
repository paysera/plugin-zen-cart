<?php
require('includes/application_top.php');
require(DIR_WS_MODULES . 'payment/libwebtopay/WebToPay.php');


try {
    WebToPay::toggleSS2(true);

    $projectID   = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYSERA_PROJECT_ID'");
    $projectPass = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYSERA_PROJECT_PASS'");

    $response = WebToPay::checkResponse($_REQUEST, array(
        'projectid'     => $projectID->fields['configuration_value'],
        'sign_password' => $projectPass->fields['configuration_value'],
    ));

    if ($response['status'] == 1) {


        $orderID = $response['orderid'];
        $Order   = $db->Execute("SELECT currency, order_total FROM " . TABLE_ORDERS . " WHERE orders_id = '" . $orderID . "'");

        if ($response['amount'] < intval(number_format($Order->fields['order_total'], 2, '', ''))) {
            exit('Bad amount!');
        }

        if ($Order->fields['currency'] != $response['currency']) {
            exit('Bad currency!');
        }


        $db->Execute('UPDATE ' . TABLE_ORDERS . ' SET orders_status = 2 WHERE orders_id = ' . $orderID);
        $db->Execute('UPDATE ' . TABLE_ORDERS_STATUS_HISTORY . ' SET orders_status_id = 2 WHERE orders_status_history_id = ' . $orderID);
    }
    exit('OK');
} catch (Exception $e) {
    exit(get_class($e) . ': ' . $e->getMessage());
}

