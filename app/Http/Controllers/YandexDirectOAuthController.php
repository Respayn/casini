<?php

namespace App\Http\Controllers;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Services\IntegrationService;
use App\Services\YandexDirectAuthService;
use App\Services\YandexDirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class YandexDirectOAuthController
{
    private ?string $clientId;
    private ?string $redirectUri;

    public function __construct(
        private YandexDirectAuthService $authService,
        private IntegrationService $integrationService,
        private YandexDirectService $yandexDirectService,
    ) {
        $this->clientId = config('services.yandex_direct.client_id');
        $this->redirectUri = config('services.yandex_direct.redirect_uri');
    }

    public function redirect(Request $request)
    {
        if (! filled($this->clientId) || ! filled($this->redirectUri)) {
            $message = 'Интеграция Яндекс.Директ не настроена на сервере. Обратитесь к администратору.';

            if ($request->boolean('popup')) {
                return response()->view('oauth.yandex-direct-oauth-unconfigured', [
                    'message' => $message,
                ], 503);
            }

            return redirect()
                ->route('system-settings.clients-and-projects.projects.manage')
                ->with('error', $message);
        }

        $stateData = json_encode([
            'project_id' => $request->input('project_id'),
            'cache_data_id' => $request->input('cache_data_id'),
            'popup' => $request->boolean('popup'),
        ]);
        $encryptedState = Crypt::encryptString($stateData);
        $state = base64_encode($encryptedState);

        $scopes = [
            'login:info',
            'login:avatar',
            'direct:api',
        ];

        $params = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'scope'         => implode(' ', $scopes),
            'force_confirm' => 'yes',
        ];

        $authUrl = 'https://oauth.yandex.ru/authorize?' . http_build_query($params);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $stateParam = $request->input('state');

        if (!$stateParam) {
            return redirect()->route('login')->with('error', 'Отсутствует параметр состояния.');
        }

        // Декодируем из base64
        $encryptedState = base64_decode($stateParam);

        if (!$encryptedState) {
            return redirect()->route('login')->with('error', 'Некорректный параметр состояния.');
        }

        // Расшифровываем данные
        try {
            $stateDataJson = Crypt::decryptString($encryptedState);
            $stateData = json_decode($stateDataJson, true);
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Не удалось расшифровать параметры состояния.');
        }

        $isPopup = ! empty($stateData['popup']);

        try {
            $tokens = $this->authService->exchangeCode($request->input('code'));
        } catch (\Throwable $e) {
            $message = 'Не удалось завершить авторизацию Яндекс.Директ. Попробуйте снова.';

            if (str_contains($e->getMessage(), 'invalid_grant')) {
                $message = 'Код авторизации уже использован или истёк. Закройте лишние вкладки OAuth и включите синхронизацию снова.';
            }

            if ($isPopup) {
                return response()->view('oauth.yandex-direct-oauth-unconfigured', [
                    'message' => $message,
                ], 400);
            }

            return redirect()
                ->route('system-settings.clients-and-projects.projects.manage')
                ->with('error', $message);
        }

        $accessToken = $tokens['access_token'];

        $oauthProfile = $this->yandexDirectService->fetchOauthUserProfile($accessToken);

        if ($oauthProfile === null) {
            $message = 'Не удалось получить информацию о пользователе от Яндекса.';

            if ($isPopup) {
                return response()->view('oauth.yandex-direct-oauth-unconfigured', [
                    'message' => $message,
                ], 502);
            }

            return redirect()->route('login')->with('error', $message);
        }

        $settingsArray = array_merge([
            'oauth_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 0))->toDateTimeString(),
        ], $oauthProfile);

        $integration = $this->integrationService->getIntegrations()->firstWhere('code', 'yandex_direct');

        if ($isPopup) {
            $cacheDataId = $stateData['cache_data_id'] ?? null;

            if (filled($cacheDataId)) {
                Cache::put(
                    'yandex_direct_oauth_result_'.$cacheDataId,
                    $settingsArray,
                    now()->addMinutes(15)
                );
            }

            return response()->view('oauth.yandex-direct-popup-complete', [
                'settings' => $settingsArray,
                'integrationId' => $integration->id,
                'cacheDataId' => $cacheDataId,
            ]);
        }

        // Fallback: полный redirect (legacy-поток без popup).
        // client_login не подставляем — OAuth-логин ≠ Client-Login Директа; выбор вручную в модалке.
        $selectedIntegration = new ProjectIntegrationData();
        $selectedIntegration->integration = IntegrationData::from($integration);
        $selectedIntegration->isEnabled = true;
        $selectedIntegration->settings = $settingsArray;

        $stateData['integrations'] = [$selectedIntegration->toArray()];
        $stateData['open_integration'] = 'yandex_direct';

        $state = base64_encode(Crypt::encryptString(json_encode($stateData)));

        return redirect()->route('system-settings.clients-and-projects.projects.manage', ['state' => $state, 'projectId' => $stateData['project_id'] ?? null])
            ->with('status', 'Вы успешно авторизовались через Яндекс и подключили Яндекс.Директ!');
    }
}
