<?php

declare(strict_types=1);

namespace App\Payments\Adapters;

final class PagarMePaymentProvider extends BaseStubPaymentProvider
{
    public function __construct()
    {
        parent::__construct(
            'pagar_me',
            'pg',
            'PAGARME_WEBHOOK_SECRET',
            'Pagar.me',
            'Pagar.me'
        );
    }
}
