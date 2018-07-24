<?php
/*

    GoByte Pay

*/

use WHMCS\Database\Capsule;

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Get variable for WHMCS configuration
global $CONFIG;

// Module name.
$gatewayModuleName = 'gobytepay';

// Fetch gateway configuration parameters.
$params = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$params['type']) {
    die("Module not activated");
}

// Die if callback not called
if (!(isset($_POST['id']) || isset($_GET['id']))) die('Callback not valid');

$bill_id = ((isset($_POST['id'])) ? $_POST['id'] : $_GET['id']);

$bill = Capsule::table(gobytepay_tablename())->where('bill_id', $bill_id)->first();

$client_id = $params['clientId'];
$client_secret = $params['clientSecret'];
$default_currency = $params['defaultCurrency'];

$output = gobytepay_curl('GET', 'bill/'.$bill_id, $client_id, $client_secret, $params);

gobytepay_check_payment_status($bill, $output, $client_id, $client_secret, $params);