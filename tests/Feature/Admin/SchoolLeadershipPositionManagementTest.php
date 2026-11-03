<?php

use App\Livewire\Admin\SchoolLeaders\SchoolLeaderIndex;
use App\Livewire\Admin\SchoolLeaders\SchoolLeadershipPositionIndex;
use App\Models\AuditLog;
use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->editor = User::factory()->create(['is_active' => true]);
    foreach (['school-leaders.view', 'school-leaders.update'] as $permission) {
        $this->editor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->position = SchoolLeadershipPosition::query()->where('key', 'commandant')->firstOrFail();
});

function positionManagementLeader(SchoolLeadershipPosition $position, ?string $endDate = null): SchoolLeader
{
    return SchoolLeader::query()->create([
        'position_id' => $position->id,
        'role_key' => $position->key,
        'title_en' => $position->name_en,
        'name_en' => 'Assigned Officer',
        'rank' => 'colonel',
        'start_date' => '2024-01-01',
        'end_date' => $endDate,
        'is_active' => true,
        'profile_enabled' => true,
        'profile_html_en' => '<p>Officer biography.</p>',
    ]);
}

it('generates an editable unique key from the name and preserves manual input', function (): void {
    $component = Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('startCreate')
        ->set('nameEn', 'Training Officer')
        ->assertSet('systemKey', 'training_officer')
        ->set('systemKey', 'training_director')
        ->set('nameEn', 'Director of Training')
        ->assertSet('systemKey', 'training_director')
        ->set('nameSi', 'Training Director')
        ->set('rankGroup', 'commissioned')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showEditor', false)
        ->assertSet('systemKey', '');

    $this->assertDatabaseHas('school_leadership_positions', [
        'key' => 'training_director', 'name_en' => 'Director of Training',
    ]);

    $component->call('startCreate')->set('nameEn', 'Training Director')
        ->assertSet('systemKey', 'training_director_2');
});

it('fits generated keys and collision suffixes within the leader key column', function (): void {
    $longName = str_repeat('a', 100);
    SchoolLeadershipPosition::query()->create([
        'key' => str_repeat('a', 50), 'name_en' => 'Existing Long Key', 'name_si' => 'Existing',
        'rank_group' => 'commissioned',
    ]);
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->set('nameEn', $longName)
        ->assertSet('systemKey', str_repeat('a', 48).'_2')
        ->set('nameSi', 'Long name')->set('rankGroup', 'commissioned')
        ->call('save')->assertHasNoErrors();
});

it('allows a name change without replacing an existing key', function (): void {
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->set('search', 'no matches')
        ->call('edit', $this->position->id)
        ->assertSet('systemKey', 'commandant')
        ->assertSeeHtml('id="position-system-key"')
        ->set('nameEn', 'Colonel Commandant')
        ->assertSet('systemKey', 'commandant')
        ->call('save')->assertHasNoErrors();

    expect($this->position->refresh()->key)->toBe('commandant');
});

it('renames keys for present and past leaders together and preserves profiles', function (): void {
    $current = positionManagementLeader($this->position);
    $past = positionManagementLeader($this->position, '2025-01-01');
    $url = $current->profileUrlForLocale('en');

    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('edit', $this->position->id)
        ->set('systemKey', 'colonel_commandant')
        ->call('save')->assertHasNoErrors();

    foreach ([$current, $past] as $leader) {
        expect($leader->refresh()->role_key)->toBe('colonel_commandant')
            ->and($leader->position_id)->toBe($this->position->id)
            ->and($leader->profile_html_en)->toBe('<p>Officer biography.</p>');
    }
    expect($current->profileUrlForLocale('en'))->toBe($url);
    $this->get($url)->assertOk()->assertSee('Officer biography.');
    Livewire::actingAs($this->editor)->test(SchoolLeaderIndex::class)
        ->call('edit', $current->id)->assertSet('roleKey', 'colonel_commandant');

    $audit = AuditLog::query()->where('event', 'settings.school-leadership-position-updated')->sole();
    expect($audit->old_values['key'])->toBe('commandant')
        ->and($audit->new_values['key'])->toBe('colonel_commandant');
});

it('rejects duplicate and invalid keys without changing linked leaders', function (string $key): void {
    $leader = positionManagementLeader($this->position);
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('edit', $this->position->id)
        ->set('systemKey', $key)
        ->call('save')->assertHasErrors('systemKey')->assertSet('showEditor', true);

    expect($this->position->refresh()->key)->toBe('commandant')
        ->and($leader->refresh()->role_key)->toBe('commandant');
})->with(['chief_instructor', '', 'UpperCase', 'two words', 'two__underscores', str_repeat('a', 51)]);

it('deletes an unused type with confirmation and an audit trail and resets pagination', function (): void {
    $component = Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->assertSeeHtml('wire:click="delete('.$this->position->id.')"')
        ->assertSeeHtml('wire:confirm="Delete the appointment type')
        ->call('edit', $this->position->id)
        ->call('setPage', 2)
        ->call('delete', $this->position->id)
        ->assertHasNoErrors()->assertSet('showEditor', false)
        ->assertSet('editingId', null)->assertSet('systemKey', '')
        ->assertSet('paginators.page', 1)
        ->assertSeeText('Appointment type deleted successfully.');

    $this->assertDatabaseMissing('school_leadership_positions', ['id' => $this->position->id]);
    $audit = AuditLog::query()->where('event', 'settings.school-leadership-position-deleted')->sole();
    expect($audit->subject_id)->toBe($this->position->id)
        ->and($audit->actor_id)->toBe($this->editor->id)
        ->and($audit->old_values['key'])->toBe('commandant');
});

it('keeps a type and its assigned leaders when deletion is requested', function (?string $endDate): void {
    $leader = positionManagementLeader($this->position, $endDate);
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('delete', $this->position->id)->assertHasErrors('delete')
        ->assertSeeText('Reassign or delete those leadership appointments');

    $this->assertDatabaseHas('school_leadership_positions', ['id' => $this->position->id]);
    $this->assertDatabaseHas('school_leaders', ['id' => $leader->id, 'position_id' => $this->position->id]);
    $this->assertDatabaseMissing('audit_logs', ['event' => 'settings.school-leadership-position-deleted']);
})->with(['present' => null, 'past' => '2025-01-01']);

it('hides deletion and rejects mutations for read-only users', function (): void {
    $this->editor->revokePermissionTo('school-leaders.update');
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->assertDontSeeHtml('wire:click="delete(')
        ->call('delete', $this->position->id)->assertForbidden();
    Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->set('systemKey', 'changed')
        ->call('save')->assertForbidden();

    expect($this->position->refresh()->key)->toBe('commandant');
});

it('checks viewing permission again before deleting or editing a key', function (): void {
    $component = Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('edit', $this->position->id);
    $editComponent = Livewire::actingAs($this->editor)->test(SchoolLeadershipPositionIndex::class)
        ->call('edit', $this->position->id);
    $this->editor->revokePermissionTo('school-leaders.view');
    $component->call('delete', $this->position->id)->assertForbidden();
    $editComponent->call('save')->assertForbidden();
    expect($this->position->refresh()->key)->toBe('commandant');
});
