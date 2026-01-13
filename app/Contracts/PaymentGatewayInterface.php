<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Process a payment transaction.
     *
     * @param float $amount
     * @param array $details
     * @return array
     */
    public function process(float $amount, array $details): array;
}
