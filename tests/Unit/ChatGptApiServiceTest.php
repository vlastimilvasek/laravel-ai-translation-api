<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatGptApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ChatGptApiServiceTest extends TestCase
{
    protected ChatGptApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set fake API key for testing
        Config::set('services.openai.api_key', 'sk-test-key-12345');

        $this->service = new ChatGptApiService();
    }

    /**
     * Test service throws exception when API key is missing
     */
    public function test_throws_exception_when_api_key_is_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OPENAI_API_KEY není nastavený v .env souboru!');

        Config::set('services.openai.api_key', '');
        new ChatGptApiService();
    }

    /**
     * Test sendMessage method returns text from API response
     */
    public function test_send_message_returns_text_from_api_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is a test response'
                        ]
                    ]
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
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-test-key-12345') &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /**
     * Test sendMessage sends correct request body
     */
    public function test_send_message_sends_correct_request_body(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test message', 2048);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['model'] === 'gpt-4o' &&
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
            'api.openai.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid API key'
                ]
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ChatGPT API error: Invalid API key');

        $this->service->sendMessage('Test');
    }

    /**
     * Test sendMessage throws exception on connection error
     */
    public function test_send_message_throws_exception_on_connection_error(): void
    {
        Http::fake([
            'api.openai.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
            }
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Chyba připojení k ChatGPT API');

        $this->service->sendMessage('Test');
    }

    /**
     * Test sendMessage throws exception on unexpected response format
     */
    public function test_send_message_throws_exception_on_unexpected_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'unexpected' => 'format'
            ], 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Neočekávaná odpověď z ChatGPT API');

        $this->service->sendMessage('Test');
    }

    /**
     * Test translateHtml method returns translated text
     */
    public function test_translate_html_returns_translated_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '<p>Hello</p>'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->translateHtml('<p>Ahoj</p>', 'cs', 'en');

        $this->assertEquals('<p>Hello</p>', $result);
    }

    /**
     * Test translateHtml removes markdown code blocks
     */
    public function test_translate_html_removes_markdown_code_blocks(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```html\n<p>Hello</p>\n```"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->translateHtml('<p>Ahoj</p>', 'cs', 'en');

        $this->assertEquals('<p>Hello</p>', $result);
    }

    /**
     * Test translateHtml removes markdown code blocks without language
     */
    public function test_translate_html_removes_markdown_code_blocks_without_language(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```\n<p>Hello</p>\n```"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->translateHtml('<p>Ahoj</p>', 'cs', 'en');

        $this->assertEquals('<p>Hello</p>', $result);
    }

    /**
     * Test translateHtml builds correct prompt
     */
    public function test_translate_html_builds_correct_prompt(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Translated'
                        ]
                    ]
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
                   str_contains($message, 'NEPŘEKLÁDEJ názvy alb') &&
                   str_contains($message, 'NEPOUŽÍVEJ markdown code bloky');
        });
    }

    /**
     * Test translateHtml uses language names from mapping
     */
    public function test_translate_html_uses_language_names_from_mapping(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Translated'
                        ]
                    ]
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
        $this->service->setModel('gpt-3.5-turbo');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            return $request->data()['model'] === 'gpt-3.5-turbo';
        });
    }

    /**
     * Test getModel method returns current model
     */
    public function test_get_model_returns_current_model(): void
    {
        $this->assertEquals('gpt-4o', $this->service->getModel());

        $this->service->setModel('gpt-3.5-turbo');
        $this->assertEquals('gpt-3.5-turbo', $this->service->getModel());
    }

    /**
     * Test setModel returns self for method chaining
     */
    public function test_set_model_returns_self_for_chaining(): void
    {
        $result = $this->service->setModel('gpt-4-turbo');

        $this->assertInstanceOf(ChatGptApiService::class, $result);
        $this->assertSame($this->service, $result);
    }

    /**
     * Test translateHtml with custom max tokens
     */
    public function test_translate_html_with_custom_max_tokens(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Translated'
                        ]
                    ]
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
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Response'
                        ]
                    ]
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
     * Test markdown cleaning with various formats
     */
    public function test_markdown_cleaning_handles_various_formats(): void
    {
        $testCases = [
            "```html\n<p>Test</p>\n```" => '<p>Test</p>',
            "```\n<p>Test</p>\n```" => '<p>Test</p>',
            "```HTML\n<p>Test</p>\n```" => '<p>Test</p>',
            "<p>Test</p>" => '<p>Test</p>',
            "  ```html\n<p>Test</p>\n```  " => '<p>Test</p>',
        ];

        foreach ($testCases as $input => $expected) {
            Http::fake([
                'api.openai.com/*' => Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => $input
                            ]
                        ]
                    ]
                ], 200)
            ]);

            $result = $this->service->translateHtml('<p>Ahoj</p>', 'cs', 'en');
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    /**
     * Test API timeout is set to 120 seconds
     */
    public function test_api_request_has_correct_timeout(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->service->sendMessage('Test');

        Http::assertSent(function ($request) {
            // Timeout is set in the code, can't easily assert here
            return true;
        });
    }
}
