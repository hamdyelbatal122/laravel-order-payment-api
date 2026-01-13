<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;

class PaypalGateway implements PaymentGatewayInterface
{
    /**
     * Process a payment transaction via PayPal.
     *
     * @param float $amount
     * @param array $details
     * @return array
     */
    public function process(float $amount, array $details): array
    {
        // Simulated PayPal payment processing
        // In a real implementation, this would call PayPal API
        
        $transactionId = 'PAYPAL_' . uniqid();
        
        // Simulate success/failure (90% success rate for demo)
        $success = (rand(1, 10) <= 9);
        
        return [
            'transaction_id' => $transactionId,
            'status' => $success ? 'successful' : 'failed',
            'gateway' => 'paypal',
            'amount' => $amount,
            'message' => $success 
                ? 'Payment processed successfully via PayPal' 
                : 'Payment failed via PayPal',
        ];
    }
}
