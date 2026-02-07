<?php

namespace App\Http\Controllers;

use App\Events\BuzzStateUpdated;
use App\Services\BuzzService;
use Illuminate\Http\Request;

class BuzzController extends Controller
{
    public function __construct(private BuzzService $buzz)
    {
    }

    public function state()
    {
        return response()->json($this->buzz->getState());
    }

    public function press(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|string|max:128',
            'name' => 'required|string|max:64',
        ]);

        $state = $this->buzz->press($data['client_id'], $data['name']);
        broadcast(new BuzzStateUpdated($state));

        return response()->json($state);
    }

    public function reset(Request $request)
    {
        $token = $request->header('X-Admin-Token');
        if (!$token || $token !== env('ADMIN_TOKEN')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $state = $this->buzz->reset();
        broadcast(new BuzzStateUpdated($state));

        return response()->json($state);
    }

    public function hardReset(Request $request)
    {
        $token = $request->header('X-Admin-Token');
        if (!$token || $token !== env('ADMIN_TOKEN')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $state = $this->buzz->hardReset();
        broadcast(new BuzzStateUpdated($state));

        return response()->json($state);
    }
}
