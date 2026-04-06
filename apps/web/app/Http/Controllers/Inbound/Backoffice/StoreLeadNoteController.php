<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\StoreLeadNoteRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteAction;
use Inbound\Application\Actions\Backoffice\AddLeadNote\LeadNotFoundException;
use InvalidArgumentException;

final class StoreLeadNoteController extends Controller
{
    public function __invoke(
        StoreLeadNoteRequest $request,
        AddLeadNoteAction $action,
        string $leadId,
    ): RedirectResponse {
        try {
            $action($request->toCommand(
                leadId: $leadId,
                authorId: $this->resolveAuthorId(),
            ));
        } catch (LeadNotFoundException|InvalidArgumentException) {
            return redirect()
                ->route('admin.leads.index')
                ->with('error', 'Не вдалося додати нотатку: лід не знайдено.');
        }

        return redirect()
            ->route('admin.leads.show', ['leadId' => $leadId], 303)
            ->withFragment('lead-note-form')
            ->with('success', 'Нотатку додано.')
            ->with('success_context', 'lead_note');
    }

    private function resolveAuthorId(): ?int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $authorId = $user->getAuthIdentifier();

        if (is_int($authorId) && $authorId > 0) {
            return $authorId;
        }

        if (is_numeric($authorId)) {
            $authorId = (int) $authorId;

            return $authorId > 0 ? $authorId : null;
        }

        return null;
    }
}
