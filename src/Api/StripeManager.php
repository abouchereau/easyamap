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
}