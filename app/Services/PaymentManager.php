<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\PaypalGateway;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentGateways\CreditCardGateway;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Resolve the payment gateway based on the gateway name.
     *
     * @param string $gatewayName
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public function resolve(string $gatewayName): PaymentGatewayInterface
    {
        $gatewayName = strtolower($gatewayName);
        
        return match ($gatewayName) {
            'paypal' => new PaypalGateway(),
            'stripe' => new StripeGateway(),
            'creditcard' => new CreditCardGateway(),
            default => throw new InvalidArgumentException(
                "Payment gateway '{$gatewayName}' is not supported."
            ),
        };
    }

    /**
     * Get list of available payment gateways.
     *
     * @return array
     */
    public function getAvailableGateways(): array
    {
        return config('payments.gateways', ['paypal', 'stripe', 'creditcard']);
    }
}
