@extends('layouts.app')

@section('title', 'Přihlášení')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Přihlášení</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Heslo</label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Zapamatovat si mě
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Přihlásit se
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center mb-3">
                        <p class="text-muted">Nebo se přihlaste pomocí:</p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('social.login', 'google') }}" class="btn btn-outline-danger">
                            <i class="bi bi-google me-2"></i>Přihlásit se přes Google
                        </a>
                        <a href="{{ route('social.login', 'facebook') }}" class="btn btn-outline-primary">
                            <i class="bi bi-facebook me-2"></i>Přihlásit se přes Facebook
                        </a>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Nemáte účet? <a href="{{ route('register') }}">Zaregistrujte se</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
