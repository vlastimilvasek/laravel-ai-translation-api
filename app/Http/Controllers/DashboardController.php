<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tokens = $user->tokens;

        return view('dashboard', compact('user', 'tokens'));
    }

    public function createToken(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $token = Auth::user()->createToken($request->token_name);

        return redirect()->route('dashboard')
            ->with('token', $token->plainTextToken)
            ->with('success', 'API token byl úspěšně vytvořen. Uložte si ho, protože jej již neuvidíte!');
    }

    public function revokeToken(Request $request, $tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('dashboard')
            ->with('success', 'API token byl úspěšně odstraněn.');
    }
}
