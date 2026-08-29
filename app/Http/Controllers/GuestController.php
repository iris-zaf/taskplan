<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    // GET /api/events/{event}/guests
    public function index(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        return $event->guests()->orderBy('name')->get();
    }

    // POST /api/events/{event}/guests
    public function store(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $guest = $event->guests()->create($validated);

        return response()->json($guest, 201);
    }

    // DELETE /api/events/{event}/guests/{guest}
    public function destroy(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEvent($request, $event);
        abort_if($guest->event_id !== $event->id, 404);

        $guest->delete();

        return response()->json(null, 204);
    }

    // GET /api/guests/ticket/{code} (public, no auth — this is the guest's own link)
    public function ticket(string $code)
    {
        $guest = Guest::where('code', $code)->with('event')->first();

        abort_if(! $guest, 404);

        return response()->json([
            'name' => $guest->name,
            'code' => $guest->code,
            'checked_in_at' => $guest->checked_in_at,
            'event' => [
                'title' => $guest->event->title,
                'event_date' => $guest->event->event_date,
                'location' => $guest->event->location,
            ],
        ]);
    }

    // POST /api/guests/checkin
    public function checkin(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $guest = Guest::where('code', $validated['code'])->with('event')->first();

        abort_if(! $guest || $guest->event->user_id !== $request->user()->id, 404);

        $alreadyCheckedIn = $guest->checked_in_at !== null;

        if (! $alreadyCheckedIn) {
            $guest->checked_in_at = now();
            $guest->save();
        }

        return response()->json([
            'guest' => $guest->fresh(),
            'event' => $guest->event,
            'already_checked_in' => $alreadyCheckedIn,
        ]);
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_if($event->user_id !== $request->user()->id, 404);
    }
}
