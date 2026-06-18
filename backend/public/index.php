<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\AnalyticsRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\MarketingRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ManualPaymentReviewRepository;
use App\Repositories\PaymentEventRepository;
use App\Repositories\PaymentRefundRepository;
use App\Repositories\PaymentTransactionRepository;
use App\Response;
use App\Payments\PaymentProviderRegistry;
use App\Services\AnalyticsService;
use App\Services\CatalogService;
use App\Services\CheckoutService;
use App\Services\DashboardService;
use App\Services\InventoryService;
use App\Services\MarketingService;
use App\Services\OrderService;
use App\Services\PaymentService;

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    if ($method === 'GET' && $path === '/health') {
        Response::json(['status' => 'ok', 'service' => 'nestcms-api']);
    }

    $pdo = Database::connect();

    $catalogRepository = new CatalogRepository($pdo);
    $inventoryRepository = new InventoryRepository($pdo);
    $orderRepository = new OrderRepository($pdo);
    $marketingRepository = new MarketingRepository($pdo);
    $analyticsRepository = new AnalyticsRepository($pdo);

    $catalog = new CatalogService($catalogRepository);
    $inventory = new InventoryService($inventoryRepository);
    $orders = new OrderService($orderRepository);
    $marketing = new MarketingService($marketingRepository);
    $analytics = new AnalyticsService($analyticsRepository);

$paymentTransactionRepository = new PaymentTransactionRepository($pdo);
$paymentEventRepository = new PaymentEventRepository($pdo);
$paymentRefundRepository = new PaymentRefundRepository($pdo);
$paymentReviewRepository = new ManualPaymentReviewRepository($pdo);
$providerRegistry = new PaymentProviderRegistry((string) getenv('PAYMENT_PROVIDER') ?: 'mock');
$provider = $providerRegistry->forName((string) getenv('PAYMENT_PROVIDER') ?: 'mock');
$paymentService = new PaymentService(
    $paymentTransactionRepository,
    $paymentEventRepository,
    $paymentRefundRepository,
    $paymentReviewRepository,
    $orderRepository,
    $provider,
    $providerRegistry
);

$checkout = new CheckoutService($pdo, $catalogRepository, $inventoryRepository, $orderRepository, $paymentService);
$dashboard = new DashboardService(
        $catalogRepository,
        $inventoryRepository,
        $orderRepository,
        $marketingRepository,
        $analyticsRepository
    );

    if ($method === 'GET' && $path === '/api/dashboard') {
        Response::json($dashboard->summary());
    }

    if ($method === 'GET' && $path === '/api/products') {
        Response::json(['data' => $catalog->listProducts()]);
    }

    if ($method === 'POST' && $path === '/api/products') {
        Response::json(['data' => $catalog->createProduct(readJsonBody())], 201);
    }

    if ($method === 'GET' && $path === '/api/inventory/low-stock') {
        Response::json(['data' => $inventory->lowStock()]);
    }

    if ($method === 'POST' && $path === '/api/checkout') {
        Response::json(['data' => $checkout->createOrder(readJsonBody())], 201);
    }

    if ($method === 'POST' && preg_match('#^/api/payments/webhooks/([a-z0-9\\-]+)$#', $path, $matchWebhook)) {
        $rawBody = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawBody, true);
        if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }
        $providerName = (string) $matchWebhook[1];
        Response::json(['data' => $paymentService->handleWebhook(
            $providerName,
            is_array($payload) ? $payload : [],
            $rawBody,
            webhookSignatureFromServer($_SERVER)
        )]);
    }

    if ($method === 'POST' && preg_match('#^/api/orders/(\\d+)/refunds$#', $path, $matchRefund)) {
        $body = readJsonBody();
        $orderId = (int) $matchRefund[1];
        Response::json(['data' => $paymentService->refund(
            $orderId,
            (float) ($body['amount'] ?? 0),
            (string) ($body['reason'] ?? ''),
            (string) ($body['actor'] ?? 'operator')
        )]);
    }

    if ($method === 'POST' && preg_match('#^/api/orders/(\\d+)/payment-review$#', $path, $matchReview)) {
        $body = readJsonBody();
        Response::json(['data' => $paymentService->addManualReview(
            (int) $matchReview[1],
            (string) ($body['actor'] ?? 'operator'),
            (string) ($body['decision'] ?? ''),
            (string) ($body['notes'] ?? ''),
            (string) ($body['risk_level'] ?? 'medium')
        )]);
    }

    if ($method === 'GET' && $path === '/api/payments/pending-report') {
        $minutes = isset($_GET['minutes']) ? max(1, (int) $_GET['minutes']) : 60;
        Response::json(['data' => $paymentService->pendingReport($minutes)]);
    }

    if ($method === 'GET' && $path === '/api/orders') {
        Response::json(['data' => $orders->listOrders()]);
    }

    if ($method === 'PATCH' && preg_match('#^/api/orders/(\d+)/status$#', $path, $matches)) {
        Response::json(['data' => $orders->updateStatus((int) $matches[1], readJsonBody()['status'] ?? '')]);
    }

    if ($method === 'GET' && $path === '/api/marketing/abandoned-carts') {
        Response::json(['data' => $marketing->abandonedCarts()]);
    }

    if ($method === 'POST' && preg_match('#^/api/marketing/abandoned-carts/(\d+)/send$#', $path, $matches)) {
        Response::json(['data' => $marketing->sendRecovery((int) $matches[1])], 201);
    }

    if ($method === 'GET' && $path === '/api/analytics/revenue') {
        Response::json(['data' => $analytics->revenueReport()]);
    }

    Response::json(['error' => 'Route not found'], 404);
} catch (InvalidArgumentException $exception) {
    Response::json(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    $payload = ['error' => 'Internal server error'];

    if (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'local') {
        $payload['detail'] = $exception->getMessage();
    }

    Response::json($payload, 500);
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);

    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('Invalid JSON payload.');
    }

    return is_array($decoded) ? $decoded : [];
}

function webhookSignatureFromServer(array $server): string
{
    $candidateHeaders = [
        'HTTP_X_WEBHOOK_SIGNATURE',
        'HTTP_X_SIGNATURE',
        'HTTP_X_SIGNATURE_256',
        'HTTP_STRIPE_SIGNATURE',
        'HTTP_X_HUB_SIGNATURE_256',
    ];

    foreach ($candidateHeaders as $header) {
        if (isset($server[$header]) && is_string($server[$header]) && trim($server[$header]) !== '') {
            return trim($server[$header]);
        }
    }

    return '';
}
