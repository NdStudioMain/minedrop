<?php

namespace App\Http\Controllers;

use App\Service\DiceService;
use App\Service\MinesService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        protected DiceService $diceService,
        protected MinesService $minesService
    ) {}

    public function dicePlay(Request $request)
    {
        $request->validate([
            'bet' => 'required|numeric|min:1',
            'chance' => 'required|numeric|min:1|max:99',
            'type' => 'required|string|in:over,under',
        ]);

        try {
            $result = $this->diceService->play(
                $request->user(),
                $request->bet,
                $request->chance,
                $request->type
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function minesStart(Request $request)
    {
        $request->validate([
            'bet' => 'required|numeric|min:1',
            'mines' => 'required|integer|min:1|max:24',
        ]);

        try {
            $result = $this->minesService->start(
                $request->user(),
                $request->bet,
                $request->mines
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function minesPick(Request $request)
    {
        $request->validate([
            'cellId' => 'required|integer|min:0|max:24',
        ]);

        try {
            $result = $this->minesService->pick($request->user(), $request->cellId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function minesCashout(Request $request)
    {
        try {
            $result = $this->minesService->cashout($request->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function minesMultipliers(Request $request)
    {
        $request->validate([
            'bet' => 'required|numeric|min:1',
            'mines' => 'required|integer|min:1|max:24',
        ]);

        try {
            $game = \App\Models\Games::where('id_game', 'mines')->first();
            $bank = $game ? $game->bank : \App\Models\Bank::first();

            $multipliers = $this->minesService->getAllMultipliers($bank, $request->bet, $request->mines);

            return response()->json(['multipliers' => $multipliers]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

