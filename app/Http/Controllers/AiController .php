<?php

use App\Services\ClaudeService;

class AiController extends Controller
{
    public function chat(Request $request, ClaudeService $ai)
    {
        $reply = $ai->ask($request->input('message'));

        return response()->json($reply);
    }
}
