<?php

namespace App\Modules\S2P\Http\Clients;

use App\Modules\S2P\Dto\Http\GetUsage;
use App\Modules\S2P\Dto\Http\GetUsageResponse;
use App\Modules\S2P\Dto\Usage;
use App\Modules\S2P\Enums\Endpoint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class S2P
{
    private readonly string $clientId;
    private readonly string $clientSecret;
    private readonly string $grantType;
    private readonly string $url;
    private ?string $accessToken;

    public function __construct()
    {
        $this->clientId = config('s2p.client_id');
        $this->clientSecret = config('s2p.client_secret');
        $this->grantType = config('s2p.grant_type');
        $this->url = config('s2p.http_schema') . '://' . config('s2p.url');

        $this->proccessAuth();
    }

    private function proccessAuth(): void
    {
        $cacheKey = "s2p.access_token.{$this->clientId}";

        try {
            $this->accessToken = Cache::get($cacheKey);

            if ($this->accessToken) {
                return;
            }

            $response = Http::asForm()->post($this->url . Endpoint::GET_ACCESS_TOKEN->value, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => $this->grantType,
            ]);

            $this->accessToken = $response->object()->access_token;

            Cache::put($cacheKey, $this->accessToken, now()->addSeconds(200));
        } catch (Throwable $exception) {
            Log::error('S2P: ошибка получения accessToken', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ]);

            throw $exception;
        }
    }

    public function getUsage(GetUsage $getUsage): ?GetUsageResponse
    {
        $data = [
            ...[
                'date_from' => $getUsage->dateFrom,
                'date_to' => $getUsage->dateTo,
            ],
            ...(! empty($getUsage->clientIid) ? ['client_id' => $getUsage->clientIid] : []),
        ];

        try {
            $response = Http::asJson()->withToken($this->accessToken)->post($this->url . Endpoint::GET_USAGE->value, $data);

            $servicesCollection = collect($response->object()->services);

            $usages = $servicesCollection->map(fn ($service) => Usage::fromArray((array) $service))->toArray();

            return new GetUsageResponse(services: $usages);
        } catch (Throwable $exception) {
            Log::error('S2P: ошибка получения услуг', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ]);

            throw $exception;
        }
    }
}
