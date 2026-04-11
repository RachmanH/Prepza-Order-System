<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MenuController extends Controller
{
    public function __construct(private readonly GroqService $groqService) {}

    public function index(): JsonResponse
    {
        $menus = Menu::query()
            ->where('is_active', true)
            ->with('aliases:id,menu_id,alias,normalized_alias')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price']);

        return response()->json([
            'data' => $menus,
        ]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'candidate' => ['required', 'string', 'max:200'],
        ]);

        $candidate = Str::of($payload['candidate'])->lower()->squish()->toString();

        if ($candidate === '') {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Kandidat menu tidak boleh kosong.',
                'data' => null,
            ], 422);
        }

        $menus = Menu::query()
            ->where('is_active', true)
            ->with('aliases:id,menu_id,alias,normalized_alias')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price']);

        $resolved = $this->resolveMenuCandidate($candidate, $menus);

        if (! $resolved) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Menu tidak ditemukan.',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'data' => [
                'matched_name' => $resolved['menu']->name,
                'source' => $resolved['source'],
                'menu' => [
                    'id' => $resolved['menu']->id,
                    'name' => $resolved['menu']->name,
                    'slug' => $resolved['menu']->slug,
                    'price' => $resolved['menu']->price,
                ],
            ],
        ]);
    }

    /**
     * @return array{menu: Menu, source: string}|null
     */
    private function resolveMenuCandidate(string $candidate, Collection $menus): ?array
    {
        $nameIndex = $menus->keyBy(fn (Menu $menu) => Str::lower($menu->name));
        $aliasIndex = $menus
            ->flatMap(function (Menu $menu): Collection {
                return $menu->aliases->mapWithKeys(function ($alias) use ($menu): array {
                    return [Str::lower($alias->normalized_alias) => $menu];
                });
            });

        $menu = $nameIndex->get($candidate);

        if ($menu) {
            return ['menu' => $menu, 'source' => 'exact'];
        }

        $menu = $aliasIndex->get($candidate);

        if ($menu) {
            return ['menu' => $menu, 'source' => 'alias'];
        }

        $menu = $this->findByFuzzyMatch($candidate, $menus);

        if ($menu) {
            return ['menu' => $menu, 'source' => 'fuzzy'];
        }

        try {
            $matchedName = $this->groqService->matchMenuCandidate($candidate, $menus->pluck('name')->all());
        } catch (Throwable $exception) {
            Log::warning('Groq menu resolution failed', [
                'message' => $exception->getMessage(),
            ]);

            $matchedName = null;
        }

        if (! $matchedName) {
            return null;
        }

        $menu = $nameIndex->get(Str::lower($matchedName));

        if (! $menu) {
            return null;
        }

        return ['menu' => $menu, 'source' => 'ai'];
    }

    private function findByFuzzyMatch(string $candidate, Collection $menus): ?Menu
    {
        $threshold = 80;
        $bestScore = 0;
        $bestMenu = null;

        foreach ($menus as $menu) {
            similar_text($candidate, Str::lower($menu->name), $nameScore);

            if ($nameScore > $bestScore) {
                $bestScore = $nameScore;
                $bestMenu = $menu;
            }

            foreach ($menu->aliases as $alias) {
                similar_text($candidate, Str::lower($alias->normalized_alias), $aliasScore);

                if ($aliasScore > $bestScore) {
                    $bestScore = $aliasScore;
                    $bestMenu = $menu;
                }
            }
        }

        return $bestScore >= $threshold ? $bestMenu : null;
    }
}
