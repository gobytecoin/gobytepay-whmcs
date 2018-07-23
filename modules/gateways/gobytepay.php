<?php
/*

    GoByte Pay

*/

use WHMCS\Database\Capsule;

if(!function_exists('gobytepay_tablename')) {

    function gobytepay_tablename() {
        return 'mod_gobytepay_gateway';
    }

    function gobytepay_curl($type, $call, $client_id, $client_secret, $params) {

        $base_link = 'https://portal.gobytepay.com/api/v1/'.$call;

        if (!function_exists('curl_init')){
            die('cURL not installed.');
        }

     // echo $base_link;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_link);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $client_id.":".$client_secret);
        // echo '?'.$client_id.'?';
        // echo $client_secret;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $type = strtoupper($type);

        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif ($type == 'GET') {
            // check
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($array = json_decode($output)) {
            // var_dump($array->url);
            return $array;
        } else {
            //var_dump($output);
            die('Cannot connect to GoByte Pay!');

        }
    }


    function gobytepay_MetaData()
    {
        return array(
            'DisplayName' => 'GoByte Pay',
            'APIVersion' => '1.1', // Use API Version 1.1
            'DisableLocalCredtCardInput' => true,
            'TokenisedStorage' => false,
        );
    }

    function gobytepay_config()
    {

        gobytepay_create_table();

        return array(
            // the friendly display name for a payment gateway should be
            // defined here for backwards compatibility
            'FriendlyName' => array(
                'Type' => 'System',
                'Value' => 'GoByte Pay',
            ),

            // a text field type allows for single line text input
            'clientId' => array(
                'FriendlyName' => 'Client ID',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter your client ID',
            ),
            // a password field type allows for masked text input
            'clientSecret' => array(
                'FriendlyName' => 'Client Secret',
                'Type' => 'password',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter secret key here',
            ),

            // a text field type allows for single line text input
            'defaultCurrency' => array(
                'FriendlyName' => 'Default Currency',
                'Type' => 'text',
                'Size' => '25',
                'Default' => 'GBX',
                'Description' => 'Enter your default cryptocurrency code here so if selected currency is a fiat, it will be converted accordingly.',
            ),
        );
    }

    function gobytepay_link($params)
    {

        // Gateway Configuration Parameters
        $client_id = $params['clientId'];
        $client_secret = $params['clientSecret'];
        $default_currency = $params['defaultCurrency'];
        // print_r($params);
        // die();

        // Invoice Parameters
        $invoiceId = $params['invoiceid'];
        $description = $params["description"];
        $amount = $params['amount'];
        $currencyCode = $params['currency'];

        // Client Parameters
        $firstname = $params['clientdetails']['firstname'];
        $lastname = $params['clientdetails']['lastname'];
        $email = $params['clientdetails']['email'];
        $address1 = $params['clientdetails']['address1'];
        $address2 = $params['clientdetails']['address2'];
        $city = $params['clientdetails']['city'];
        $state = $params['clientdetails']['state'];
        $postcode = $params['clientdetails']['postcode'];
        $country = $params['clientdetails']['country'];
        $phone = $params['clientdetails']['phonenumber'];

        // System Parameters
        $companyName = $params['companyname'];
        $systemUrl = $params['systemurl'];
        $returnUrl = $params['returnurl'];
        $langPayNow = $params['langpaynow'];
        $moduleDisplayName = $params['name'];
        $moduleName = $params['paymentmethod'];
        $whmcsVersion = $params['whmcsVersion'];

        $postfields = array();
        $postfields['username'] = $username;
        $postfields['invoice_id'] = $invoiceId;
        $postfields['description'] = $description;
        $postfields['amount'] = $amount;
        $postfields['currency'] = $currencyCode;
        $postfields['first_name'] = $firstname;
        $postfields['last_name'] = $lastname;
        $postfields['email'] = $email;
        $postfields['address1'] = $address1;
        $postfields['address2'] = $address2;
        $postfields['city'] = $city;
        $postfields['state'] = $state;
        $postfields['postcode'] = $postcode;
        $postfields['country'] = $country;
        $postfields['phone'] = $phone;
        $postfields['callback_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
        $postfields['return_url'] = $returnUrl;

        $check_existing = Capsule::table(gobytepay_tablename())->where('invoice_id', $invoiceId)->first();

        if ($check_existing) {
            // cancel existing
            //$cancel_old_bill = gobytepay_curl('DELETE', 'bill/'.$check_existing->bill_id, $client_id, $client_secret, []);

            //$delete_existing = Capsule::table(gobytepay_tablename())->where('invoice_id', $invoiceId)->delete();
        }

        $params = ['title' => 'Payment for Invoice #'.$invoiceId, 'currency' => $currencyCode, 'amount' => $amount, 'default_currency' => $default_currency, 'redirect_url' => $returnUrl, 'callback_url' => $systemUrl.'/modules/gateways/callback/gobytepay_callback.php'];


        // die($client_id);
        $output = gobytepay_curl('POST', 'bill', $client_id, $client_secret, $params);

        if (isset($output->url)) {
            $htmlOutput = '<form method="get" action="' . $output->url . '">';
            $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
            $htmlOutput .= '</form>';

            Capsule::table(gobytepay_tablename())->insert(['invoice_id' => $invoiceId, 'bill_id' => $output->uid]);

        } else {
            //print_r($output);
            $htmlOutput = 'GoByte Pay cannot be used at the moment. Reason: '. $output->error;
        }

        return $htmlOutput;
    }

    function gobytepay_create_table()
    {

        if (!Capsule::schema()->hasTable(gobytepay_tablename())) {
            Capsule::schema()->create(gobytepay_tablename(), function ($table) {
                $table->integer('invoice_id')->primary();
                $table->string('bill_id');
            });
        }
    }
}
