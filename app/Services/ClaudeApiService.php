<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ClaudeApiService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-5-20250929';
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $batchApiUrl = 'https://api.anthropic.com/v1/messages/batches';
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

    /**
     * Vytvoří batch pro hromadné překlady
     *
     * @param array $translations [{id: string, text: string, from: string, to: string}, ...]
     * @param int $maxTokens Max tokens per translation
     * @return array Batch response with batch_id and request_counts
     */
    public function createBatchTranslation(array $translations, int $maxTokens = 4096): array
    {
        $requests = [];
        $languageNames = $this->getLanguageNames();

        foreach ($translations as $translation) {
            $customId = $translation['id'];
            $text = $translation['text'];
            $from = $translation['from'] ?? 'cs';
            $to = $translation['to'] ?? 'pl';

            $fromLang = $languageNames[$from] ?? $from;
            $toLang = $languageNames[$to] ?? $to;
            $prompt = $this->buildTranslationPrompt($text, $fromLang, $toLang);

            $requests[] = [
                'custom_id' => $customId,
                'params' => [
                    'model' => $this->model,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->batchApiUrl, [
                'requests' => $requests,
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude Batch API: ' . $e->getMessage());
        }
    }

    /**
     * Získá status batche
     *
     * @param string $batchId
     * @return array Batch status with processing_status and request_counts
     */
    public function getBatchStatus(string $batchId): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
            ])
            ->timeout(30)
            ->get("{$this->batchApiUrl}/{$batchId}");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude Batch API: ' . $e->getMessage());
        }
    }

    /**
     * Stáhne výsledky batche
     *
     * @param string $batchId
     * @return array Results array with custom_id and result for each request
     */
    public function getBatchResults(string $batchId): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
            ])
            ->timeout(120)
            ->get("{$this->batchApiUrl}/{$batchId}/results");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude Batch API error: ' . $errorMessage);
            }

            // Results are in JSONL format - parse line by line
            $body = $response->body();
            $lines = explode("\n", trim($body));
            $results = [];

            foreach ($lines as $line) {
                if (!empty($line)) {
                    $results[] = json_decode($line, true);
                }
            }

            return $results;

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude Batch API: ' . $e->getMessage());
        }
    }

    /**
     * Zruší běžící batch
     *
     * @param string $batchId
     * @return array Cancellation response
     */
    public function cancelBatch(string $batchId): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
            ])
            ->timeout(30)
            ->post("{$this->batchApiUrl}/{$batchId}/cancel");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude Batch API: ' . $e->getMessage());
        }
    }

    /**
     * Vypíše všechny batche
     *
     * @param int $limit Počet batchů k načtení (max 100)
     * @return array List of batches
     */
    public function listBatches(int $limit = 20): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
            ])
            ->timeout(30)
            ->get($this->batchApiUrl, [
                'limit' => min($limit, 100),
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('Claude Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k Claude Batch API: ' . $e->getMessage());
        }
    }
}
