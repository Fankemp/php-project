<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use App\Services\AuthStrategies\AuthStrategyInterface;
use App\Services\AuthStrategies\BearerStrategy;
use App\Services\AuthStrategies\ApiKeyStrategy;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class ApiClientService
{
    private PendingRequest $client;

    public function __construct(Token $token)
    {
        $credentials = $token->credentials;
        $baseUrl = $token->apiService->base_url ?? 'https://statistics-api.wildberries.ru';

        // 1. Выбираем стратегию в зависимости от типа токена из БД
        $strategy = $this->resolveAuthStrategy($token->tokenType->code);

        // 2. Получаем готовые заголовки авторизации
        $authHeaders = $strategy->getHeaders($credentials);

        // 3. Формируем итоговый массив заголовков
        $headers = array_merge([
            'Accept' => 'application/json'
        ], $authHeaders);

        // 4. Инициализируем клиент
        $this->client = Http::baseUrl($baseUrl)
            ->timeout(60)
            ->retry(3, 1000, function (\Throwable $exception) {
                if ($exception instanceof RequestException && $exception->response) {
                    $status = $exception->response->status();
                    return $status === 429 || $status >= 500;
                }
                return false;
            })
            ->withHeaders($headers);
    }

    /**
     * Фабричный метод: подбирает нужный класс стратегии
     */
    private function resolveAuthStrategy(string $typeCode): AuthStrategyInterface
    {
        return match (strtolower($typeCode)) {
            'bearer' => new BearerStrategy(),
            'api_key', 'standard' => new ApiKeyStrategy(),
            default => throw new InvalidArgumentException("Неизвестный тип авторизации: {$typeCode}"),
        };
    }

    /**
     * Обычный запрос (без пагинации)
     * * @throws RequestException
     */
    public function fetch(string $endpoint, array $queryParams = []): array
    {
        $response = $this->client->get($endpoint, $queryParams);
        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * Запрос с пагинацией (скачивает все страницы до конца)
     * * @throws RequestException
     */
    public function fetchAllPaginated(string $endpoint, array $params = [], int $limit = 500): array
    {
        $page = 1;
        $allData = [];

        while (true) {
            $response = $this->client->get($endpoint, array_merge([
                'limit' => $limit,
                'page'  => $page
            ], $params));

            $response->throw();

            $json = $response->json() ?? [];
            $data = $json['data'] ?? (isset($json[0]) ? $json : []);

            if (empty($data)) {
                break;
            }

            $allData = array_merge($allData, $data);

            if (count($data) < $limit) {
                break;
            }

            $page++;
        }

        return $allData;
    }
}