<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // GET /api/events
    public function index()
    {
        return Event::all();
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

        $event = Event::create($validated);
        return response()->json($event, 201);
    }

    // GET /api/events/{id}
    public function show(Event $event)
    {
        return $event;
    }

    // PUT/PATCH /api/events/{id}
    public function update(Request $request, Event $event)
    {
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
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(null, 204);
    }
}