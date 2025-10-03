<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ChatGptApiService
{
    private string $apiKey;
    private string $model = 'gpt-4o';
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private string $filesApiUrl = 'https://api.openai.com/v1/files';
    private string $batchApiUrl = 'https://api.openai.com/v1/batches';

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

    /**
     * Vytvoří batch pro hromadné překlady pomocí OpenAI Batch API
     *
     * @param array $translations [{id: string, text: string, from: string, to: string}, ...]
     * @param int $maxTokens Max tokens per translation
     * @return array Batch response with batch_id and status
     */
    public function createBatchTranslation(array $translations, int $maxTokens = 4096): array
    {
        // Vytvoř JSONL obsah
        $jsonlLines = [];
        $languageNames = $this->getLanguageNames();

        foreach ($translations as $translation) {
            $customId = $translation['id'];
            $text = $translation['text'];
            $from = $translation['from'] ?? 'cs';
            $to = $translation['to'] ?? 'pl';

            $fromLang = $languageNames[$from] ?? $from;
            $toLang = $languageNames[$to] ?? $to;
            $prompt = $this->buildTranslationPrompt($text, $fromLang, $toLang);

            $request = [
                'custom_id' => $customId,
                'method' => 'POST',
                'url' => '/v1/chat/completions',
                'body' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $maxTokens,
                ],
            ];

            $jsonlLines[] = json_encode($request);
        }

        $jsonlContent = implode("\n", $jsonlLines);

        // Upload JSONL soubor
        $fileId = $this->uploadBatchFile($jsonlContent);

        // Vytvoř batch job
        return $this->createBatchJob($fileId);
    }

    /**
     * Nahraje JSONL soubor pro batch processing
     *
     * @param string $jsonlContent JSONL content
     * @return string File ID
     */
    private function uploadBatchFile(string $jsonlContent): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(60)
            ->attach('file', $jsonlContent, 'batch_requests.jsonl')
            ->post($this->filesApiUrl, [
                'purpose' => 'batch',
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Files API error: ' . $errorMessage);
            }

            $data = $response->json();
            return $data['id'];

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Files API: ' . $e->getMessage());
        }
    }

    /**
     * Vytvoří batch job
     *
     * @param string $fileId File ID from uploadBatchFile
     * @return array Batch response
     */
    private function createBatchJob(string $fileId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->batchApiUrl, [
                'input_file_id' => $fileId,
                'endpoint' => '/v1/chat/completions',
                'completion_window' => '24h',
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Batch API: ' . $e->getMessage());
        }
    }

    /**
     * Získá status batche
     *
     * @param string $batchId
     * @return array Batch status
     */
    public function getBatchStatus(string $batchId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(30)
            ->get("{$this->batchApiUrl}/{$batchId}");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Batch API: ' . $e->getMessage());
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
        // Nejdřív získej status, abychom zjistili output_file_id
        $status = $this->getBatchStatus($batchId);

        if (!isset($status['output_file_id'])) {
            throw new \Exception('Batch ještě nemá výsledky nebo skončil s chybou');
        }

        $outputFileId = $status['output_file_id'];

        // Stáhni soubor s výsledky
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(120)
            ->get("{$this->filesApiUrl}/{$outputFileId}/content");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Files API error: ' . $errorMessage);
            }

            // Results are in JSONL format - parse line by line
            $body = $response->body();
            $lines = explode("\n", trim($body));
            $results = [];

            foreach ($lines as $line) {
                if (!empty($line)) {
                    $lineData = json_decode($line, true);

                    // Zpracuj výsledek a vyčisti markdown
                    if (isset($lineData['response']['body']['choices'][0]['message']['content'])) {
                        $content = $lineData['response']['body']['choices'][0]['message']['content'];
                        $lineData['response']['body']['choices'][0]['message']['content'] =
                            $this->cleanMarkdownCodeBlocks($content);
                    }

                    $results[] = $lineData;
                }
            }

            return $results;

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Files API: ' . $e->getMessage());
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
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(30)
            ->post("{$this->batchApiUrl}/{$batchId}/cancel");

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Batch API: ' . $e->getMessage());
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
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(30)
            ->get($this->batchApiUrl, [
                'limit' => min($limit, 100),
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? $response->body();
                throw new \Exception('OpenAI Batch API error: ' . $errorMessage);
            }

            return $response->json();

        } catch (ConnectionException $e) {
            throw new \Exception('Chyba připojení k OpenAI Batch API: ' . $e->getMessage());
        }
    }
}
