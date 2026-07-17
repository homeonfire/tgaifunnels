<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiKeyController extends Controller
{
    public function index()
    {
        return response()->json(AiKey::latest()->get());
    }

    public function store(Request $request)
    {
        $key = AiKey::create($request->only(['name', 'provider', 'token']));
        return response()->json($key, 201);
    }

    public function update(Request $request, $id)
    {
        $key = AiKey::findOrFail($id);
        $key->update($request->only(['name', 'provider', 'token']));
        return response()->json($key);
    }

    public function destroy($id)
    {
        AiKey::destroy($id);
        return response()->json(['message' => 'Ключ удален']);
    }

    public function getModels($id)
    {
        $key = AiKey::findOrFail($id);
        
        // Определяем базовый URL в зависимости от провайдера
        $baseUrl = $key->provider === 'openai' 
            ? 'https://api.openai.com/v1/models' 
            : 'https://api.deepseek.com/models';

        try {
            $response = Http::withToken($key->token)->timeout(10)->get($baseUrl);

            if ($response->successful()) {
                // OpenAI и DeepSeek возвращают массив моделей внутри ключа 'data'
                $models = $response->json('data');
                
                // Вытаскиваем только названия моделей (id)
                $modelNames = collect($models)->pluck('id')->sort()->values();
                
                return response()->json($modelNames);
            }

            return response()->json(['error' => 'Не удалось получить список моделей от провайдера'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка соединения с API'], 500);
        }
    }
}