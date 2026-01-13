<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $details): array
    {
        $transactionId = 'CREDITCARD_' . uniqid();
        
        $success = (rand(1, 10) <= 9);
        
        return [
            'transaction_id' => $transactionId,
            'status' => $success ? 'successful' : 'failed',
            'gateway' => 'creditcard',
            'amount' => $amount,
            'message' => $success 
                ? 'Payment processed successfully via Credit Card' 
                : 'Payment failed via Credit Card',
        ];
    }
}
