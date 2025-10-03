@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <div class="row mt-4">
        <div class="col-md-12">
            <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
            <p class="text-muted">Vítejte, {{ $user->name }}!</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('token'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Váš nový API token:</h5>
            <p class="mb-2"><code class="user-select-all">{{ session('token') }}</code></p>
            <hr>
            <p class="mb-0"><small>Zkopírujte si tento token hned teď! Z bezpečnostních důvodů jej již neuvidíte.</small></p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- User Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Informace o účtu</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Jméno:</strong> {{ $user->name }}
                    </div>
                    <div class="mb-2">
                        <strong>Email:</strong> {{ $user->email }}
                    </div>
                    @if($user->provider)
                        <div class="mb-2">
                            <strong>Přihlášen přes:</strong>
                            <span class="badge bg-info">
                                @if($user->provider === 'google')
                                    <i class="bi bi-google me-1"></i>Google
                                @elseif($user->provider === 'facebook')
                                    <i class="bi bi-facebook me-1"></i>Facebook
                                @else
                                    {{ ucfirst($user->provider) }}
                                @endif
                            </span>
                        </div>
                    @endif
                    <div class="mb-2">
                        <strong>Registrován:</strong> {{ $user->created_at->format('d.m.Y H:i') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistiky</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Aktivních API tokenů:</strong> {{ $tokens->count() }}
                    </div>
                    <div class="mb-2">
                        <strong>Poslední aktivita:</strong> {{ $user->updated_at->format('d.m.Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Tokens -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-key me-2"></i>API Tokeny</h5>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#createTokenModal">
                        <i class="bi bi-plus-circle me-1"></i>Vytvořit nový token
                    </button>
                </div>
                <div class="card-body">
                    @if($tokens->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Název</th>
                                        <th>Vytvořeno</th>
                                        <th>Poslední použití</th>
                                        <th>Akce</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tokens as $token)
                                        <tr>
                                            <td><strong>{{ $token->name }}</strong></td>
                                            <td>{{ $token->created_at->format('d.m.Y H:i') }}</td>
                                            <td>{{ $token->last_used_at ? $token->last_used_at->format('d.m.Y H:i') : 'Nikdy' }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('tokens.revoke', $token->id) }}" class="d-inline" onsubmit="return confirm('Opravdu chcete tento token odstranit?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash me-1"></i>Odstranit
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>Nemáte žádné aktivní API tokeny. Vytvořte si jeden pro přístup k API.
                        </div>
                    @endif

                    <hr>

                    <h6>Jak používat API tokeny?</h6>
                    <p class="text-muted mb-2">API tokeny slouží k autentizaci vašich požadavků na API endpointy. Přidejte token do hlavičky požadavku:</p>
                    <pre class="bg-light p-3 rounded"><code>curl -X POST {{ url('/api/v1/translate/claude') }} \
  -H "Authorization: Bearer VÁŠ_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"text": "&lt;p&gt;Text k překladu&lt;/p&gt;", "from": "cs", "to": "pl"}'</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Token Modal -->
<div class="modal fade" id="createTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('tokens.create') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Vytvořit nový API token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="token_name" class="form-label">Název tokenu</label>
                        <input type="text"
                               class="form-control"
                               id="token_name"
                               name="token_name"
                               placeholder="např. Mobilní aplikace"
                               required>
                        <small class="text-muted">Pojmenujte token podle účelu (např. "Web", "Mobilní app", "Testování")</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Vytvořit token</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
