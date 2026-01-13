<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    /**
     * Process a payment transaction via Stripe.
     *
     * @param float $amount
     * @param array $details
     * @return array
     */
    public function process(float $amount, array $details): array
    {
        // Simulated Stripe payment processing
        // In a real implementation, this would call Stripe API
        
        $transactionId = 'STRIPE_' . uniqid();
        
        // Simulate success/failure (90% success rate for demo)
        $success = (rand(1, 10) <= 9);
        
        return [
            'transaction_id' => $transactionId,
            'status' => $success ? 'successful' : 'failed',
            'gateway' => 'stripe',
            'amount' => $amount,
            'message' => $success 
                ? 'Payment processed successfully via Stripe' 
                : 'Payment failed via Stripe',
        ];
    }
}
