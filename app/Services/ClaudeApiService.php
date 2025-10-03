<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ClaudeApiService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-5-20250929';
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $apiVersion = '2023-06-01';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        
        if (empty($this->apiKey)) {
            throw new \Exception('ANTHROPIC_API_KEY není nastavený v .env souboru!');
        }
    }

    /**
     * Přeloží HTML text z jednoho jazyka do druhého
     */
    public function translateHtml(string $text, string $from = 'cs', string $to = 'pl', int $maxTokens = 4096): string
    {
        $languageNames = $this->getLanguageNames();
        $fromLang = $languageNames[$from] ?? $from;
        $toLang = $languageNames[$to] ?? $to;

        $prompt = $this->buildTranslationPrompt($text, $fromLang, $toLang);

        return $this->sendMessage($prompt, $maxTokens);
    }

    /**
     * Pošle obecnou zprávu do Claude API
     */
    public function sendMessage(string $message, int $maxTokens = 4096, array $options = []): string
    {
        $params = array_merge([
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
        ], $options);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])
            ->timeout(120)
            ->post($this->apiUrl, $params);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude API error: ' . $errorMessage);
            }

            $data = $response->json();

            if (!isset($data['content'][0]['text'])) {
                throw new \Exception('Neočekávaná odpověď z Claude API: ' . json_encode($data));
            }

            return $data['content'][0]['text'];

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude API: ' . $e->getMessage());
        }
    }

    private function buildTranslationPrompt(string $text, string $fromLang, string $toLang): string
    {
        return <<<EOT
Přelož následující HTML text z {$fromLang} do {$toLang}.

DŮLEŽITÉ INSTRUKCE:
- Zachovej přesně všechny HTML tagy a strukturu
- Překládej pouze textový obsah uvnitř tagů
- NEPŘEKLÁDEJ názvy alb (text v <em> tazích)
- NEPŘEKLÁDEJ jména osob, značky a vlastní názvy
- Zachovej všechny atributy HTML tagů beze změny
- Vrať pouze přeložený HTML kód bez jakýchkoliv vysvětlení

HTML text k překladu:

{$text}
EOT;
    }

    private function getLanguageNames(): array
    {
        return [
            'cs' => 'češtiny',
            'pl' => 'polštiny',
            'en' => 'angličtiny',
            'de' => 'němčiny',
            'sk' => 'slovenštiny',
            'fr' => 'francouzštiny',
            'es' => 'španělštiny',
            'it' => 'italštiny',
            'ru' => 'ruštiny',
            'uk' => 'ukrajinštiny',
        ];
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
