<?php

namespace App\Modules\Calendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\CalendarEventRegistry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function events(Request $request, CalendarEventRegistry $registry): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'sources' => 'nullable|string',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $sources = $request->sources ? explode(',', $request->sources) : null;

        $events = $registry->getEvents($request->user(), $start, $end, $sources);

        return response()->json([
            'data' => array_map(fn ($e) => $e->toArray(), $events),
            'sources' => $registry->availableSources($request->user()),
        ]);
    }
}
