<?php

namespace App\Livewire\Admin\Redirects;

use App\Models\Redirect;
use App\Models\User;
use App\Services\ContentLinkInspector;
use App\Services\RedirectService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

final class RedirectIndex extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var list<array{url: string, status: string, message: string}> */
    public array $linkResults = [];

    public ?int $editingId = null;

    public string $sourcePath = '';

    public string $destinationUrl = '';

    public int $httpStatus = 301;

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('redirects.manage');
    }

    public function edit(int $id): void
    {
        $redirect = Redirect::query()->findOrFail($id);
        $this->editingId = $redirect->id;
        $this->sourcePath = $redirect->source_path;
        $this->destinationUrl = $redirect->destination_url;
        $this->httpStatus = $redirect->http_status;
        $this->isActive = $redirect->is_active;
    }

    public function save(): void
    {
        $this->validate([
            'sourcePath' => ['required', 'string', 'max:500'],
            'destinationUrl' => ['required', 'string', 'max:2048'],
            'httpStatus' => ['required', 'integer', 'in:301,302,307,308'],
            'isActive' => ['boolean'],
        ]);

        $redirect = $this->editingId !== null ? Redirect::query()->findOrFail($this->editingId) : null;
        app(RedirectService::class)->save(
            redirect: $redirect,
            actor: $this->actor(),
            sourcePath: $this->sourcePath,
            destinationUrl: $this->destinationUrl,
            status: $this->httpStatus,
            isActive: $this->isActive,
        );

        $this->resetForm();
        session()->flash('status', 'Redirect saved successfully.');
    }

    public function delete(int $id): void
    {
        app(RedirectService::class)->delete(Redirect::query()->findOrFail($id), $this->actor());
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('status', 'Redirect deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.admin.redirects.redirect-index', [
            'redirects' => Redirect::query()->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query->where('source_path', 'like', '%'.trim($this->search).'%')->orWhere('destination_url', 'like', '%'.trim($this->search).'%')))->latest()->paginate(15),
        ])->layout('components.layouts.admin', ['title' => 'Redirect Manager']);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->sourcePath = '';
        $this->destinationUrl = '';
        $this->httpStatus = 301;
        $this->isActive = true;
        $this->resetValidation();
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages(['authorization' => 'An authenticated administrator is required.']);
        }

        return $user;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function checkDestination(): void
    {
        Gate::authorize('redirects.manage');
        $this->validate(['destinationUrl' => ['required', 'string', 'max:2048']]);
        $target = $this->destinationUrl;
        $seen = [];
        $this->linkResults = [];
        for ($i = 0; $i < 20; $i++) {
            if (isset($seen[$target])) {
                $this->linkResults[] = ['url' => $target, 'status' => 'broken', 'message' => 'Redirect cycle detected.'];
                break;
            }
            $seen[$target] = true;
            $result = app(ContentLinkInspector::class)->inspect($target);
            $this->linkResults[] = $result;
            if ($result['status'] !== 'redirect') {
                break;
            }
            $redirect = Redirect::query()->where('source_path', parse_url($target, PHP_URL_PATH))->where('is_active', true)->first();
            if (! $redirect) {
                break;
            }
            $target = $redirect->destination_url;
        }
    }
}
