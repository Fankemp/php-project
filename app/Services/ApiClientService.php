<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ApiClientService
{
    private PendingRequest $client;

    public function __construct(Token $token)
    {
        $credentials = $token->credentials;
        $baseUrl = $token->apiService->base_url ?? 'https://statistics-api.wildberries.ru';

        $authHeader = $token->tokenType->code === 'bearer' ? "Bearer {$credentials}" : $credentials;

        $this->client = Http::baseUrl($baseUrl)
            ->timeout(60)
            // Умный retry: 3 попытки, пауза 1 секунда. 
            // Срабатывает только при 429 (Rate Limit) или 5xx (Сервер упал)
            ->retry(3, 1000, function (\Throwable $exception) {
                if ($exception instanceof RequestException && $exception->response) {
                    $status = $exception->response->status();
                    return $status === 429 || $status >= 500;
                }
                return false;
            })
            ->withHeaders([
                'Authorization' => $authHeader,
                'Accept'        => 'application/json',
            ]);
    }

    /**
     * Обычный запрос (без пагинации)
     * * @throws RequestException
     */
    public function fetch(string $endpoint, array $queryParams = []): array
    {
        $response = $this->client->get($endpoint, $queryParams);

        // Laravel сам выбросит RequestException с красивым описанием ошибки, если код ответа не 2xx
        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * Запрос с пагинацией (скачивает все страницы до конца)
     * полагаемся на реактивный retry
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

            // Если все 3 попытки ретрая провалились — падаем и отдаем ошибку логировщику
            $response->throw();

            $json = $response->json() ?? [];
            
            // Универсальное извлечение данных
            $data = $json['data'] ?? (isset($json[0]) ? $json : []);

            if (empty($data)) {
                break;
            }

            $allData = array_merge($allData, $data);

            // Если пришло меньше лимита, значит это последняя страница
            if (count($data) < $limit) {
                break;
            }

            $page++;
        }

        return $allData;
    }
}