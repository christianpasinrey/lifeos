<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\HabitTemplate;
use Illuminate\Http\Request;

class HabitTemplateController extends Controller
{
    public function __construct(private HabitService $service) {}

    public function index(Request $request)
    {
        $this->authorize('feature:habits.templates');

        $templates = HabitTemplate::orderBy('sort_order')->get()->groupBy('category');

        return response()->json($templates);
    }

    public function apply(Request $request, HabitTemplate $template)
    {
        $this->authorize('feature:habits.templates');

        $habit = $this->service->create($request->user(), [
            'name' => $template->name,
            'description' => $template->description,
            'icon' => $template->icon,
            'color' => $template->color,
            'type' => $template->type,
            'unit' => $template->unit,
            'target_value' => $template->target_value,
            'frequency' => $template->frequency,
            'target_days' => $template->target_days,
        ]);

        return response()->json($habit, 201);
    }
}
