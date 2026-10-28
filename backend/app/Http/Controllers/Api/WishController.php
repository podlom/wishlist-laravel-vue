<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class WishController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Wish::query()->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $wish = Wish::create($data);

        return response()->json($wish, 201);
    }

    public function show(Wish $wish): JsonResponse
    {
        return response()->json($wish);
    }

    public function update(Request $request, Wish $wish): JsonResponse
    {
        $data = $request->validate(
            $this->rules(partial: true)
        );

        $wish->update($data);

        return response()->json($wish->refresh());
    }

    public function destroy(Wish $wish): Response
    {
        $wish->delete();

        return response()->noContent();
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:100'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'image_url' => ['nullable', 'url'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'original_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'is_purchased' => ['sometimes', 'boolean'],
        ];
    }
}