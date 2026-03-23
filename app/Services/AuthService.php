<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $data): array
    {
        $usuario = Usuario::where('email', $data['email'])->first();

        if (!$usuario || !Hash::check($data['password'], $usuario->password)) {
            throw new \Exception('Credenciales incorrectas');
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $usuario,
            'token' => $token,
        ];
    }

    public function register(array $data): array
    {
        $usuario = Usuario::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $usuario,
            'token' => $token,
        ];
    }
}
