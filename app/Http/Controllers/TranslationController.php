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
}