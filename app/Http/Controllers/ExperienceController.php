<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Experience::forUser($request->user()->id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get();

        return response()->json($items);
    }

    public function store(ExperienceRequest $request): JsonResponse
    {
        $experience = Experience::create([
            ...$request->persistenceData(),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($experience, 201);
    }

    public function update(ExperienceRequest $request, int $id): JsonResponse
    {
        $experience = Experience::forUser($request->user()->id)->findOrFail($id);
        $experience->update($request->persistenceData());

        return response()->json($experience->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $experience = Experience::forUser($request->user()->id)->findOrFail($id);
        $experience->delete();

        return response()->json(['message' => 'Experiencia eliminada correctamente']);
    }
}
