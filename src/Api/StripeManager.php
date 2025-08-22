<?php
namespace App\Api;
require __DIR__ . '/../../vendor/stripe/stripe-php/init.php';
//use \Stripe\StripeClient;

class StripeManager {

    private $client;

    public function __construct() {
        $this->client = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);                
    }        

    public function createCustomer($user) {
        $customer = $this->client->customers->create([
            'email' => $user->getEmail(),
            'description' => 'Client pour prélèvement SEPA',
            'name' => $user
        ]);
        return $customer->id;
    }

    public function getPaymentMethod( $paymentMethodId) {
        return $this->client->paymentMethods->retrieve($paymentMethodId);
    }

    public function getObfuscatedIban($paymentMethodId) {
        $pm = $this->getPaymentMethod($paymentMethodId);
        $str = $pm['sepa_debit']['bank_code'];
        $str .= " ".$pm['sepa_debit']['branch_code'];
        $str .= " *********".$pm['sepa_debit']['last4'];
        return $str;
    }


    public function createPaymentMethod($user, $iban) {
        $payment_method = $this->client->paymentMethods->create([
            'type' => 'sepa_debit',
            'sepa_debit' => [
                'iban' => $iban, // Remplace par un IBAN valide
            ],
            'billing_details' => [
                'name' => 'Prélèvement '.$user,
                'email' => $user->getEmail()
            ],
        ]);

         $this->client->paymentMethods->attach(
            $payment_method->id, 
            ['customer' => $user->getStripeCustomerId()] 
        );
        return $payment_method->id;
    }

    public function createAccount($farm, $token, $tel, $email) {
        $account = $this->client->accounts->create([
            'type' => 'custom',
            'account_token' => $token,
            'country' => 'FR',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_profile' => [		
                'product_description' => $farm->getProductType(),			
                'support_phone' => $tel,
                'support_email' => $email,
            ],
        ]);
        return $account->id;
    }
    
    public function getAccount($account_id) {
        return $this->client->accounts->retrieve($account_id, []);
    }

    public function createAccountLink($account_id, $refreshUrl, $returnUrl) {
        $accountLink = $this->client->accountLinks->create([
            'account' => $account_id,
            'refresh_url' => $refreshUrl, // URL de redirection en cas d'expiration du lien
            'return_url' => $returnUrl,  // URL de redirection après complétion du formulaire
            'type' => 'account_onboarding', // Type de lien, ici pour l'activation du compte
            /*'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],*/
        ]);
        return $accountLink;
    }

    public function createPayment($payment, $url, $setting) {                
        $paymentIntent = $this->client->paymentIntents->create([
            'customer' => $payment->getFkUser()->getStripeCustomerId(),
            'amount' => $payment->getAmountTaxStripePrelevement(), 
            'currency' => 'eur',
            'description' => 'Paiement pour le contrat '.$payment->getFkContract()->getLabel(),
            'confirm' => true, 
            //'payment_method_types' => ['card','sepa_debit'], 
            'payment_method' => $user->getStripePaymentMethodId(),//'card',//'sepa_debit',//'card',
            'transfer_data' => [
                'destination' => $payment->getFkFarm()->getStripeAccountId(), 
            ],
            'return_url' => $url,
            'metadata' => [
                "amap" => $setting->getName(),
                "user" =>  $user->getFirstname()." ".$user->getLastname(),
                "farm" => $payment->getFkFarm()->getLabel()
            ],
            "receipt_email"=> $user->getEmail(),
            'mandate_data' => [
                'customer_acceptance' => [
                    'type' => 'offline'         
                ],
            ],
        ]);
        return $paymentIntent;
    }

    /**
Stripe\PaymentIntent Object
(
    [id] => pi_3Pb23lJLwGy52MhE1HSdAqul
    [object] => payment_intent
    [amount] => 200
    [amount_capturable] => 0
    [amount_details] => Stripe\StripeObject Object
        (
            [tip] => Array
                (
                )

        )

    [amount_received] => 0
    [application] =>
    [application_fee_amount] =>
    [automatic_payment_methods] =>
    [canceled_at] =>
    [cancellation_reason] =>
    [capture_method] => automatic_async
    [client_secret] => pi_3Pb23lJLwGy52MhE1HSdAqul_secret_yRkgIq2AIqq4dKXoeXhinqhi6
    [confirmation_method] => automatic
    [created] => 1720623837
    [currency] => eur
    [customer] =>
    [description] =>
    [invoice] =>
    [last_payment_error] =>
    [latest_charge] =>
    [livemode] => 1
    [metadata] => Stripe\StripeObject Object
        (
        )

    [next_action] =>
    [on_behalf_of] =>
    [payment_method] =>
    [payment_method_configuration_details] =>
    [payment_method_options] => Stripe\StripeObject Object
        (
            [card] => Stripe\StripeObject Object
                (
                    [installments] =>
                    [mandate_options] =>
                    [network] =>
                    [request_three_d_secure] => automatic
                )

        )

    [payment_method_types] => Array
        (
            [0] => card
        )

    [processing] =>
    [receipt_email] =>
    [review] =>
    [setup_future_usage] =>
    [shipping] =>
    [source] =>
    [statement_descriptor] =>
    [statement_descriptor_suffix] =>
    [status] => requires_payment_method
    [transfer_data] =>
    [transfer_group] =>
)
*/
}