<?php

declare(strict_types=1);

namespace App\Payments\Adapters;

final class FakePaymentProvider extends BaseStubPaymentProvider
{
    public function __construct()
    {
        parent::__construct(
            'mock',
            'mock',
            'PAYMENT_WEBHOOK_SECRET',
            'Simulado',
            'provedor mock'
        );
    }
}
