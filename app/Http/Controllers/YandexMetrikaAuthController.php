<?php

namespace App\Http\Controllers;

use App\Data\Integrations\IntegrationData;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Services\IntegrationService;
use App\Services\YandexMetrikaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class YandexMetrikaAuthController extends Controller
{
    private ?string $clientId;
    private ?string $redirectUri;

    public function __construct(
        private YandexMetrikaAuthService $authService,
        private IntegrationService $integrationService,
    ) {
        $this->clientId = config('services.yandex_metrika.client_id');
        $this->redirectUri = config('services.yandex_metrika.redirect_uri');
    }

    public function redirect(Request $request)
    {
        $stateData = json_encode([
            'project_id' => $request->input('project_id'),
            'cache_data_id' => $request->input('cache_data_id'),
        ]);

        $encryptedState = Crypt::encryptString($stateData);
        $state = base64_encode($encryptedState);

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ];

        return redirect()->away('https://oauth.yandex.ru/authorize?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        if (!$request->has('state')) {
            return redirect()->route('login')->with('error', 'Missing state parameter');
        }

        try {
            $decryptedState = Crypt::decryptString(base64_decode($request->state));
            $stateData = json_decode($decryptedState, true);
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Invalid state parameter');
        }

        // Получаем токены
        $tokenData = $this->authService->getAccessToken($request->code);

        // Получаем информацию о пользователе
        $userInfo = Http::withToken($tokenData['oauth_token'])
            ->get('https://login.yandex.ru/info')
            ->json();

        // Формируем настройки интеграции
        $settings = [
            'id' => $userInfo['id'],
            'oauth_token' => $tokenData['oauth_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            'counter_id' => $tokenData['counter_id'],
        ];

        // Сохраняем интеграцию
        $integration = $this->integrationService->getIntegrations()->firstWhere('code', 'yandex_metrika');

        $projectIntegration = new ProjectIntegrationData();
        $projectIntegration->integration = IntegrationData::from($integration);
        $projectIntegration->isEnabled = true;
        $projectIntegration->settings = YandexMetrikaIntegrationSettingsData::fromSettings(collect($settings))->toArray();

        // Возвращаемся на форму проекта
        $stateData['integrations'] = [$projectIntegration->toArray()];
        $encryptedState = Crypt::encryptString(json_encode($stateData));

        return redirect()->route('system-settings.clients-and-projects.projects.manage', [
            'project' => $stateData['project_id'],
            'state' => base64_encode($encryptedState)
        ])->with('success', 'Yandex Metrika connected!');
    }
}
