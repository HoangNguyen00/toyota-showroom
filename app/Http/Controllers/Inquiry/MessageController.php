<?php

namespace App\Http\Controllers\Inquiry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inquiry\StoreMessageRequest;
use App\Models\Inquiry;
use App\Policies\MessagePolicy;
use App\Services\Inquiry\MessageData;
use App\Services\Inquiry\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Inquiry $inquiry)
    {
        abort_unless((new MessagePolicy)->viewHistory($request->user(), $inquiry), 403);
        $data = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = $data['limit'] ?? 50;
        $query = $inquiry->messages()->with('sender:id,name');
        if (isset($data['after_id'])) {
            $query->where('id', '>', $data['after_id'])->orderBy('id');
        } else {
            $query->when($data['before_id'] ?? null, fn ($q, $id) => $q->where('id', '<', $id))->orderByDesc('id');
        }
        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->sortBy('id')->values();

        return response()->json([
            'data' => $rows->map(fn ($message) => MessageData::from($message)),
            'has_more' => $hasMore, 'next_after_id' => $rows->last()?->id ?? ($data['after_id'] ?? 0),
            'next_before_id' => $rows->first()?->id,
            'access_version' => $inquiry->access_version,
            'channel' => 'inquiry.'.$inquiry->id.'.v'.$inquiry->access_version,
        ]);
    }

    public function store(StoreMessageRequest $request, Inquiry $inquiry, MessageService $service)
    {
        [$message, $created] = $service->send($request->user(), $inquiry, $request->validated());

        return response()->json(MessageData::from($message), $created ? 201 : 200);
    }
}
