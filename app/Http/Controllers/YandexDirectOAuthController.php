<?php

namespace App\Http\Controllers;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Factories\IntegrationSettingsFactory;
use App\Services\IntegrationService;
use App\Services\YandexDirectAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class YandexDirectOAuthController
{
    private ?string $clientId;
    private ?string $redirectUri;

    public function __construct(
        private YandexDirectAuthService $authService,
        private IntegrationService $integrationService,
    ) {
        $this->clientId = config('services.yandex_direct.client_id');
        $this->redirectUri = config('services.yandex_direct.redirect_uri');
    }

    public function redirect(Request $request)
    {
        $projectId = $request->input('project_id');
        $stateData = json_encode([
            'project_id' => (int)$projectId,
            'user_id'    => auth()->id(),
        ]);
        $encryptedState = Crypt::encryptString($stateData);
        $state = base64_encode($encryptedState);

        $scopes = ['login:email', 'direct:api'];

        $params = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'scope'         => implode(' ', $scopes),
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

        $tokens = $this->authService->exchangeCode($request->input('code'));

        $accessToken = $tokens['access_token'];

        // Получаем информацию о пользователе из Яндекса
        $userInfoResponse = Http::withHeaders([
            'Authorization' => 'OAuth ' . $accessToken,
        ])->get('https://login.yandex.ru/info?format=json&with_openid_identity=1');

        if ($userInfoResponse->failed()) {
            return redirect()->route('login')->with('error', 'Не удалось получить информацию о пользователе от Яндекса.');
        }

        $userInfo = $userInfoResponse->json();

        $settingsArray = [
            'client_login' => $userInfo['login'],
            'account_id' => $userInfo['client_id'],
            'oauth_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in'])->toDateTimeString(),
        ];

        $integration = $this->integrationService->getIntegrations()->firstWhere('code', 'yandex_direct');
        $selectedIntegration = new ProjectIntegrationData();
        $selectedIntegration->integration = IntegrationData::from($integration);
        $selectedIntegration->isEnabled = false;
        $selectedIntegration->settings = IntegrationSettingsFactory::createFromSettings('yandex_direct', collect($settingsArray))->toArray();

        app(IntegrationService::class)->saveIntegrationSettings(
            $stateData['project_id'],
            $selectedIntegration
        );

        return redirect()->route('system-settings.clients-and-projects.projects.manage', $stateData['project_id'])
            ->with('status', 'Вы успешно авторизовались через Яндекс и подключили Яндекс.Директ!');
    }
}
