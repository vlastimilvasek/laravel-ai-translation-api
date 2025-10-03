<?php

namespace App\Http\Controllers;

use App\Services\ClaudeApiService;
use App\Services\ChatGptApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    public function __construct(
        private ClaudeApiService $claudeApi,
        private ChatGptApiService $chatGptApi
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/translate/claude",
     *     summary="Překlad HTML textu pomocí Claude AI",
     *     description="Překládá HTML text mezi jazyky pomocí Claude Sonnet 4.5. Zachovává HTML strukturu a nepřekládá vlastní názvy.",
     *     tags={"Translation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=50000, example="<p>Dobrý den, jak se máte?</p>", description="HTML text k překladu"),
     *             @OA\Property(property="from", type="string", minLength=2, maxLength=2, example="cs", description="Zdrojový jazyk (2 znaky ISO kód, výchozí: cs)"),
     *             @OA\Property(property="to", type="string", minLength=2, maxLength=2, example="pl", description="Cílový jazyk (2 znaky ISO kód, výchozí: pl)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Úspěšný překlad",
     *         @OA\JsonContent(
     *             @OA\Property(property="translated", type="string", example="<p>Dzień dobry, jak się masz?</p>")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Neautorizovaný přístup - chybí nebo neplatný Bearer token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validační chyba",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validační chyba"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="text", type="array", @OA\Items(type="string", example="The text field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Chyba serveru nebo Claude API",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chyba při překladu: API error")
     *         )
     *     )
     * )
     */
    public function translateWithClaude(Request $request)
    {
        // DEBUG: Vypiš, co přišlo
        Log::info('Request data:', $request->all());
        Log::info('Raw input:', ['raw' => $request->getContent()]);

        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'from' => 'sometimes|string|size:2',
            'to' => 'sometimes|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validační chyba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $translated = $this->claudeApi->translateHtml(
                $request->input('text'),
                $request->input('from', 'cs'),
                $request->input('to', 'pl')
            );

            return response()->json(['translated' => $translated]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při překladu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/translate/chatgpt",
     *     summary="Překlad HTML textu pomocí ChatGPT",
     *     description="Překládá HTML text mezi jazyky pomocí GPT-4o. Zachovává HTML strukturu.",
     *     tags={"Translation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=50000, example="<p>Hello world</p>"),
     *             @OA\Property(property="from", type="string", example="en"),
     *             @OA\Property(property="to", type="string", example="cs")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Úspěšný překlad"),
     *     @OA\Response(response=401, description="Neautorizováno"),
     *     @OA\Response(response=422, description="Validační chyba"),
     *     @OA\Response(response=500, description="Chyba serveru")
     * )
     */
    public function translateWithChatGpt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'from' => 'sometimes|string|size:2',
            'to' => 'sometimes|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validační chyba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $translated = $this->chatGptApi->translateHtml(
                $request->input('text'),
                $request->input('from', 'cs'),
                $request->input('to', 'pl')
            );

            return response()->json(['translated' => $translated]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při překladu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ask/claude",
     *     summary="Obecná komunikace s Claude AI",
     *     description="Pošle obecnou zprávu do Claude AI a vrátí odpověď",
     *     tags={"Claude"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", maxLength=50000, example="Jaké je hlavní město Polska?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Úspěšná odpověď",
     *         @OA\JsonContent(
     *             @OA\Property(property="response", type="string", example="Hlavním městem Polska je Varšava.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Neautorizováno"),
     *     @OA\Response(response=422, description="Validační chyba"),
     *     @OA\Response(response=500, description="Chyba serveru")
     * )
     */
    public function askClaude(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validační chyba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $response = $this->claudeApi->sendMessage(
                $request->input('message')
            );

            return response()->json(['response' => $response]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při komunikaci s Claude: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vytvoří batch pro hromadné překlady pomocí Claude
     *
     * @OA\Post(
     *     path="/api/v1/batch/claude",
     *     summary="Vytvoří batch job pro hromadné překlady pomocí Claude (50% sleva)",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"translations"},
     *             @OA\Property(
     *                 property="translations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id","text"},
     *                     @OA\Property(property="id", type="string", example="article-123"),
     *                     @OA\Property(property="text", type="string", example="<p>Text k překladu</p>"),
     *                     @OA\Property(property="from", type="string", example="cs"),
     *                     @OA\Property(property="to", type="string", example="pl")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Batch úspěšně vytvořen")
     * )
     */
    public function createClaudeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array|min:1|max:100000',
            'translations.*.id' => 'required|string',
            'translations.*.text' => 'required|string|max:50000',
            'translations.*.from' => 'nullable|string|size:2',
            'translations.*.to' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validační chyba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $batch = $this->claudeApi->createBatchTranslation($request->input('translations'));
            return response()->json($batch);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při vytváření batche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vytvoří batch pro hromadné překlady pomocí ChatGPT
     *
     * @OA\Post(
     *     path="/api/v1/batch/chatgpt",
     *     summary="Vytvoří batch job pro hromadné překlady pomocí ChatGPT (50% sleva)",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"translations"},
     *             @OA\Property(
     *                 property="translations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id","text"},
     *                     @OA\Property(property="id", type="string", example="article-456"),
     *                     @OA\Property(property="text", type="string", example="<p>Text to translate</p>"),
     *                     @OA\Property(property="from", type="string", example="en"),
     *                     @OA\Property(property="to", type="string", example="cs")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Batch úspěšně vytvořen")
     * )
     */
    public function createChatGptBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array|min:1|max:50000',
            'translations.*.id' => 'required|string',
            'translations.*.text' => 'required|string|max:50000',
            'translations.*.from' => 'nullable|string|size:2',
            'translations.*.to' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validační chyba',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $batch = $this->chatGptApi->createBatchTranslation($request->input('translations'));
            return response()->json($batch);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při vytváření batche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Získá status batche
     *
     * @OA\Get(
     *     path="/api/v1/batch/{provider}/{batchId}/status",
     *     summary="Získá status batch jobu",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"claude","chatgpt"})),
     *     @OA\Parameter(name="batchId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Status batche")
     * )
     */
    public function getBatchStatus(Request $request, string $provider, string $batchId)
    {
        try {
            if ($provider === 'claude') {
                $status = $this->claudeApi->getBatchStatus($batchId);
            } elseif ($provider === 'chatgpt') {
                $status = $this->chatGptApi->getBatchStatus($batchId);
            } else {
                return response()->json(['message' => 'Neplatný provider'], 400);
            }

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při získávání statusu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stáhne výsledky batche
     *
     * @OA\Get(
     *     path="/api/v1/batch/{provider}/{batchId}/results",
     *     summary="Stáhne výsledky batch jobu",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"claude","chatgpt"})),
     *     @OA\Parameter(name="batchId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Výsledky batche")
     * )
     */
    public function getBatchResults(Request $request, string $provider, string $batchId)
    {
        try {
            if ($provider === 'claude') {
                $results = $this->claudeApi->getBatchResults($batchId);
            } elseif ($provider === 'chatgpt') {
                $results = $this->chatGptApi->getBatchResults($batchId);
            } else {
                return response()->json(['message' => 'Neplatný provider'], 400);
            }

            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při stahování výsledků: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Zruší batch job
     *
     * @OA\Post(
     *     path="/api/v1/batch/{provider}/{batchId}/cancel",
     *     summary="Zruší běžící batch job",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"claude","chatgpt"})),
     *     @OA\Parameter(name="batchId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Batch zrušen")
     * )
     */
    public function cancelBatch(Request $request, string $provider, string $batchId)
    {
        try {
            if ($provider === 'claude') {
                $result = $this->claudeApi->cancelBatch($batchId);
            } elseif ($provider === 'chatgpt') {
                $result = $this->chatGptApi->cancelBatch($batchId);
            } else {
                return response()->json(['message' => 'Neplatný provider'], 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při rušení batche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vypíše všechny batche
     *
     * @OA\Get(
     *     path="/api/v1/batch/{provider}",
     *     summary="Vypíše všechny batch joby",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"claude","chatgpt"})),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Seznam batchů")
     * )
     */
    public function listBatches(Request $request, string $provider)
    {
        $limit = $request->query('limit', 20);

        try {
            if ($provider === 'claude') {
                $batches = $this->claudeApi->listBatches($limit);
            } elseif ($provider === 'chatgpt') {
                $batches = $this->chatGptApi->listBatches($limit);
            } else {
                return response()->json(['message' => 'Neplatný provider'], 400);
            }

            return response()->json($batches);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chyba při získávání seznamu: ' . $e->getMessage()
            ], 500);
        }
    }
}