<?php

namespace App\Modules\Calendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\CalendarService;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Calendar\Requests\StoreCalendarEventRequest;
use App\Modules\Calendar\Requests\UpdateCalendarEventRequest;
use App\Modules\Calendar\Resources\CalendarEventResource;
use Illuminate\Http\JsonResponse;

class CalendarEventController extends Controller
{
    public function __construct(private CalendarService $service) {}

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $event = $this->service->createEvent($request->user(), $request->validated());

        return response()->json([
            'data' => new CalendarEventResource($event),
        ], 201);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        abort_if($calendarEvent->user_id !== $request->user()->id, 403);

        $event = $this->service->updateEvent($calendarEvent, $request->validated());

        return response()->json([
            'data' => new CalendarEventResource($event),
        ]);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        abort_if($calendarEvent->user_id !== request()->user()->id, 403);

        $this->service->deleteEvent($calendarEvent);

        return response()->json(['message' => 'Evento eliminado']);
    }
}
