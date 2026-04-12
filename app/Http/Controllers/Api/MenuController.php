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
use Illuminate\Support\Facades\Storage;
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
            ->with('aliases:id,menu_id,alias,normalized_alias', 'category:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'image_path', 'image_url', 'price', 'category_id']);

        return response()->json([
            'data' => $menus->map(fn (Menu $menu): array => $this->serializeMenu($menu))->values(),
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
            ->with('aliases:id,menu_id,alias,normalized_alias', 'category:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'image_path', 'image_url', 'price', 'category_id']);

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
                'menu' => $this->serializeMenu($resolved['menu']),
            ],
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $menus = Menu::query()
            ->with('aliases:id,menu_id,alias,normalized_alias', 'category:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'image_path', 'image_url', 'price', 'is_active', 'category_id', 'created_at', 'updated_at']);

        return response()->json([
            'data' => $menus->map(fn (Menu $menu): array => $this->serializeMenu($menu, true))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:menus,name'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:120'],
        ]);

        $name = Str::of($payload['name'])->squish()->toString();
        $storedImagePath = $request->hasFile('image_file')
            ? $request->file('image_file')->store('menus', 'public')
            : null;

        $menu = Menu::query()->create([
            'name' => $name,
            'slug' => $this->buildUniqueSlug($name),
            'category_id' => $payload['category_id'],
            'description' => Str::of((string) ($payload['description'] ?? ''))->squish()->toString() ?: null,
            'image_path' => $storedImagePath,
            'image_url' => $storedImagePath ? null : ($payload['image_url'] ?? null),
            'price' => $payload['price'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->syncAliases($menu, $payload['aliases'] ?? []);

        $menu->load('aliases:id,menu_id,alias,normalized_alias', 'category:id,name,slug');

        return response()->json([
            'status' => 'ok',
            'message' => 'Menu berhasil ditambahkan.',
            'data' => $this->serializeMenu($menu, true),
        ], 201);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('menus', 'name')->ignore($menu->id)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:120'],
        ]);

        $name = Str::of($payload['name'])->squish()->toString();
        $removeImage = (bool) ($payload['remove_image'] ?? false);

        if ($removeImage && $menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
            $menu->image_path = null;
            $menu->image_url = null;
        }

        if ($request->hasFile('image_file')) {
            if ($menu->image_path) {
                Storage::disk('public')->delete($menu->image_path);
            }

            $menu->image_path = $request->file('image_file')->store('menus', 'public');
            $menu->image_url = null;
        } elseif (array_key_exists('image_url', $payload)) {
            $menu->image_url = $payload['image_url'];
            if ($payload['image_url']) {
                $menu->image_path = null;
            }
        }

        $menu->update([
            'name' => $name,
            'slug' => $menu->name === $name ? $menu->slug : $this->buildUniqueSlug($name, $menu->id),
            'category_id' => $payload['category_id'],
            'description' => Str::of((string) ($payload['description'] ?? ''))->squish()->toString() ?: null,
            'image_path' => $menu->image_path,
            'image_url' => $menu->image_url,
            'price' => $payload['price'],
            'is_active' => (bool) ($payload['is_active'] ?? $menu->is_active),
        ]);

        if (array_key_exists('aliases', $payload)) {
            $this->syncAliases($menu, $payload['aliases'] ?? []);
        }

        $menu->load('aliases:id,menu_id,alias,normalized_alias', 'category:id,name,slug');

        return response()->json([
            'status' => 'ok',
            'message' => 'Menu berhasil diperbarui.',
            'data' => $this->serializeMenu($menu, true),
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

    public function removeImage(Menu $menu): JsonResponse
    {
        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->update([
            'image_path' => null,
            'image_url' => null,
        ]);

        $menu->load('aliases:id,menu_id,alias,normalized_alias');

        return response()->json([
            'status' => 'ok',
            'message' => 'Gambar menu berhasil dihapus.',
            'data' => $this->serializeMenu($menu, true),
        ]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

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

    private function serializeMenu(Menu $menu, bool $includeMeta = false): array
    {
        $data = [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'description' => $menu->description,
            'image_path' => $menu->image_path,
            'image_external_url' => $menu->image_url,
            'image_url' => $this->resolveMenuImageUrl($menu),
            'price' => $menu->price,
            'is_active' => (bool) $menu->is_active,
            'category_id' => $menu->category_id,
            'category' => [
                'id' => $menu->category?->id,
                'name' => $menu->category?->name,
                'slug' => $menu->category?->slug,
            ],
            'aliases' => $menu->aliases
                ->map(fn ($alias): array => [
                    'id' => $alias->id,
                    'alias' => $alias->alias,
                    'normalized_alias' => $alias->normalized_alias,
                ])
                ->values()
                ->all(),
        ];

        if ($includeMeta) {
            $data['created_at'] = $menu->created_at?->toIso8601String();
            $data['updated_at'] = $menu->updated_at?->toIso8601String();
        }

        return $data;
    }

    private function resolveMenuImageUrl(Menu $menu): ?string
    {
        if ($menu->image_path) {
            return Storage::url($menu->image_path);
        }

        return $menu->image_url;
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
