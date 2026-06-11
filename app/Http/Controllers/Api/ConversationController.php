<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\TypingStarted;
use App\Events\TypingStopped;
use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $conversations = Conversation::query()
            ->with(['participants', 'listing.book', 'latestMessage.sender'])
            ->whereHas('participants', fn ($query) => $query->where('users.id', $request->user()->id))
            ->latest('updated_at')
            ->paginate($this->perPage($request));

        return $this->paginated($conversations, ConversationResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'participant_id' => ['nullable', 'integer', 'exists:users,id'],
            'listing_id' => ['nullable', 'integer', 'exists:listings,id'],
        ]);

        $participantId = $data['participant_id'] ?? null;
        $listing = null;

        if (! empty($data['listing_id'])) {
            $listing = Listing::query()->findOrFail($data['listing_id']);
            $participantId ??= $listing->seller_id;
        }

        abort_if(! $participantId, 422, 'Informe participant_id ou listing_id.');
        abort_if((int) $participantId === $request->user()->id, 422, 'A conversa precisa ter outro participante.');

        $participant = User::query()->findOrFail($participantId);

        $conversation = Conversation::query()->create([
            'created_by' => $request->user()->id,
            'listing_id' => $listing?->id,
        ]);

        $conversation->participants()->syncWithoutDetaching([$request->user()->id, $participant->id]);

        return $this->created(new ConversationResource($conversation->load(['participants', 'listing.book'])));
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($messages, MessageResource::class);
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);
        $conversation->touch();

        broadcast(new MessageSent($message))->toOthers();

        return $this->created(new MessageResource($message->load('sender')));
    }

    public function read(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);

        if ($message->sender_id !== $request->user()->id && ! $message->read_at) {
            $message->update(['read_at' => now()]);
            broadcast(new MessageRead($message))->toOthers();
        }

        return $this->ok(new MessageResource($message->refresh()->load('sender')));
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'typing' => ['required', 'boolean'],
        ]);

        $event = $data['typing']
            ? new TypingStarted($conversation->id, $request->user())
            : new TypingStopped($conversation->id, $request->user());

        broadcast($event)->toOthers();

        return $this->message('Evento enviado.');
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()->where('users.id', $request->user()->id)->exists(),
            403
        );
    }
}
