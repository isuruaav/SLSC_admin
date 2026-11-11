<?php

namespace App\Livewire\Admin\ContactMessages;

use App\Models\ContactMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ContactMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

final class ContactMessageIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $selectedMessageId = null;

    public string $assignedTo = '';

    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('contacts.manage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openMessage(int $id): void
    {
        $message = ContactMessage::query()->findOrFail($id);
        app(ContactMessageService::class)->markRead($message, $this->actor());
        $this->selectedMessageId = $id;
        $this->assignedTo = (string) ($message->assigned_to ?? '');
        $this->notes = (string) ($message->internal_notes ?? '');
        $this->dispatch('contact-messages-updated');
    }

    public function resolve(int $id): void
    {
        $message = ContactMessage::query()->findOrFail($id);
        app(ContactMessageService::class)->resolve($message, $this->actor());
        $this->dispatch('contact-messages-updated');
        session()->flash('status', 'Contact message resolved.');
    }

    public function render(): View
    {
        Gate::authorize('contacts.manage');
        $query = ContactMessage::query();
        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.contact-messages.contact-message-index', [
            'messages' => $query->latest()->paginate(15),
            'selectedMessage' => $this->selectedMessageId !== null ? ContactMessage::query()->with('handler')->find($this->selectedMessageId) : null,
            'newCount' => ContactMessage::query()->where('status', 'new')->count(),
            'staff' => User::query()->where('is_active', true)->with('roles.permissions')->orderBy('name')->get()->filter(fn (User $user) => Gate::forUser($user)->allows('contacts.manage')),
        ])->layout('components.layouts.admin', ['title' => 'Contact Messages']);
    }

    public function saveMessageDetails(): void
    {
        Gate::authorize('contacts.manage');
        $this->validate(['assignedTo' => ['nullable', 'integer', 'exists:users,id'], 'notes' => ['nullable', 'string', 'max:5000']]);
        $assignee = $this->assignedTo !== '' ? User::query()->findOrFail((int) $this->assignedTo) : null;
        if ($assignee !== null && (! $assignee->is_active || ! Gate::forUser($assignee)->allows('contacts.manage'))) {
            throw ValidationException::withMessages(['assignedTo' => 'Select an active staff member who can manage contact messages.']);
        }
        $message = ContactMessage::query()->whereKey($this->selectedMessageId)->firstOrFail();
        $message->forceFill(['assigned_to' => $assignee?->id, 'internal_notes' => trim($this->notes) ?: null])->save();
        app(AuditLogger::class)->log(event: 'contacts.assigned', description: 'Contact message assignment and internal notes updated.', actor: $this->actor(), subject: $message, newValues: ['assigned_to' => $assignee?->id]);
        session()->flash('status', 'Message details saved.');
    }

    public function reopen(int $id): void
    {
        Gate::authorize('contacts.manage');
        $message = ContactMessage::query()->findOrFail($id);
        $message->forceFill(['status' => 'read', 'resolved_at' => null])->save();
        app(AuditLogger::class)->log(event: 'contacts.reopened', description: 'Contact message reopened.', actor: $this->actor(), subject: $message);
        session()->flash('status', 'Message reopened.');
        $this->dispatch('contact-messages-updated');
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages(['authorization' => 'An authenticated administrator is required.']);
        }

        return $user;
    }
}
