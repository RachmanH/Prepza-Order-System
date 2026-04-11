<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
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
}
