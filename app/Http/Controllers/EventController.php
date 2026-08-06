<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // GET /api/events
    public function index(Request $request)
    {
        return $request->user()->events()->get();
    }

    // POST /api/events
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'guest_count' => 'nullable|integer|min:0',
        ]);

        $event = $request->user()->events()->create($validated);
        return response()->json($event, 201);
    }

    // GET /api/events/{id}
    public function show(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        return $event;
    }

    // PUT/PATCH /api/events/{id}
    public function update(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'guest_count' => 'nullable|integer|min:0',
        ]);

        $event->update($validated);
        return response()->json($event);
    }

    // DELETE /api/events/{id}
    public function destroy(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $event->delete();
        return response()->json(null, 204);
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_if($event->user_id !== $request->user()->id, 404);
    }
}
