<?php

declare(strict_types=1);

namespace App\Payments\Adapters;

final class MercadoPagoPaymentProvider extends BaseStubPaymentProvider
{
    public function __construct()
    {
        parent::__construct(
            'mercado_pago',
            'mp',
            'MERCADO_PAGO_WEBHOOK_SECRET',
            'Mercado Pago',
            'Mercado Pago'
        );
    }
}
