<?php

namespace Tests\Unit\Services\GoogleSheets;

use App\Services\GoogleSheets\GoogleSheetsServiceAccountCredentials;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSheetsServiceAccountCredentialsTest extends TestCase
{
    private ?string $tempKeyPath = null;

    protected function tearDown(): void
    {
        if ($this->tempKeyPath !== null && is_file($this->tempKeyPath)) {
            @unlink($this->tempKeyPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_reports_missing_key_as_not_configured(): void
    {
        Config::set('services.google_sheets.service_account_json', '/tmp/missing-google-sa.json');
        $credentials = new GoogleSheetsServiceAccountCredentials;

        $this->assertFalse($credentials->isConfigured());
        $this->assertNull($credentials->clientEmail());
    }

    #[Test]
    public function it_reads_client_email_from_json(): void
    {
        $this->writeKeyFile([
            'client_email' => 'robot@example.iam.gserviceaccount.com',
            'private_key' => $this->generatePrivateKeyPem(),
        ]);

        $credentials = new GoogleSheetsServiceAccountCredentials;

        $this->assertTrue($credentials->isConfigured());
        $this->assertSame('robot@example.iam.gserviceaccount.com', $credentials->clientEmail());
    }

    #[Test]
    public function it_exchanges_jwt_for_access_token(): void
    {
        $this->writeKeyFile([
            'client_email' => 'robot@example.iam.gserviceaccount.com',
            'private_key' => $this->generatePrivateKeyPem(),
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.test-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
        ]);

        $credentials = new GoogleSheetsServiceAccountCredentials;

        $this->assertSame('ya29.test-token', $credentials->accessToken());
        $this->assertSame('ya29.test-token', $credentials->accessToken());

        Http::assertSentCount(1);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeKeyFile(array $payload): void
    {
        $this->tempKeyPath = tempnam(sys_get_temp_dir(), 'gsa');
        file_put_contents($this->tempKeyPath, json_encode($payload, JSON_THROW_ON_ERROR));
        Config::set('services.google_sheets.service_account_json', $this->tempKeyPath);
    }

    private function generatePrivateKeyPem(): string
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($resource);

        $pem = '';
        openssl_pkey_export($resource, $pem);

        return $pem;
    }
}
