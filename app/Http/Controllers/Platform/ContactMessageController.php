<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\CrmLead;
use App\Services\Platform\CrmService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbox for homepage contact-form messages (CRM leads with source = موقع).
 */
class ContactMessageController extends Controller
{
    public const SOURCE = 'موقع';

    public function __construct(
        private readonly CrmService $crm,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 20)));
        $q = CrmLead::query()
            ->where('source', self::SOURCE)
            ->with('assignee')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $s = '%'.trim((string) $request->input('search')).'%';
            $q->where(function ($b) use ($s): void {
                $b->where('name', 'like', $s)
                    ->orWhere('email', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('atelier_name', 'like', $s)
                    ->orWhere('last_message', 'like', $s);
            });
        }

        $unread = $request->input('unread');
        if ($unread === '1' || $unread === 1 || $unread === true || $unread === 'true') {
            $q->whereNull('read_at');
        } elseif ($unread === '0' || $unread === 0 || $unread === 'false') {
            $q->whereNotNull('read_at');
        }

        $paginator = $q->paginate($perPage);

        $items = collect($paginator->items())->map(fn (CrmLead $lead) => $this->transform($lead))->all();

        return ApiResponse::paginated($paginator, $items);
    }

    public function unreadCount(): JsonResponse
    {
        $count = CrmLead::query()
            ->where('source', self::SOURCE)
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success(['unread_count' => $count]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $lead = $this->findMessage($id);
        if ($lead->read_at === null) {
            $lead->read_at = now();
            $lead->save();
            $this->crm->addEvent($lead, 'message_read', 'تم فتح رسالة التواصل', null, $request->user()?->id);
        }

        return ApiResponse::success($this->transform($lead->fresh(['assignee', 'leadNotes.author'])));
    }

    public function markRead(int $id): JsonResponse
    {
        $lead = $this->findMessage($id);
        if ($lead->read_at === null) {
            $lead->read_at = now();
            $lead->save();
        }

        return ApiResponse::success($this->transform($lead->fresh(['assignee'])), 'تم تعليم الرسالة كمقروءة');
    }

    public function markAllRead(): JsonResponse
    {
        $updated = CrmLead::query()
            ->where('source', self::SOURCE)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(['updated' => $updated], 'تم تعليم كل الرسائل كمقروءة');
    }

    private function findMessage(int $id): CrmLead
    {
        return CrmLead::query()
            ->where('source', self::SOURCE)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(CrmLead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'atelier_name' => $lead->atelier_name,
            'message' => $lead->last_message,
            'source' => $lead->source,
            'status' => $lead->status,
            'is_read' => $lead->read_at !== null,
            'read_at' => optional($lead->read_at)?->toIso8601String(),
            'created_at' => optional($lead->created_at)?->toIso8601String(),
            'notes' => $lead->relationLoaded('leadNotes')
                ? $lead->leadNotes->map(fn ($n) => [
                    'id' => $n->id,
                    'body' => $n->body,
                    'author' => $n->author?->name,
                    'created_at' => optional($n->created_at)?->toIso8601String(),
                ])->values()->all()
                : null,
        ];
    }
}
