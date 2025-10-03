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