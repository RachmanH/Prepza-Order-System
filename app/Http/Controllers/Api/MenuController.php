<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuAlias;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function adminIndex(): JsonResponse
    {
        $menus = Menu::query()
            ->with('aliases:id,menu_id,alias,normalized_alias')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price', 'is_active', 'created_at', 'updated_at']);

        return response()->json([
            'data' => $menus,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:menus,name'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:120'],
        ]);

        $name = Str::of($payload['name'])->squish()->toString();

        $menu = Menu::query()->create([
            'name' => $name,
            'slug' => $this->buildUniqueSlug($name),
            'price' => $payload['price'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->syncAliases($menu, $payload['aliases'] ?? []);

        $menu->load('aliases:id,menu_id,alias,normalized_alias');

        return response()->json([
            'status' => 'ok',
            'message' => 'Menu berhasil ditambahkan.',
            'data' => $menu,
        ], 201);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('menus', 'name')->ignore($menu->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:120'],
        ]);

        $name = Str::of($payload['name'])->squish()->toString();

        $menu->update([
            'name' => $name,
            'slug' => $menu->name === $name ? $menu->slug : $this->buildUniqueSlug($name, $menu->id),
            'price' => $payload['price'],
            'is_active' => (bool) ($payload['is_active'] ?? $menu->is_active),
        ]);

        if (array_key_exists('aliases', $payload)) {
            $this->syncAliases($menu, $payload['aliases'] ?? []);
        }

        $menu->load('aliases:id,menu_id,alias,normalized_alias');

        return response()->json([
            'status' => 'ok',
            'message' => 'Menu berhasil diperbarui.',
            'data' => $menu,
        ]);
    }

    public function toggle(Menu $menu): JsonResponse
    {
        $menu->update([
            'is_active' => ! $menu->is_active,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Status menu berhasil diubah.',
            'data' => [
                'id' => $menu->id,
                'is_active' => $menu->is_active,
            ],
        ]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->aliases()->delete();
        $menu->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Menu berhasil dihapus.',
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

    private function buildUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (
            Menu::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $aliases
     */
    private function syncAliases(Menu $menu, array $aliases): void
    {
        $normalized = collect($aliases)
            ->map(fn ($alias) => Str::of((string) $alias)->lower()->squish()->toString())
            ->filter(fn (string $alias) => $alias !== '')
            ->unique()
            ->values();

        MenuAlias::query()->where('menu_id', $menu->id)->delete();

        foreach ($normalized as $alias) {
            $menu->aliases()->create([
                'alias' => $alias,
                'normalized_alias' => $alias,
            ]);
        }
    }
}
