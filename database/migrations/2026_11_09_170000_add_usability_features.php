<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->string('reference', 32)->nullable()->unique();
            $table->string('district', 80)->nullable();
            $table->string('interest', 40)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
        });
        Schema::table('course_qualifications', fn (Blueprint $table) => $table->text('qualification_si')->nullable());
        Schema::table('first_appointment_holders', function (Blueprint $table): void {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
        foreach (['hero_slides', 'special_programs'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
            });
        }
        Schema::table('publications', function (Blueprint $table): void {
            $table->string('issue_number', 80)->nullable();
            $table->unsignedSmallInteger('issue_year')->nullable()->index();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
        });
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('backup_frequency', 12)->default('manual');
            $table->string('backup_time', 5)->default('02:00');
            $table->string('backup_timezone', 64)->default('Asia/Colombo');
            $table->unsignedSmallInteger('backup_retention')->default(5);
        });
        Schema::create('organization_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot');
            $table->string('summary', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view', 'create', 'update', 'delete', 'publish', 'categories.manage'] as $action) {
            $permission = Permission::findOrCreate('courses.'.$action, 'web');
            foreach (Role::whereIn('name', ['Super Administrator', 'Site Administrator'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        $trainer = Role::query()->firstOrCreate(['name' => 'Training Manager', 'guard_name' => 'web']);
        if ($trainer->wasRecentlyCreated) {
            foreach (['admin.access', 'dashboard.view', 'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish', 'courses.categories.manage'] as $permission) {
                $trainer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_revisions');
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['reference', 'district', 'interest', 'consented_at', 'internal_notes']);
        });
        Schema::table('course_qualifications', fn (Blueprint $table) => $table->dropColumn('qualification_si'));
        Schema::table('first_appointment_holders', fn (Blueprint $table) => $table->dropColumn(['start_date', 'end_date']));
        foreach (['hero_slides', 'special_programs'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['starts_at', 'ends_at']));
        }
        Schema::table('publications', fn (Blueprint $table) => $table->dropColumn(['issue_number', 'issue_year']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['invited_at', 'invitation_accepted_at']));
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['backup_frequency', 'backup_time', 'backup_timezone', 'backup_retention']));
    }
};
