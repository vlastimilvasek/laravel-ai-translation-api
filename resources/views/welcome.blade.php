@extends('layouts.app')

@section('title', 'Claude API - Dokumentace projektu')

@section('content')
<div class="container">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h1 class="display-4 text-white mb-3">
                        <i class="bi bi-robot me-3"></i>Claude API Překladač
                    </h1>
                    <p class="lead text-white mb-4">
                        Laravel 10 aplikace pro překlad HTML textů pomocí Claude AI a ChatGPT
                    </p>
                    <a href="{{ route('translate.form') }}" class="btn btn-light btn-lg">
                        <i class="bi bi-translate me-2"></i>Začít překládat
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Funkce projektu -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <h2 class="mb-4"><i class="bi bi-list-check me-2"></i>Co projekt umí?</h2>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-terminal text-primary me-2"></i>CLI překlady
                    </h5>
                    <p class="card-text">Překládejte HTML soubory přímo z příkazové řádky pomocí Artisan commands.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-window text-success me-2"></i>Web rozhraní
                    </h5>
                    <p class="card-text">Přátelský formulář pro rychlé překlady přímo v prohlížeči.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-code-square text-info me-2"></i>REST API
                    </h5>
                    <p class="card-text">JSON API pro integraci s jinými aplikacemi a službami.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CLI Commands -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="mb-0"><i class="bi bi-terminal me-2"></i>CLI Příkazy (Artisan Commands)</h3>
                </div>
                <div class="card-body">
                    
                    <h5 class="mb-3">Claude API překlad</h5>
                    <pre><code># Základní použití (interaktivní)
php artisan claude:translate

# Překlad ze souboru
php artisan claude:translate --input=storage/translations/input/text.html --output=storage/translations/output/text_pl.html

# Překlad přímého textu
php artisan claude:translate --text="&lt;p&gt;Dobrý den&lt;/p&gt;" --to=pl

# Překlad do jiného jazyka
php artisan claude:translate --input=text.html --from=cs --to=en --output=text_en.html</code></pre>

                    <hr class="my-4">

                    <h5 class="mb-3">ChatGPT API překlad</h5>
                    <pre><code># Základní použití
php artisan chatgpt:translate --text="&lt;p&gt;Dobrý den&lt;/p&gt;" --to=pl

# Překlad se souborem
php artisan chatgpt:translate --input=text.html --output=text_pl.html

# Volba modelu
php artisan chatgpt:translate --text="&lt;p&gt;Hello&lt;/p&gt;" --to=cs --model=gpt-3.5-turbo

# Dostupné modely: gpt-4o, gpt-4-turbo, gpt-3.5-turbo</code></pre>

                    <hr class="my-4">

                    <h5 class="mb-3">Kontrola konfigurace</h5>
                    <pre><code># Zkontroluj nastavení API klíčů
php artisan claude:check</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- REST API -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-cloud me-2"></i>REST API Endpointy</h3>
                </div>
                <div class="card-body">
                    
                    <div class="mb-4">
                        <h5><span class="badge bg-success">POST</span> /preklad/claude</h5>
                        <p class="mb-2">Přeloží HTML text pomocí Claude API</p>
                        <p class="mb-2"><strong>Request Body (JSON):</strong></p>
                        <pre><code>{
    "text": "&lt;p&gt;Text k překladu&lt;/p&gt;",
    "from": "cs",
    "to": "pl"
}</code></pre>
                        <p class="mb-2"><strong>Response:</strong></p>
                        <pre><code>{
    "translated": "&lt;p&gt;Tekst do tłumaczenia&lt;/p&gt;"
}</code></pre>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5><span class="badge bg-success">POST</span> /preklad/chatgpt</h5>
                        <p class="mb-2">Přeloží HTML text pomocí ChatGPT API</p>
                        <p class="mb-2"><strong>Request Body (JSON):</strong></p>
                        <pre><code>{
    "text": "&lt;p&gt;Text k překladu&lt;/p&gt;",
    "from": "cs",
    "to": "en"
}</code></pre>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5><span class="badge bg-success">POST</span> /ask/claude</h5>
                        <p class="mb-2">Pošle obecnou zprávu do Claude AI</p>
                        <p class="mb-2"><strong>Request Body (JSON):</strong></p>
                        <pre><code>{
    "message": "Jaká je hlavní města Polska?"
}</code></pre>
                        <p class="mb-2"><strong>Response:</strong></p>
                        <pre><code>{
    "response": "Hlavním městem Polska je Varšava..."
}</code></pre>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Tip:</strong> Pro testování API použij nástroje jako Postman, Insomnia nebo curl.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Curl příklady -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="mb-0"><i class="bi bi-code-slash me-2"></i>Příklady s CURL</h3>
                </div>
                <div class="card-body">
                    <h5>Překlad pomocí Claude</h5>
                    <pre><code>curl -X POST {{ url('/preklad/claude') }} \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "text": "&lt;p&gt;Dobrý den, jak se máte?&lt;/p&gt;",
    "from": "cs",
    "to": "pl"
  }'</code></pre>

                    <hr>

                    <h5>Překlad pomocí ChatGPT</h5>
                    <pre><code>curl -X POST {{ url('/preklad/chatgpt') }} \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "text": "&lt;p&gt;Hello world&lt;/p&gt;",
    "from": "en",
    "to": "cs"
  }'</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Podporované jazyky -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0"><i class="bi bi-globe me-2"></i>Podporované jazyky</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <ul class="list-unstyled">
                                <li><strong>cs</strong> - Čeština</li>
                                <li><strong>pl</strong> - Polština</li>
                                <li><strong>en</strong> - Angličtina</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="list-unstyled">
                                <li><strong>de</strong> - Němčina</li>
                                <li><strong>sk</strong> - Slovenština</li>
                                <li><strong>fr</strong> - Francouzština</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="list-unstyled">
                                <li><strong>es</strong> - Španělština</li>
                                <li><strong>it</strong> - Italština</li>
                                <li><strong>ru</strong> - Ruština</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="list-unstyled">
                                <li><strong>uk</strong> - Ukrajinština</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Konfigurace -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h3 class="mb-0"><i class="bi bi-gear me-2"></i>Konfigurace (.env)</h3>
                </div>
                <div class="card-body">
                    <p>Pro správné fungování aplikace je potřeba nastavit API klíče v <code>.env</code> souboru:</p>
                    <pre><code>ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...</code></pre>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Pozor:</strong> API klíče nikdy nezveřejňujte a nesdílejte je!
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection