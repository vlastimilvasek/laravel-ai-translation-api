@extends('layouts.app')

@section('title', 'Překlad HTML textu')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Hlavička -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h1 class="text-white mb-2">
                        <i class="bi bi-translate me-2"></i>HTML Překladač
                    </h1>
                    <p class="text-white mb-0">Přeložte HTML text pomocí AI - Claude nebo ChatGPT</p>
                </div>
            </div>

            <!-- Formulář -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form id="translateForm">
                        @csrf
                        
                        <!-- Volba API -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-cpu me-2"></i>Zvolte AI model
                            </label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="api" id="api-claude" value="claude" checked>
                                <label class="btn btn-outline-primary" for="api-claude">
                                    <i class="bi bi-robot me-2"></i>Claude AI
                                    <small class="d-block text-muted">Sonnet 4.5</small>
                                </label>
                                
                                <input type="radio" class="btn-check" name="api" id="api-chatgpt" value="chatgpt">
                                <label class="btn btn-outline-success" for="api-chatgpt">
                                    <i class="bi bi-chat-dots me-2"></i>ChatGPT
                                    <small class="d-block text-muted">GPT-4o</small>
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Zdrojový jazyk -->
                            <div class="col-md-6 mb-3">
                                <label for="from" class="form-label fw-bold">
                                    <i class="bi bi-flag me-2"></i>Ze jazyka
                                </label>
                                <select class="form-select" id="from" name="from">
                                    <option value="cs" selected>🇨🇿 Čeština</option>
                                    <option value="en">🇬🇧 Angličtina</option>
                                    <option value="pl">🇵🇱 Polština</option>
                                    <option value="de">🇩🇪 Němčina</option>
                                    <option value="sk">🇸🇰 Slovenština</option>
                                    <option value="fr">🇫🇷 Francouzština</option>
                                    <option value="es">🇪🇸 Španělština</option>
                                    <option value="it">🇮🇹 Italština</option>
                                    <option value="ru">🇷🇺 Ruština</option>
                                    <option value="uk">🇺🇦 Ukrajinština</option>
                                </select>
                            </div>

                            <!-- Cílový jazyk -->
                            <div class="col-md-6 mb-3">
                                <label for="to" class="form-label fw-bold">
                                    <i class="bi bi-flag-fill me-2"></i>Do jazyka
                                </label>
                                <select class="form-select" id="to" name="to">
                                    <option value="pl" selected>🇵🇱 Polština</option>
                                    <option value="en">🇬🇧 Angličtina</option>
                                    <option value="cs">🇨🇿 Čeština</option>
                                    <option value="de">🇩🇪 Němčina</option>
                                    <option value="sk">🇸🇰 Slovenština</option>
                                    <option value="fr">🇫🇷 Francouzština</option>
                                    <option value="es">🇪🇸 Španělština</option>
                                    <option value="it">🇮🇹 Italština</option>
                                    <option value="ru">🇷🇺 Ruština</option>
                                    <option value="uk">🇺🇦 Ukrajinština</option>
                                </select>
                            </div>
                        </div>

                        <!-- Vstupní text -->
                        <div class="mb-3">
                            <label for="text" class="form-label fw-bold">
                                <i class="bi bi-file-earmark-code me-2"></i>HTML text k překladu
                            </label>
                            <textarea 
                                class="form-control font-monospace" 
                                id="text" 
                                name="text" 
                                rows="10" 
                                placeholder="<p>Vložte HTML text k překladu...</p>"
                                required
                            ></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Můžete vložit HTML s tagy. Přeloží se pouze textový obsah.
                            </div>
                        </div>

                        <!-- Tlačítko -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="translateBtn">
                                <i class="bi bi-translate me-2"></i>Přeložit text
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Výsledek -->
            <div class="card shadow-sm mt-4" id="resultCard" style="display: none;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-check-circle me-2"></i>Přeložený text
                    </span>
                    <button type="button" class="btn btn-sm btn-light" id="copyBtn">
                        <i class="bi bi-clipboard me-1"></i>Kopírovat
                    </button>
                </div>
                <div class="card-body">
                    <pre class="mb-0" id="result"></pre>
                </div>
            </div>

            <!-- Loading spinner -->
            <div class="text-center mt-4" id="loadingSpinner" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Překládám...</span>
                </div>
                <p class="mt-3 text-muted">Překládám text pomocí AI...</p>
            </div>

            <!-- Error alert -->
            <div class="alert alert-danger mt-4" id="errorAlert" style="display: none;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Chyba:</strong> <span id="errorMessage"></span>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('translateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const api = form.querySelector('input[name="api"]:checked').value;
    const text = form.querySelector('#text').value;
    const from = form.querySelector('#from').value;
    const to = form.querySelector('#to').value;
    
    // UI elements
    const translateBtn = document.getElementById('translateBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultCard = document.getElementById('resultCard');
    const errorAlert = document.getElementById('errorAlert');
    const result = document.getElementById('result');
    
    // Hide previous results
    resultCard.style.display = 'none';
    errorAlert.style.display = 'none';
    
    // Show loading
    translateBtn.disabled = true;
    loadingSpinner.style.display = 'block';
    
    try {
        const endpoint = api === 'claude' ? '{{ route("translate.claude") }}' : '{{ route("translate.chatgpt") }}';
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ text, from, to })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Chyba při překladu');
        }
        
        // Show result
        result.textContent = data.translated;
        resultCard.style.display = 'block';
        
        // Scroll to result
        resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
    } catch (error) {
        // Show error
        document.getElementById('errorMessage').textContent = error.message;
        errorAlert.style.display = 'block';
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } finally {
        // Hide loading
        translateBtn.disabled = false;
        loadingSpinner.style.display = 'none';
    }
});

// Copy to clipboard
document.getElementById('copyBtn').addEventListener('click', function() {
    const result = document.getElementById('result').textContent;
    navigator.clipboard.writeText(result).then(() => {
        const btn = this;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Zkopírováno!';
        btn.classList.remove('btn-light');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-light');
        }, 2000);
    });
});
</script>
@endpush