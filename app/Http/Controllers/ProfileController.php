<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Usuario;
use Illuminate\Http\Request;
use App\Models\Proyecto;

class ProfileController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        try {
            $usuario = $request->user();

            // Todo va directo a tabla 'usuarios'
            $datosUsuario = [];

            if ($request->filled('nombre'))
                $datosUsuario['nombre'] = $request->nombre;
            if ($request->filled('apellido'))
                $datosUsuario['apellido'] = $request->apellido;
            if ($request->filled('biografia'))
                $datosUsuario['biografia'] = $request->biografia;
            if ($request->filled('ubicacion'))
                $datosUsuario['ubicacion'] = $request->ubicacion;
            if ($request->filled('fecha_nacimiento'))
                $datosUsuario['fecha_nacimiento'] = $request->fecha_nacimiento;

            if ($request->hasFile('foto_perfil')) {
                $datosUsuario['foto_perfil'] = $request->file('foto_perfil')
                    ->store('fotos_perfil', 'public');
            }
            if ($request->hasFile('foto_portada')) {
                $datosUsuario['foto_portada'] = $request->file('foto_portada')
                    ->store('fotos_portada', 'public');
            }

            if (!empty($datosUsuario)) {
                $usuario->update($datosUsuario);
            }

            // Redes sociales van a tabla 'social'
            foreach (['github', 'linkedin'] as $red) {
                if ($request->filled($red)) {
                    \App\Models\Social::updateOrCreate(
                        [
                            'usuario_id'        => $usuario->id,
                            'nombre_plataforma' => $red,
                        ],
                        ['url_plataforma' => $request->$red]
                    );
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Perfil actualizado correctamente',
                'data'    => $usuario->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function show(Request $request)
    {
        $usuario = $request->user();

        // Traer redes sociales
        $github   = \App\Models\Social::where('usuario_id', $usuario->id)
            ->where('nombre_plataforma', 'github')
            ->first();
        $linkedin = \App\Models\Social::where('usuario_id', $usuario->id)
            ->where('nombre_plataforma', 'linkedin')
            ->first();

        return response()->json([
            'nombre'             => $usuario->nombre          ?? '',
            'apellido'           => $usuario->apellido        ?? '',
            'email'              => $usuario->email           ?? '',
            'biografia'          => $usuario->biografia       ?? '',
            'ubicacion'          => $usuario->ubicacion       ?? '',
            'fecha_nacimiento'   => $usuario->fecha_nacimiento ?? '',
            'foto_perfil'        => $usuario->foto_perfil     ?? '',
            'foto_portada'       => $usuario->foto_portada    ?? '',
            'perfil_completado'  => $usuario->perfil_completado ?? 0,
            'github'             => $github?->url_plataforma  ?? '',
            'linkedin'           => $linkedin?->url_plataforma ?? '',
        ]);
    }
    public function completar(Request $request)
    {
        try {
            $usuario = $request->user();
            $datos = [];

            if ($request->filled('biografia'))
                $datos['biografia'] = $request->biografia;

            if ($request->filled('ubicacion'))
                $datos['ubicacion'] = $request->ubicacion;

            // foto perfil
            if ($request->hasFile('foto_perfil')) {
                $path = $request->file('foto_perfil')->store('fotos_perfil', 'public');
                $datos['foto_perfil'] = $path;
            }

            $datos['perfil_completado'] = 1;

            // guarda en usuarios
            $usuario->update($datos);

            return response()->json([
                'message' => 'Perfil completado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function crearPerfilProfesional(Request $request)
    {
        try {
            $usuario = $request->user();
            // Verificar que el perfil básico esté completo
            if (!$usuario->perfil_completado) {
                return response()->json([
                    'message' => 'Primero debe completar su perfil básico'
                ], 400);
            }
            // Actualizar campos de perfil en tabla 'usuarios'
            $datosUsuario = [];
            if ($request->filled('biografia'))
                $datosUsuario['biografia'] = $request->biografia;
            if ($request->filled('ubicacion'))
                $datosUsuario['ubicacion'] = $request->ubicacion;
            if ($request->filled('fecha_nacimiento'))
                $datosUsuario['fecha_nacimiento'] = $request->fecha_nacimiento;
            if ($request->hasFile('foto_perfil')) {
                $datosUsuario['foto_perfil'] = $request->file('foto_perfil')
                    ->store('fotos_perfil', 'public');
            }

            if (!empty($datosUsuario)) {
                $usuario->update($datosUsuario);
            }

            // Guardar redes sociales en tabla 'social'
            $redes = [
                'github'   => $request->github,
                'linkedin' => $request->linkedin,
            ];

            foreach ($redes as $plataforma => $url) {
                if (!empty($url)) {
                    \App\Models\Social::updateOrCreate(
                        [
                            'usuario_id'        => $usuario->id,
                            'nombre_plataforma' => $plataforma,
                        ],
                        ['url_plataforma' => $url]
                    );
                }
            }

            return response()->json([
                'message' => 'Perfil profesional actualizado correctamente',
                'usuario' => $usuario->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // 🔍 BÚSQUEDA DE USUARIOS
    public function searchUsers(Request $request)
    {
        try {
            $query = trim($request->query('q', ''));

            if ($query === '') {
                return response()->json([]);
            }

            // 🔹 Normalizar espacios múltiples
            $words = preg_split('/\s+/', $query);

            $usersQuery = Usuario::query();

            // 🔹 CASO 1: una sola palabra
            if (count($words) === 1) {
                $term = $words[0];

                $usersQuery->where(function ($q) use ($term) {
                    $q->where('nombre', 'LIKE', "%{$term}%")
                        ->orWhere('apellido', 'LIKE', "%{$term}%")
                        ->orWhereHas('habilidades', function ($h) use ($term) {
                            $h->where('nombre', 'LIKE', "%{$term}%");
                        });
                });
            }

            // 🔹 CASO 2: nombre + apellido
            elseif (count($words) === 2) {
                [$nombre, $apellido] = $words;
                $usersQuery->where('nombre', 'LIKE', "%{$nombre}%")
                    ->where('apellido', 'LIKE', "%{$apellido}%");
            }

            // 🔹 CASO 3: nombre + múltiples apellidos
            else {
                $nombre = array_shift($words);
                $apellido = implode(' ', $words);

                $usersQuery->where('nombre', 'LIKE', "%{$nombre}%")
                    ->where('apellido', 'LIKE', "%{$apellido}%");
            }

            // 🔹 Traer habilidades
            $users = $usersQuery
                ->with('habilidades')
                ->limit(20)
                ->get();

            // 🔹 Formato para frontend
            $result = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->nombre,
                    'lastName' => $user->apellido,
                    'photo' => $user->foto_perfil,
                    'bio' => $user->biografia,
                    'skills' => $user->habilidades->pluck('nombre')->values(),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en la búsqueda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = trim($request->query('q', ''));
            $category = $request->query('category', 'usuario');
            $filter = $request->query('filter', 'nombre');

            if ($query === '') {
                return response()->json([]);
            }

            switch ($category) {

                case 'usuario':
                    return $this->searchUsuarios($query, $filter);

                case 'proyecto':
                    return $this->searchUsuariosPorProyecto($query, $filter);

                case 'habilidad':
                    return $this->searchUsuariosPorHabilidad($query, $filter);
                default:
                    return response()->json([]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en búsqueda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function searchUsuariosPorHabilidad($query, $filter)
    {
        // 1. Buscar en tabla habilidades
        $habilidadesQuery = \App\Models\Habilidad::query();

        switch ($filter) {

            // 🔹 Buscar por nombre (cualquier tipo)
            case 'nombre':
                $habilidadesQuery->where('nombre', 'LIKE', "%{$query}%");
                break;

            // 🔹 Solo técnicas
            case 'tecnica':
                $habilidadesQuery->where('tipo', 'tecnica')
                    ->where('nombre', 'LIKE', "%{$query}%");
                break;

            // 🔹 Solo blandas
            case 'blanda':
                $habilidadesQuery->where('tipo', 'blanda')
                    ->where('nombre', 'LIKE', "%{$query}%");
                break;
        }

        // 2. Obtener IDs de usuarios SIN duplicados
        $usuarioIds = $habilidadesQuery
            ->pluck('usuario_id')
            ->unique();

        // 3. Buscar usuarios
        $usuarios = Usuario::whereIn('id', $usuarioIds)
            ->with('habilidades')
            ->limit(20)
            ->get();

        // 4. Formato estándar (MUY IMPORTANTE)
        return response()->json(
            $usuarios->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->nombre,
                    'lastName' => $user->apellido,
                    'photo' => $user->foto_perfil,
                    'bio' => $user->biografia,
                    'skills' => $user->habilidades->pluck('nombre')->values(),
                ];
            })
        );
    }

    private function searchUsuarios($query, $filter)
    {
        $usuariosQuery = Usuario::query();

        switch ($filter) {

            // 🔹 Nombre y apellido
            case 'nombre':
                $words = preg_split('/\s+/', trim($query));

                if (count($words) === 1) {
                    $term = $words[0];

                    $usuariosQuery->where(function ($q) use ($term) {
                        $q->where('nombre', 'LIKE', "%{$term}%")
                            ->orWhere('apellido', 'LIKE', "%{$term}%");
                    });
                } else {
                    $nombre = array_shift($words);
                    $apellido = implode(' ', $words);

                    $usuariosQuery->where('nombre', 'LIKE', "%{$nombre}%")
                        ->where('apellido', 'LIKE', "%{$apellido}%");
                }
                break;

            // 🔹 Biografía
            case 'bio':
                $usuariosQuery->where('biografia', 'LIKE', "%{$query}%");
                break;

            // 🔹 Ubicación
            case 'ubicacion':
                $usuariosQuery->where('ubicacion', 'LIKE', "%{$query}%");
                break;
        }

        $usuarios = $usuariosQuery
            ->with('habilidades')
            ->limit(20)
            ->get();

        return response()->json(
            $usuarios->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->nombre,
                    'lastName' => $user->apellido,
                    'photo' => $user->foto_perfil,
                    'bio' => $user->biografia,
                    'skills' => $user->habilidades->pluck('nombre')->values(),
                ];
            })
        );
    }

    private function searchUsuariosPorProyecto($query, $filter)
    {
        // 1. Buscar proyectos
        $proyectosQuery = Proyecto::query();

        switch ($filter) {

            case 'nombre': // título del proyecto
                $proyectosQuery->where('titulo', 'LIKE', "%{$query}%");
                break;

            case 'tecnologia':
                $proyectosQuery->where('tecnologias', 'LIKE', "%{$query}%");
                break;

            case 'descripcion':
                $proyectosQuery->where('descripcion', 'LIKE', "%{$query}%");
                break;
        }

        // 2. Obtener IDs de usuarios SIN duplicados
        $usuarioIds = $proyectosQuery
            ->pluck('usuario_id')
            ->unique();

        // 3. Buscar usuarios con esos IDs
        $usuarios = Usuario::whereIn('id', $usuarioIds)
            ->with('habilidades')
            ->limit(20)
            ->get();

        // 4. Formato igual que SIEMPRE (IMPORTANTE)
        return response()->json(
            $usuarios->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->nombre,
                    'lastName' => $user->apellido,
                    'photo' => $user->foto_perfil,
                    'bio' => $user->biografia,
                    'skills' => $user->habilidades->pluck('nombre')->values(),
                ];
            })
        );
    }
}
