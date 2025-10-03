<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\ChatGptApiService;

class TranslateChatGptCommand extends Command
{
    protected $signature = 'chatgpt:translate 
                            {--input= : Cesta k vstupnímu HTML souboru}
                            {--output= : Cesta k výstupnímu souboru}
                            {--from=cs : Zdrojový jazyk}
                            {--to=pl : Cílový jazyk}
                            {--text= : Přímý text k překladu}
                            {--model=gpt-4o : Model ChatGPT (gpt-4o, gpt-4-turbo, gpt-3.5-turbo)}';

    protected $description = 'Překládá HTML text pomocí ChatGPT API';

    private ChatGptApiService $chatGptApi;

    public function __construct(ChatGptApiService $chatGptApi)
    {
        parent::__construct();
        $this->chatGptApi = $chatGptApi;
    }

    public function handle(): int
    {
        try {
            // Získání textu k překladu
            $htmlText = $this->getInputText();
            
            if (empty($htmlText)) {
                $this->components->error('Nebyl zadán žádný text k překladu!');
                return self::FAILURE;
            }

            // Nastavení modelu, pokud je zadán
            if ($model = $this->option('model')) {
                $this->chatGptApi->setModel($model);
            }

            $this->components->info('Zahajuji překlad pomocí ChatGPT API...');
            
            // Překlad pomocí ChatGPT API Service
            $translatedText = $this->chatGptApi->translateHtml(
                $htmlText,
                $this->option('from'),
                $this->option('to')
            );

            // Uložení nebo zobrazení výsledku
            $this->handleOutput($translatedText);

            $this->components->info('Překlad dokončen!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->components->error('Chyba: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function getInputText(): string
    {
        // Priorita: --text > --input > stdin
        if ($text = $this->option('text')) {
            return $text;
        }

        if ($inputFile = $this->option('input')) {
            $fullPath = base_path($inputFile);
            
            if (!File::exists($fullPath)) {
                throw new \Exception("Soubor '$inputFile' nebyl nalezen!");
            }
            
            return File::get($fullPath);
        }

        // Pokud není zadán ani text ani soubor, ptáme se uživatele
        return $this->ask('Zadej HTML text k překladu') ?? '';
    }

    private function handleOutput(string $text): void
    {
        if ($outputFile = $this->option('output')) {
            $fullPath = base_path($outputFile);
            
            // Vytvoř adresář, pokud neexistuje
            $directory = dirname($fullPath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            File::put($fullPath, $text);
            $this->components->info("Překlad uložen do: $outputFile");
        } else {
            $this->newLine();
            $this->line('=== PŘELOŽENÝ TEXT ===');
            $this->line($text);
            $this->line('======================');
        }
    }
}
