<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class TranslationApiTest extends TestCase
{
    /**
     * Test Claude translation endpoint with valid data
     */
    public function test_claude_translation_returns_success_response(): void
    {
        // Mock Claude API response
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => '<p>Dzień dobry</p>']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => '<p>Dobrý den</p>',
            'from' => 'cs',
            'to' => 'pl'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['translated'])
            ->assertJson([
                'translated' => '<p>Dzień dobry</p>'
            ]);
    }

    /**
     * Test Claude translation endpoint with missing text field
     */
    public function test_claude_translation_validates_required_text(): void
    {
        $response = $this->postJson('/api/v1/translate/claude', [
            'from' => 'cs',
            'to' => 'pl'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    /**
     * Test Claude translation endpoint with text exceeding max length
     */
    public function test_claude_translation_validates_text_max_length(): void
    {
        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => str_repeat('a', 50001),
            'from' => 'cs',
            'to' => 'pl'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    /**
     * Test Claude translation endpoint with invalid language code
     */
    public function test_claude_translation_validates_language_code_format(): void
    {
        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => '<p>Test</p>',
            'from' => 'cze', // Invalid - must be 2 chars
            'to' => 'pl'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    /**
     * Test Claude translation endpoint handles API errors
     */
    public function test_claude_translation_handles_api_errors(): void
    {
        // Mock Claude API error
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'error' => [
                    'message' => 'API key invalid'
                ]
            ], 401)
        ]);

        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => '<p>Test</p>',
            'from' => 'cs',
            'to' => 'pl'
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test ChatGPT translation endpoint with valid data
     */
    public function test_chatgpt_translation_returns_success_response(): void
    {
        // Mock OpenAI API response
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

        $response = $this->postJson('/api/v1/translate/chatgpt', [
            'text' => '<p>Ahoj</p>',
            'from' => 'cs',
            'to' => 'en'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['translated'])
            ->assertJson([
                'translated' => '<p>Hello</p>'
            ]);
    }

    /**
     * Test ChatGPT translation endpoint with missing text field
     */
    public function test_chatgpt_translation_validates_required_text(): void
    {
        $response = $this->postJson('/api/v1/translate/chatgpt', [
            'from' => 'cs',
            'to' => 'en'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    /**
     * Test ChatGPT translation endpoint handles API errors
     */
    public function test_chatgpt_translation_handles_api_errors(): void
    {
        // Mock OpenAI API error
        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid API key'
                ]
            ], 401)
        ]);

        $response = $this->postJson('/api/v1/translate/chatgpt', [
            'text' => '<p>Test</p>',
            'from' => 'cs',
            'to' => 'en'
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test ask Claude endpoint with valid message
     */
    public function test_ask_claude_returns_success_response(): void
    {
        // Mock Claude API response
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Hlavním městem Polska je Varšava.']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/v1/ask/claude', [
            'message' => 'Jaké je hlavní město Polska?'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['response'])
            ->assertJson([
                'response' => 'Hlavním městem Polska je Varšava.'
            ]);
    }

    /**
     * Test ask Claude endpoint validates required message
     */
    public function test_ask_claude_validates_required_message(): void
    {
        $response = $this->postJson('/api/v1/ask/claude', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test ask Claude endpoint validates message max length
     */
    public function test_ask_claude_validates_message_max_length(): void
    {
        $response = $this->postJson('/api/v1/ask/claude', [
            'message' => str_repeat('a', 50001)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test ask Claude endpoint handles API errors
     */
    public function test_ask_claude_handles_api_errors(): void
    {
        // Mock Claude API error
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded'
                ]
            ], 429)
        ]);

        $response = $this->postJson('/api/v1/ask/claude', [
            'message' => 'Test question'
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test Claude translation preserves HTML structure
     */
    public function test_claude_translation_preserves_html_structure(): void
    {
        $htmlInput = '<div class="test"><p>Text <strong>tučně</strong></p></div>';
        $htmlOutput = '<div class="test"><p>Tekst <strong>pogrubiony</strong></p></div>';

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => $htmlOutput]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => $htmlInput,
            'from' => 'cs',
            'to' => 'pl'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'translated' => $htmlOutput
            ]);
    }

    /**
     * Test translation with default language parameters
     */
    public function test_translation_uses_default_languages_when_not_specified(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => '<p>Translated</p>']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/v1/translate/claude', [
            'text' => '<p>Test</p>'
            // from and to not specified, should use defaults: cs -> pl
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['translated']);
    }
}
