<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsAuthService;
use App\Services\GoogleSheetsService;
use App\Services\IntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class GoogleSheetsOAuthController
{
    private ?string $clientId;

    private ?string $redirectUri;

    public function __construct(
        private GoogleSheetsAuthService $authService,
        private GoogleSheetsService $googleSheetsService,
        private IntegrationService $integrationService,
    ) {
        $this->clientId = config('services.google_sheets.client_id');
        $this->redirectUri = config('services.google_sheets.redirect_uri');
    }

    public function redirect(Request $request)
    {
        if (! filled($this->clientId) || ! filled($this->redirectUri)) {
            $message = 'Интеграция Google Таблицы не настроена на сервере. Обратитесь к администратору.';

            if ($request->boolean('popup')) {
                return response()->view('oauth.google-sheets-oauth-unconfigured', [
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

        $state = base64_encode(Crypt::encryptString($stateData));

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/spreadsheets.readonly',
                'openid',
                'email',
                'profile',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $stateParam = $request->input('state');

        if (! $stateParam) {
            return redirect()->route('login')->with('error', 'Отсутствует параметр состояния.');
        }

        $encryptedState = base64_decode((string) $stateParam);

        if (! $encryptedState) {
            return redirect()->route('login')->with('error', 'Некорректный параметр состояния.');
        }

        try {
            $stateData = json_decode(Crypt::decryptString($encryptedState), true);
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Не удалось расшифровать параметры состояния.');
        }

        $isPopup = ! empty($stateData['popup']);

        if ($request->filled('error')) {
            $message = 'Авторизация Google отменена или не удалась.';

            if ($isPopup) {
                return response()->view('oauth.google-sheets-oauth-unconfigured', [
                    'message' => $message,
                ], 400);
            }

            return redirect()
                ->route('system-settings.clients-and-projects.projects.manage')
                ->with('error', $message);
        }

        try {
            $tokens = $this->authService->exchangeCode((string) $request->input('code'));
        } catch (\Throwable $e) {
            $message = 'Не удалось завершить авторизацию Google. Попробуйте снова.';

            if ($isPopup) {
                return response()->view('oauth.google-sheets-oauth-unconfigured', [
                    'message' => $message,
                ], 400);
            }

            return redirect()
                ->route('system-settings.clients-and-projects.projects.manage')
                ->with('error', $message);
        }

        $accessToken = (string) ($tokens['access_token'] ?? '');
        $profile = $accessToken !== ''
            ? $this->authService->fetchUserProfile($accessToken)
            : null;

        if ($profile === null) {
            $message = 'Не удалось получить профиль Google.';

            if ($isPopup) {
                return response()->view('oauth.google-sheets-oauth-unconfigured', [
                    'message' => $message,
                ], 502);
            }

            return redirect()->route('login')->with('error', $message);
        }

        $settingsArray = array_merge([
            'oauth_token' => $tokens['access_token'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 0))->toDateTimeString(),
        ], $this->googleSheetsService->mapOAuthProfile($profile));

        $integration = $this->integrationService->getIntegrations()->firstWhere('code', 'google_sheets');

        $cacheDataId = $stateData['cache_data_id'] ?? null;

        if (filled($cacheDataId)) {
            Cache::put(
                'google_sheets_oauth_result_'.$cacheDataId,
                $settingsArray,
                now()->addMinutes(15)
            );
        }

        if ($isPopup) {
            return response()->view('oauth.google-sheets-popup-complete', [
                'settings' => $settingsArray,
                'integrationId' => $integration->id,
                'cacheDataId' => $cacheDataId,
            ]);
        }

        // Redirect-поток: токены только в Cache, в URL — короткий state (иначе nginx 502: header too big).
        $redirectState = [
            'project_id' => $stateData['project_id'] ?? null,
            'cache_data_id' => $cacheDataId,
            'open_integration' => 'google_sheets',
        ];

        $state = base64_encode(Crypt::encryptString(json_encode($redirectState)));

        return redirect()->route('system-settings.clients-and-projects.projects.manage', [
            'state' => $state,
            'projectId' => $stateData['project_id'] ?? null,
        ])->with('status', 'Вы успешно авторизовались через Google.');
    }
}
