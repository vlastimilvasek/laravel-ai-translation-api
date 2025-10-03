<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ClaudeApiService;
use App\Services\ChatGptApiService;

class BatchTranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:translate
                            {provider : AI provider (claude nebo chatgpt)}
                            {--create : Vytvoří nový batch job}
                            {--status= : Zjistí status batche podle ID}
                            {--results= : Stáhne výsledky batche podle ID}
                            {--cancel= : Zruší batch podle ID}
                            {--list : Vypíše všechny batche}
                            {--input= : CSV soubor se sloupci: id,text,from,to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hromadné překlady pomocí Batch API (50% sleva) - Claude nebo ChatGPT';

    /**
     * Execute the console command.
     */
    public function handle(ClaudeApiService $claude, ChatGptApiService $chatgpt)
    {
        $provider = $this->argument('provider');

        if (!in_array($provider, ['claude', 'chatgpt'])) {
            $this->error('Neplatný provider! Použijte: claude nebo chatgpt');
            return 1;
        }

        $service = $provider === 'claude' ? $claude : $chatgpt;

        // List batches
        if ($this->option('list')) {
            return $this->listBatches($service, $provider);
        }

        // Check status
        if ($batchId = $this->option('status')) {
            return $this->checkStatus($service, $batchId, $provider);
        }

        // Get results
        if ($batchId = $this->option('results')) {
            return $this->getResults($service, $batchId, $provider);
        }

        // Cancel batch
        if ($batchId = $this->option('cancel')) {
            return $this->cancelBatch($service, $batchId, $provider);
        }

        // Create batch
        if ($this->option('create')) {
            return $this->createBatch($service, $provider);
        }

        $this->error('Musíte specifikovat akci: --create, --status, --results, --cancel nebo --list');
        return 1;
    }

    private function createBatch($service, string $provider): int
    {
        $inputFile = $this->option('input');

        if (!$inputFile) {
            $this->error('Pro vytvoření batche musíte zadat --input=soubor.csv');
            return 1;
        }

        if (!file_exists($inputFile)) {
            $this->error("Soubor nenalezen: {$inputFile}");
            return 1;
        }

        $this->info("📂 Načítám data z {$inputFile}...");

        $translations = [];
        $row = 0;

        if (($handle = fopen($inputFile, 'r')) !== false) {
            // Skip header
            $header = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $row++;

                if (count($data) < 2) {
                    $this->warn("⚠️  Řádek {$row}: nedostatek sloupců, přeskakuji");
                    continue;
                }

                $translations[] = [
                    'id' => $data[0] ?? "row-{$row}",
                    'text' => $data[1] ?? '',
                    'from' => $data[2] ?? 'cs',
                    'to' => $data[3] ?? 'pl',
                ];
            }
            fclose($handle);
        }

        if (empty($translations)) {
            $this->error('❌ Žádná data k překladu!');
            return 1;
        }

        $count = count($translations);
        $maxLimit = $provider === 'claude' ? 100000 : 50000;

        if ($count > $maxLimit) {
            $this->error("❌ Příliš mnoho záznamů! Maximum pro {$provider}: {$maxLimit}");
            return 1;
        }

        $this->info("✅ Načteno {$count} záznamů k překladu");
        $this->info("💰 Očekávaná sleva: 50% (Batch API)");

        if (!$this->confirm("Pokračovat a vytvořit batch job?")) {
            $this->info('Zrušeno.');
            return 0;
        }

        try {
            $this->info("🚀 Vytvářím batch job...");
            $batch = $service->createBatchTranslation($translations);

            $batchId = $batch['id'] ?? 'unknown';
            $this->info("✅ Batch vytvořen!");
            $this->line("");
            $this->line("📋 Batch ID: <fg=green>{$batchId}</>");
            $this->line("📊 Status: " . ($batch['processing_status'] ?? $batch['status'] ?? 'unknown'));
            $this->line("");
            $this->info("ℹ️  Pro kontrolu statusu použijte:");
            $this->line("   php artisan batch:translate {$provider} --status={$batchId}");
            $this->line("");
            $this->info("📥 Pro stažení výsledků použijte (po dokončení):");
            $this->line("   php artisan batch:translate {$provider} --results={$batchId}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Chyba při vytváření batche: " . $e->getMessage());
            return 1;
        }
    }

    private function checkStatus($service, string $batchId, string $provider): int
    {
        try {
            $this->info("🔍 Kontroluji status batche {$batchId}...");
            $status = $service->getBatchStatus($batchId);

            $this->line("");
            $this->line("📋 Batch ID: <fg=green>{$batchId}</>");
            $this->line("📊 Status: " . ($status['processing_status'] ?? $status['status'] ?? 'unknown'));

            if (isset($status['request_counts'])) {
                $counts = $status['request_counts'];
                $this->line("📈 Požadavky:");
                $this->line("   ✅ Úspěšné: " . ($counts['succeeded'] ?? 0));
                $this->line("   ❌ Chybné: " . ($counts['errored'] ?? 0));
                $this->line("   ⏳ Zpracovává se: " . ($counts['processing'] ?? 0));
            }

            if (isset($status['created_at'])) {
                $this->line("🕐 Vytvořeno: " . $status['created_at']);
            }

            $this->line("");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Chyba: " . $e->getMessage());
            return 1;
        }
    }

    private function getResults($service, string $batchId, string $provider): int
    {
        try {
            $this->info("📥 Stahuji výsledky batche {$batchId}...");
            $results = $service->getBatchResults($batchId);

            $count = count($results);
            $this->info("✅ Staženo {$count} výsledků");

            // Save to file
            $outputFile = "batch-results-{$batchId}.jsonl";
            $this->info("💾 Ukládám do {$outputFile}...");

            $fp = fopen($outputFile, 'w');
            foreach ($results as $result) {
                fwrite($fp, json_encode($result) . "\n");
            }
            fclose($fp);

            $this->info("✅ Výsledky uloženy!");
            $this->line("");
            $this->line("📄 Soubor: <fg=green>{$outputFile}</>");
            $this->line("");

            // Show sample
            if ($count > 0) {
                $this->info("📝 Ukázka prvního výsledku:");
                $first = $results[0];
                $this->line(json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Chyba: " . $e->getMessage());
            return 1;
        }
    }

    private function cancelBatch($service, string $batchId, string $provider): int
    {
        try {
            if (!$this->confirm("Opravdu chcete zrušit batch {$batchId}?")) {
                $this->info('Zrušeno.');
                return 0;
            }

            $this->info("🛑 Rušším batch {$batchId}...");
            $result = $service->cancelBatch($batchId);

            $this->info("✅ Batch zrušen!");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Chyba: " . $e->getMessage());
            return 1;
        }
    }

    private function listBatches($service, string $provider): int
    {
        try {
            $this->info("📋 Načítám seznam batchů...");
            $data = $service->listBatches(20);

            $batches = $data['data'] ?? [];
            $count = count($batches);

            if ($count === 0) {
                $this->info("ℹ️  Zatím žádné batche");
                return 0;
            }

            $this->info("✅ Nalezeno {$count} batchů");
            $this->line("");

            foreach ($batches as $batch) {
                $id = $batch['id'] ?? 'unknown';
                $status = $batch['processing_status'] ?? $batch['status'] ?? 'unknown';
                $created = $batch['created_at'] ?? 'unknown';

                $this->line("🔹 <fg=cyan>{$id}</> | Status: {$status} | Vytvořeno: {$created}");
            }

            $this->line("");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Chyba: " . $e->getMessage());
            return 1;
        }
    }
}
