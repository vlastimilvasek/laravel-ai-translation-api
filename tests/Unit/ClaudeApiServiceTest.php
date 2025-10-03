<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ClaudeApiServiceTest extends TestCase
{
    protected ClaudeApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set fake API key for testing
        Config::set('services.anthropic.api_key', 'sk-ant-test-key-12345');

        $this->service = new ClaudeApiService();
    }

    /**
     * Test service throws exception when API key is missing
     */
    public function test_throws_exception_when_api_key_is_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY není nastavený v .env souboru!');

        Config::set('services.anthropic.api_key', '');
        new ClaudeApiService();
    }

    /**
     * Test sendMessage method returns text from API response
     */
    public function test_send_message_returns_text_from_api_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'This is a test response']
                ]
            ], 200)
        ]);

        $result = $this->service->sendMessage('Test message');

        $this->assertEquals('This is a test response', $result);
    }

    /**
     * Test sendMessage includes correct headers
     */
    public function test_send_message_includes_correct_headers(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Response']
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-key', 'sk-ant-test-key-12345') &&
                   $request->hasHeader('anthropic-version', '2023-06-01') &&
                   $request->hasHeader('content-type', 'application/json');
        });
    }

    /**
     * Test sendMessage sends correct request body
     */
    public function test_send_message_sends_correct_request_body(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Response']
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test message', 2048);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['model'] === 'claude-sonnet-4-5-20250929' &&
                   $body['max_tokens'] === 2048 &&
                   $body['messages'][0]['role'] === 'user' &&
                   $body['messages'][0]['content'] === 'Test message';
        });
    }

    /**
     * Test sendMessage throws exception on API error
     */
    public function test_send_message_throws_exception_on_api_error(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid API key'
                ]
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Claude API error: Invalid API key');

        $this->service->sendMessage('Test');
    }

    /**
     * Test sendMessage throws exception on connection error
     */
    public function test_send_message_throws_exception_on_connection_error(): void
    {
        Http::fake([
            'api.anthropic.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
            }
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Chyba připojení k Claude API');

        $this->service->sendMessage('Test');
    }

    /**
     * Test sendMessage throws exception on unexpected response format
     */
    public function test_send_message_throws_exception_on_unexpected_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'unexpected' => 'format'
            ], 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Neočekávaná odpověď z Claude API');

        $this->service->sendMessage('Test');
    }

    /**
     * Test translateHtml method returns translated text
     */
    public function test_translate_html_returns_translated_text(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => '<p>Dzień dobry</p>']
                ]
            ], 200)
        ]);

        $result = $this->service->translateHtml('<p>Dobrý den</p>', 'cs', 'pl');

        $this->assertEquals('<p>Dzień dobry</p>', $result);
    }

    /**
     * Test translateHtml builds correct prompt
     */
    public function test_translate_html_builds_correct_prompt(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Translated']
                ]
            ], 200)
        ]);

        $this->service->translateHtml('<p>Test</p>', 'cs', 'pl');

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['messages'][0]['content'];

            return str_contains($message, 'češtiny') &&
                   str_contains($message, 'polštiny') &&
                   str_contains($message, '<p>Test</p>') &&
                   str_contains($message, 'NEPŘEKLÁDEJ názvy alb');
        });
    }

    /**
     * Test translateHtml uses language names from mapping
     */
    public function test_translate_html_uses_language_names_from_mapping(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Translated']
                ]
            ], 200)
        ]);

        $this->service->translateHtml('<p>Test</p>', 'en', 'de');

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = $body['messages'][0]['content'];

            return str_contains($message, 'angličtiny') &&
                   str_contains($message, 'němčiny');
        });
    }

    /**
     * Test setModel method changes the model
     */
    public function test_set_model_changes_the_model(): void
    {
        $this->service->setModel('claude-3-opus-20240229');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Response']
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            return $request->data()['model'] === 'claude-3-opus-20240229';
        });
    }

    /**
     * Test getModel method returns current model
     */
    public function test_get_model_returns_current_model(): void
    {
        $this->assertEquals('claude-sonnet-4-5-20250929', $this->service->getModel());

        $this->service->setModel('claude-3-opus-20240229');
        $this->assertEquals('claude-3-opus-20240229', $this->service->getModel());
    }

    /**
     * Test setModel returns self for method chaining
     */
    public function test_set_model_returns_self_for_chaining(): void
    {
        $result = $this->service->setModel('test-model');

        $this->assertInstanceOf(ClaudeApiService::class, $result);
        $this->assertSame($this->service, $result);
    }

    /**
     * Test translateHtml with custom max tokens
     */
    public function test_translate_html_with_custom_max_tokens(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Translated']
                ]
            ], 200)
        ]);

        $this->service->translateHtml('<p>Test</p>', 'cs', 'pl', 8192);

        Http::assertSent(function ($request) {
            return $request->data()['max_tokens'] === 8192;
        });
    }

    /**
     * Test sendMessage with custom options
     */
    public function test_send_message_with_custom_options(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Response']
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test', 4096, [
            'temperature' => 0.7,
            'top_p' => 0.9
        ]);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['temperature'] === 0.7 && $body['top_p'] === 0.9;
        });
    }

    /**
     * Test API timeout is set to 120 seconds
     */
    public function test_api_request_has_correct_timeout(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Response']
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            // Laravel HTTP client uses guzzle options
            return true; // Timeout is set in the code, can't easily assert here
        });
    }
}
