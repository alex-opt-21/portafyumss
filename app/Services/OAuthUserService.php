<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthUserService
{
    public function resolveOrCreateUser(
        string $provider,
        string $providerId,
        ?string $email,
        string $nombre,
        string $apellido = ''
    ): Usuario {
        $normalizedEmail = $email ? trim(mb_strtolower($email)) : null;

        $user = $normalizedEmail
            ? Usuario::where('email', $normalizedEmail)->first()
            : null;

        $user ??= Usuario::query()
            ->when(
                Schema::hasColumn('usuarios', 'proveedor'),
                fn ($query) => $query
                    ->where('proveedor', $provider)
                    ->where('proveedor_id', $providerId),
                fn ($query) => $query
                    ->where('provider', $provider)
                    ->where('provider_id', $providerId)
            )
            ->first();

        if ($user) {
            $updates = [];

            if (! $user->proveedor) {
                $updates['proveedor'] = $provider;
            }

            if (! $user->proveedor_id) {
                $updates['proveedor_id'] = $providerId;
            }

            if ($normalizedEmail && ! $user->email) {
                $updates['email'] = $normalizedEmail;
            }

            if (blank($user->nombre) && $nombre !== '') {
                $updates['nombre'] = $nombre;
            }

            if (blank($user->apellido) && $apellido !== '') {
                $updates['apellido'] = $apellido;
            }

            if ($updates !== []) {
                $user->update(Usuario::persistenceData($updates));
                $user->refresh();
            }

            return $user;
        }

        if (! $normalizedEmail) {
            throw new RuntimeException('No fue posible recuperar el correo del proveedor.');
        }

        return Usuario::create(Usuario::persistenceData([
            'email' => $normalizedEmail,
            'nombre' => $nombre !== '' ? $nombre : 'Usuario',
            'apellido' => $apellido,
            'proveedor' => $provider,
            'proveedor_id' => $providerId,
            'rol' => 'usuario',
            'password' => Hash::make(Str::random(24)),
        ]));
    }

    public function issueToken(Usuario $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}
