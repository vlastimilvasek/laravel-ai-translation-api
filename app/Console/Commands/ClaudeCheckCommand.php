<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClaudeCheckCommand extends Command
{
    protected $signature = 'claude:check';
    protected $description = 'Zkontroluje konfiguraci Claude a ChatGPT API';

    public function handle(): int
    {
        $this->components->info('Kontrola konfigurace AI API...');
        $this->newLine();

        // Claude API
        $claudeKey = config('services.anthropic.api_key');
        if (empty($claudeKey)) {
            $this->components->error('❌ ANTHROPIC_API_KEY není nastaven v .env!');
        } else {
            $this->components->info('✓ Claude API klíč je nastaven (' . strlen($claudeKey) . ' znaků)');
        }

        // OpenAI API
        $openaiKey = config('services.openai.api_key');
        if (empty($openaiKey)) {
            $this->components->error('❌ OPENAI_API_KEY není nastaven v .env!');
        } else {
            $this->components->info('✓ OpenAI API klíč je nastaven (' . strlen($openaiKey) . ' znaků)');
        }

        $this->newLine();
        
        if (empty($claudeKey) || empty($openaiKey)) {
            $this->components->warn('Některé API klíče chybí. Přidej je do .env souboru:');
            $this->line('ANTHROPIC_API_KEY=sk-ant-...');
            $this->line('OPENAI_API_KEY=sk-...');
            return self::FAILURE;
        }

        $this->components->info('Všechny API klíče jsou nastaveny správně!');
        $this->newLine();
        
        $this->components->info('Dostupné příkazy:');
        $this->line('  php artisan claude:translate --help');
        $this->line('  php artisan chatgpt:translate --help');
        $this->newLine();
        
        $this->components->info('Web rozhraní:');
        $this->line('  ' . url('/') . ' - Dokumentace');
        $this->line('  ' . url('/preklad') . ' - Překladač');

        return self::SUCCESS;
    }
}
