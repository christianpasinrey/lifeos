<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Requests\StoreBoardRequest;
use App\Modules\Tasks\Resources\BoardResource;
use App\Modules\Tasks\TaskService;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function __construct(private TaskService $service) {}

    public function index(Request $request)
    {
        $boards = $request->user()
            ->boards()
            ->orderBy('sort_order')
            ->get();

        return BoardResource::collection($boards);
    }

    public function store(StoreBoardRequest $request)
    {
        $board = $this->service->createBoard(
            $request->user(),
            $request->validated(),
        );

        return new BoardResource($board);
    }

    public function show(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $board->load('columns.tasks');

        return new BoardResource($board);
    }

    public function update(StoreBoardRequest $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $board->update($request->validated());

        return new BoardResource($board);
    }

    public function destroy(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $board->delete();

        return response()->json(['message' => 'Tablero eliminado']);
    }
}
