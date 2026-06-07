<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ApiService 
{
    private $host;
    private $key;
    private $limit = 500;

    public function __construct()
    {
        $this->host = config('services.api.host');
        $this->key = config('services.api.key');
    }

    public function getEndpointData($endpoint, $params = [])
    {
        $page = 1;
        $allData = [];

        while (true) {
            usleep(250000);

            $response = Http::retry(3, 1000)->timeout(60)->get("{$this->host}/api/{$endpoint}", array_merge([
                'key' => $this->key,
                'limit' => $this->limit,
                'page' => $page
            ], $params));

            if ($response->failed()) {
                throw new Exception("Ошибка API на эндпоинте {$endpoint}. Код: " . $response->status());
            }

            $json = $response->json();
            $data = $json['data'] ?? [];

            if (empty($data)) {
                break;
            }

            $allData = array_merge($allData, $data);

            if (count($data) < $this->limit) {
                break;
            }

            $page++;
        }

        return $allData;
    }
}