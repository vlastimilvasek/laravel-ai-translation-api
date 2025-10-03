<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ChatGptApiService
{
    private string $apiKey;
    private string $model = 'gpt-4o';
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new \Exception('OPENAI_API_KEY není nastavený v .env souboru!');
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

        $response = $this->sendMessage($prompt, $maxTokens);
        
        // Odstraň markdown code bloky
        return $this->cleanMarkdownCodeBlocks($response);
    }

    /**
     * Pošle obecnou zprávu do ChatGPT API
     */
    public function sendMessage(string $message, int $maxTokens = 4096, array $options = []): string
    {
        $params = array_merge([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
            'max_tokens' => $maxTokens,
        ], $options);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post($this->apiUrl, $params);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('ChatGPT API error: ' . $errorMessage);
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Neočekávaná odpověď z ChatGPT API: ' . json_encode($data));
            }

            return $data['choices'][0]['message']['content'];

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k ChatGPT API: ' . $e->getMessage());
        }
    }

    /**
     * Odstraní markdown code bloky z odpovědi (```html ... ``` nebo ```...```)
     */
    private function cleanMarkdownCodeBlocks(string $text): string
    {
        // Odstraň ```html na začátku a ``` na konci
        $text = preg_replace('/^```html\s*/i', '', $text);
        $text = preg_replace('/^```\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        
        return trim($text);
    }

    /**
     * Vytvoří prompt pro překlad HTML
     */
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
- Vrať POUZE přeložený HTML kód bez markdown formátování
- NEPOUŽÍVEJ markdown code bloky (```html nebo ```)
- Vrať čistý HTML kód bez jakýchkoliv vysvětlení

HTML text k překladu:

{$text}
EOT;
    }

    /**
     * Vrátí mapování kódů jazyků na jejich názvy
     */
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

    /**
     * Nastaví model pro API volání
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Získá aktuální model
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
