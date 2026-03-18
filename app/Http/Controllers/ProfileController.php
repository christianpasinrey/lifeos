<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()->only('id', 'name', 'email'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'user' => $user->only('id', 'name', 'email'),
        ]);
    }

    public function mcpTokenStatus(Request $request)
    {
        $token = $request->user()->tokens()->where('name', 'mcp')->first();

        return response()->json([
            'has_token' => $token !== null,
            'created_at' => $token?->created_at,
        ]);
    }

    public function generateMcpToken(Request $request)
    {
        $user = $request->user();

        // Revoke any existing MCP tokens
        $user->tokens()->where('name', 'mcp')->delete();

        $token = $user->createToken('mcp', ['mcp:*']);

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }

    public function revokeMcpToken(Request $request)
    {
        $request->user()->tokens()->where('name', 'mcp')->delete();

        return response()->json([
            'message' => 'MCP token revoked.',
        ]);
    }
}
