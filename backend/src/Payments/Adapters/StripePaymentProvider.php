<?php

declare(strict_types=1);

namespace App\Payments\Adapters;

final class StripePaymentProvider extends BaseStubPaymentProvider
{
    public function __construct()
    {
        parent::__construct(
            'stripe',
            'st',
            'STRIPE_WEBHOOK_SECRET',
            'Stripe',
            'Stripe'
        );
    }
}
