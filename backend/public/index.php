<?php

declare(strict_types=1);

use App\AuthException;
use App\ForbiddenException;
use App\Database;
use App\Repositories\AnalyticsRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\InviteeRepository;
use App\Repositories\MagicTokenRepository;
use App\Repositories\MarketingRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentEventRepository;
use App\Repositories\PaymentRefundRepository;
use App\Repositories\PaymentTransactionRepository;
use App\Repositories\ManualPaymentReviewRepository;
use App\Repositories\AuthSessionRepository;
use App\Repositories\AuthRateLimitRepository;
use App\Repositories\AuthAuditRepository;
use App\Response;
use App\Payments\PaymentProviderRegistry;
use App\Services\AuthService;
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

    $checkout = new CheckoutService(
        $pdo,
        $catalogRepository,
        $inventoryRepository,
        $orderRepository,
        $paymentService
    );

    $dashboard = new DashboardService(
        $catalogRepository,
        $inventoryRepository,
        $orderRepository,
        $marketingRepository,
        $analyticsRepository
    );

    $inviteeRepository = new InviteeRepository($pdo);
    $magicTokenRepository = new MagicTokenRepository($pdo);
    $authSessionRepository = new AuthSessionRepository($pdo);
    $authRateLimitRepository = new AuthRateLimitRepository($pdo);
    $authAuditRepository = new AuthAuditRepository($pdo);
    $authService = new AuthService(
        $inviteeRepository,
        $magicTokenRepository,
        $authSessionRepository,
        $authRateLimitRepository,
        $authAuditRepository
    );

    $requestIp = requestIp();
    $requestUserAgent = requestUserAgent();
    $requireAuth = static function (array $roles = []) use ($authService, $path, $method, $requestIp, $requestUserAgent): array {
        $accessToken = requestBearerToken();
        $session = $authService->getSessionByAccessToken($accessToken, $requestIp, $requestUserAgent, $path, true, $method);
        $authService->requireRole($session, $roles, $requestIp, $requestUserAgent, $path, $method);

        return $session;
    };

    if ($method === 'POST' && $path === '/api/auth/magic/request') {
        $body = readJsonBody();
        Response::json($authService->requestMagicLink(
            (string) ($body['email'] ?? ''),
            $requestIp,
            $requestUserAgent,
            $path
        ));
    }

    if ($method === 'GET' && $path === '/api/auth/magic/callback') {
        $token = (string) ($_GET['token'] ?? '');
        Response::json($authService->consumeMagicLink($token, $requestIp, $requestUserAgent, $path));
    }

    if ($method === 'POST' && $path === '/api/auth/refresh') {
        $body = readJsonBody();
        Response::json($authService->refresh((string) ($body['refresh_token'] ?? ''), $requestIp, $requestUserAgent, $path));
    }

    if ($method === 'POST' && $path === '/api/auth/logout') {
        $body = readJsonBody();
        $accessToken = requestBearerToken();
        $refreshToken = (string) ($body['refresh_token'] ?? '');
        Response::json(['status' => $authService->logout($accessToken, $refreshToken, $requestIp, $requestUserAgent, $path)]);
    }

    if ($method === 'GET' && $path === '/api/auth/me') {
        $accessToken = requestBearerToken();
        Response::json($authService->me($accessToken, $requestIp, $requestUserAgent, $path));
    }

    if ($method === 'GET' && $path === '/api/dashboard') {
        $requireAuth(['admin', 'operator', 'finance']);
        Response::json(['data' => $dashboard->summary()]);
    }

    if ($method === 'GET' && $path === '/api/products' && isset($_GET['public']) && (string) $_GET['public'] === '1') {
        Response::json(['data' => $catalog->listProducts()]);
    }

    if ($method === 'GET' && $path === '/api/products') {
        $requireAuth(['admin', 'operator', 'finance']);
        Response::json(['data' => $catalog->listProducts()]);
    }

    if ($method === 'POST' && $path === '/api/products') {
        $requireAuth(['admin']);
        Response::json(['data' => $catalog->createProduct(readJsonBody())], 201);
    }

    if ($method === 'GET' && $path === '/api/inventory/low-stock') {
        $requireAuth(['admin', 'operator', 'finance']);
        Response::json(['data' => $inventory->lowStock()]);
    }

    if ($method === 'POST' && $path === '/api/checkout') {
        Response::json(['data' => $checkout->createOrder(readJsonBody())], 201);
    }

    if ($method === 'GET' && $path === '/api/marketing/abandoned-carts') {
        $requireAuth(['admin', 'operator', 'finance']);
        Response::json(['data' => $marketing->abandonedCarts()]);
    }

    if ($method === 'POST' && preg_match('#^/api/marketing/abandoned-carts/(\\d+)/send$#', $path, $matches)) {
        $requireAuth(['admin']);
        Response::json(['data' => $marketing->sendRecovery((int) $matches[1])], 201);
    }

    if ($method === 'GET' && $path === '/api/payments/pending-report') {
        $requireAuth(['admin', 'finance']);
        $minutes = isset($_GET['minutes']) ? max(1, (int) $_GET['minutes']) : 60;
        Response::json(['data' => $paymentService->pendingReport($minutes)]);
    }

    if ($method === 'GET' && $path === '/api/orders') {
        $requireAuth(['admin', 'operator', 'finance']);
        Response::json(['data' => $orders->listOrders()]);
    }

    if ($method === 'PATCH' && preg_match('#^/api/orders/(\\d+)/status$#', $path, $matches)) {
        $requireAuth(['admin', 'operator']);
        Response::json(['data' => $orders->updateStatus((int) $matches[1], readJsonBody()['status'] ?? '')]);
    }

    if ($method === 'POST' && preg_match('#^/api/orders/(\\d+)/refunds$#', $path, $matchRefund)) {
        $requireAuth(['admin', 'finance']);
        $body = readJsonBody();
        Response::json(['data' => $paymentService->refund(
            (int) $matchRefund[1],
            (float) ($body['amount'] ?? 0),
            (string) ($body['reason'] ?? ''),
            (string) ($body['actor'] ?? 'operator')
        )]);
    }

    if ($method === 'POST' && preg_match('#^/api/orders/(\\d+)/payment-review$#', $path, $matchReview)) {
        $requireAuth(['admin', 'finance']);
        $body = readJsonBody();
        Response::json(['data' => $paymentService->addManualReview(
            (int) $matchReview[1],
            (string) ($body['actor'] ?? 'operator'),
            (string) ($body['decision'] ?? ''),
            (string) ($body['notes'] ?? ''),
            (string) ($body['risk_level'] ?? 'medium')
        )]);
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

    if ($method === 'GET' && $path === '/api/analytics/revenue') {
        $requireAuth(['admin', 'finance', 'operator']);
        Response::json(['data' => $analytics->revenueReport()]);
    }

    Response::json(['error' => 'Route not found'], 404);
} catch (ForbiddenException $exception) {
    Response::json(['error' => $exception->getMessage()], 403);
} catch (AuthException $exception) {
    Response::json(['error' => $exception->getMessage()], $exception->getStatusCode());
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

function requestIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim((string) $forwarded[0]);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return (string) $_SERVER['REMOTE_ADDR'];
    }

    return '127.0.0.1';
}

function requestUserAgent(): string
{
    return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
}

function requestBearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    if (is_string($header) && preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
        return trim((string) $matches[1]);
    }

    return '';
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
