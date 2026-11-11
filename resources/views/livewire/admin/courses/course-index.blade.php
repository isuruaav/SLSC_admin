<div class="course-admin">
    <style>
        .course-admin{--ca-navy:#11243b;--ca-green:#147449;--ca-muted:#64748b;color:#182336}
        .course-admin *{box-sizing:border-box}
        .course-admin .ca-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px}
        .course-admin .ca-eyebrow{display:flex;align-items:center;gap:8px;color:#158052;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .course-admin .ca-title{margin:8px 0 4px;color:#11243b;font-size:26px;font-weight:750;line-height:1.25}
        .course-admin .ca-subtitle{margin:0;color:var(--ca-muted);font-size:14px}
        .course-admin .ca-primary{display:inline-flex;min-height:42px;align-items:center;justify-content:center;gap:9px;border:0;border-radius:10px;background:#16764d;padding:10px 16px;color:#fff;font-size:14px;font-weight:700;box-shadow:0 4px 10px #16764d25;transition:background .15s,transform .15s}
        .course-admin .ca-primary:hover{transform:translateY(-1px);background:#105f3c}
        .course-admin .ca-soft-button{display:inline-flex;align-items:center;gap:8px;border:1px solid #dce4ed;border-radius:9px;background:white;padding:8px 11px;color:#334155;font-size:12px;font-weight:650}
        .course-admin .ca-soft-button:hover{background:#f8fafc}
        .course-admin .ca-notice{display:flex;align-items:center;gap:10px;margin-bottom:18px;border:1px solid #b9e5ce;border-radius:11px;background:#f0fbf4;padding:12px 15px;color:#17623e;font-size:13px}
        .course-admin .ca-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:20px}
        .course-admin .ca-stat{display:flex;align-items:center;gap:13px;border:1px solid #e5eaf0;border-radius:13px;background:#fff;padding:15px}
        .course-admin .ca-stat-icon{display:grid;width:40px;height:40px;flex:none;place-items:center;border-radius:11px;background:#eaf6ef;color:#19754c;font-size:16px}
        .course-admin .ca-stat:nth-child(2) .ca-stat-icon{background:#eff6ff;color:#2563a6}
        .course-admin .ca-stat:nth-child(3) .ca-stat-icon{background:#fff7e8;color:#a36309}
        .course-admin .ca-stat-number{color:#11243b;font-size:21px;font-weight:750;line-height:1}
        .course-admin .ca-stat-label{margin-top:5px;color:#64748b;font-size:12px}
        .course-admin .ca-workspace{display:grid;grid-template-columns:250px minmax(0,1fr);align-items:start;gap:18px}
        .course-admin .ca-sidebar,.course-admin .ca-panel{border:1px solid #e3e9ef;border-radius:14px;background:#fff;box-shadow:0 3px 12px #11243b08}
        .course-admin .ca-sidebar{position:sticky;top:100px;padding:15px}
        .course-admin .ca-sidebar-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:13px}
        .course-admin .ca-sidebar-title{margin:0;color:#11243b;font-size:13px;font-weight:750}
        .course-admin .ca-side-link{display:flex;width:100%;min-height:39px;align-items:center;justify-content:space-between;gap:8px;border:0;border-radius:9px;background:transparent;padding:8px 10px;text-align:left;color:#475569;font-size:12px;transition:background .15s,color .15s}
        .course-admin .ca-side-link:hover{background:#f5f8fa;color:#11243b}
        .course-admin .ca-side-link.active{background:#eaf6ef;color:#126640;font-weight:700}
        .course-admin .ca-side-label{display:flex;min-width:0;align-items:center;gap:9px}
        .course-admin .ca-side-label i{width:15px;text-align:center;color:#739080}
        .course-admin .ca-side-link.active .ca-side-label i{color:#16764d}
        .course-admin .ca-count{min-width:24px;border-radius:999px;background:#f0f3f6;padding:3px 7px;text-align:center;color:#64748b;font-size:10px;font-weight:700}
        .course-admin .ca-side-link.active .ca-count{background:#d5eddf;color:#126640}
        .course-admin .ca-divider{height:1px;margin:13px 0;background:#edf0f3}
        .course-admin .ca-category-manage{display:flex;align-items:center;justify-content:space-between;margin:4px 0 8px;padding:0 4px;color:#94a3b8;font-size:10px;font-weight:750;letter-spacing:.08em;text-transform:uppercase}
        .course-admin .ca-category-tools{display:flex;align-items:center;gap:2px}
        .course-admin .ca-mini-icon{display:grid;width:25px;height:25px;place-items:center;border:0;border-radius:7px;background:transparent;color:#8492a3;font-size:11px}
        .course-admin .ca-mini-icon:hover{background:#eef5f0;color:#16764d}
        .course-admin .ca-mini-icon.danger:hover{background:#fff0f0;color:#c03939}
        .course-admin .ca-category-form{margin:9px 0 11px;border:1px solid #dce8e0;border-radius:10px;background:#f8fcf9;padding:11px}
        .course-admin .ca-category-form h3{margin:0 0 9px;color:#1b4330;font-size:12px;font-weight:750}
        .course-admin .ca-filter-label{margin:0 0 7px;color:#94a3b8;font-size:10px;font-weight:750;letter-spacing:.08em;text-transform:uppercase}
        .course-admin .ca-panel{min-width:0;overflow:hidden}
        .course-admin .ca-form{margin:15px;border:1px solid #dce8e0;border-radius:12px;background:#fbfdfb;padding:18px}
        .course-admin .ca-form-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
        .course-admin .ca-form-heading h2{margin:0;color:#11243b;font-size:17px;font-weight:750}
        .course-admin .ca-form-heading p{margin:4px 0 0;color:#64748b;font-size:12px}
        .course-admin .ca-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .course-admin .ca-field{min-width:0}
        .course-admin .ca-field.full{grid-column:1/-1}
        .course-admin .ca-label{display:block;margin:0 0 6px;color:#344256;font-size:12px;font-weight:700}
        .course-admin .ca-help{margin:5px 0 0;color:#8b98a9;font-size:11px}
        .course-admin .ca-input{width:100%;min-height:40px;border:1px solid #d8e0e8;border-radius:8px;background:white;padding:9px 11px;color:#182336;font-size:13px;outline:none}
        .course-admin .ca-input:focus{border-color:#53a77d;box-shadow:0 0 0 3px #16764d16}
        .course-admin .ca-qual-row{display:flex;align-items:flex-start;gap:8px;margin-top:8px}
        .course-admin .ca-qual-row textarea{min-height:56px;resize:vertical}
        .course-admin .ca-check{display:flex;align-items:center;gap:9px;color:#344256;font-size:12px;font-weight:650}
        .course-admin .ca-form-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;margin-top:17px;border-top:1px solid #e8eee9;padding-top:14px}
        .course-admin .ca-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:11px;border-bottom:1px solid #edf0f3;padding:16px}
        .course-admin .ca-results-heading{min-width:130px}
        .course-admin .ca-results-heading h2{margin:0;color:#11243b;font-size:15px;font-weight:750}
        .course-admin .ca-results-heading p{margin:4px 0 0;color:#8491a1;font-size:11px}
        .course-admin .ca-filters{display:flex;flex:1;justify-content:flex-end;gap:8px}
        .course-admin .ca-search{position:relative;min-width:160px;max-width:290px;flex:1}
        .course-admin .ca-search i{position:absolute;top:50%;left:12px;transform:translateY(-50%);color:#94a3b8;font-size:12px}
        .course-admin .ca-search .ca-input{padding-left:33px}
        .course-admin .ca-status-select{width:auto;min-width:125px}
        .course-admin .ca-course{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;border-bottom:1px solid #f0f2f4;padding:16px}
        .course-admin .ca-course:last-child{border-bottom:0}
        .course-admin .ca-course:hover{background:#fcfdfc}
        .course-admin .ca-course-main{min-width:0;flex:1}
        .course-admin .ca-course-title-row{display:flex;flex-wrap:wrap;align-items:center;gap:7px}
        .course-admin .ca-course h3{margin:0;color:#1b293b;font-size:13px;font-weight:730;line-height:1.6}
        .course-admin .ca-course-si{margin:2px 0 0;color:#516172;font-size:12px;line-height:1.75}
        .course-admin .ca-badge{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:4px 8px;font-size:9px;font-weight:750;white-space:nowrap}
        .course-admin .ca-badge.active{background:#e9f7ee;color:#187346}
        .course-admin .ca-badge.inactive{background:#f0f2f4;color:#657386}
        .course-admin .ca-badge.category{background:#eff5fb;color:#386384}
        .course-admin .ca-course-meta{display:flex;flex-wrap:wrap;align-items:center;gap:7px 14px;margin-top:8px;color:#798697;font-size:10px}
        .course-admin .ca-course-meta span{display:inline-flex;align-items:center;gap:5px}
        .course-admin .ca-qualification-list{display:flex;flex-wrap:wrap;gap:5px;margin:9px 0 0}
        .course-admin .ca-qualification{border:1px solid #e9edf1;border-radius:6px;background:#fafbfc;padding:3px 7px;color:#68778a;font-size:10px}
        .course-admin .ca-actions{display:flex;flex:none;gap:5px}
        .course-admin .ca-empty{padding:44px 20px;text-align:center}
        .course-admin .ca-empty-icon{display:grid;width:48px;height:48px;margin:0 auto 12px;place-items:center;border-radius:15px;background:#edf7f0;color:#187346;font-size:19px}
        .course-admin .ca-empty h3{margin:0;color:#253449;font-size:14px;font-weight:750}
        .course-admin .ca-empty p{margin:6px 0 0;color:#8793a2;font-size:12px}
        .course-admin .ca-error{margin-top:4px;color:#c03939;font-size:11px}
        @media(max-width:900px){.course-admin .ca-workspace{grid-template-columns:210px minmax(0,1fr)}.course-admin .ca-sidebar{top:90px;padding:12px}}
        @media(max-width:700px){.course-admin .ca-heading{align-items:center}.course-admin .ca-title{font-size:22px}.course-admin .ca-subtitle{max-width:250px;font-size:12px}.course-admin .ca-primary{padding:9px 11px;font-size:12px}.course-admin .ca-stats{gap:7px}.course-admin .ca-stat{align-items:flex-start;gap:8px;padding:10px}.course-admin .ca-stat-icon{width:32px;height:32px;border-radius:9px;font-size:13px}.course-admin .ca-stat-number{font-size:17px}.course-admin .ca-stat-label{font-size:10px}.course-admin .ca-workspace{grid-template-columns:1fr;gap:12px}.course-admin .ca-sidebar{position:static}.course-admin .ca-sidebar .ca-category-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:3px}.course-admin .ca-side-link{min-width:0;padding:7px}.course-admin .ca-side-label{font-size:11px}.course-admin .ca-category-tools{opacity:1}.course-admin .ca-form-grid{grid-template-columns:1fr}.course-admin .ca-field.full{grid-column:auto}.course-admin .ca-toolbar{align-items:flex-start}.course-admin .ca-results-heading{width:100%}.course-admin .ca-filters{width:100%;justify-content:stretch}.course-admin .ca-search{max-width:none}.course-admin .ca-course{gap:8px;padding:13px}.course-admin .ca-course h3{font-size:12px}.course-admin .ca-course-si{font-size:11px}.course-admin .ca-actions{gap:3px}.course-admin .ca-actions .inline-flex{width:32px;height:32px}.course-admin .ca-course-meta{gap:6px 10px}}
    </style>

    <header class="ca-heading">
        <div>
            <div class="ca-eyebrow"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Training management</div>
            <h1 class="ca-title">Courses</h1>
            <p class="ca-subtitle">Organize programmes, keep course details current, and control what visitors can see.</p>
        </div>
        @can('courses.create')<button type="button" wire:click="create" class="ca-primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add course</span>
        </button>@endcan
    </header>

    @if (session('status'))
        <div role="status" class="ca-notice"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ session('status') }}</div>
    @endif

    <section class="ca-stats" aria-label="Course summary">
        <div class="ca-stat">
            <span class="ca-stat-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
            <div><div class="ca-stat-number">{{ $courseCounts['all'] }}</div><div class="ca-stat-label">Total courses</div></div>
        </div>
        <div class="ca-stat">
            <span class="ca-stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <div><div class="ca-stat-number">{{ $courseCounts['active'] }}</div><div class="ca-stat-label">Active and public</div></div>
        </div>
        <div class="ca-stat">
            <span class="ca-stat-icon"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></span>
            <div><div class="ca-stat-number">{{ $courseCounts['inactive'] }}</div><div class="ca-stat-label">Hidden courses</div></div>
        </div>
    </section>

    <div class="ca-workspace">
        <aside class="ca-sidebar" aria-label="Course categories">
            <div class="ca-sidebar-top">
                <h2 class="ca-sidebar-title"><i class="fa-solid fa-layer-group mr-2 text-emerald-700" aria-hidden="true"></i>Browse courses</h2>
                @can('courses.categories.manage')<button type="button" wire:click="openCategoryForm" class="ca-mini-icon" aria-label="Add category" title="Add category">
                    <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
                </button>@endcan
            </div>

            @if ($categoryFormOpen)
                <form wire:submit="saveCategory" class="ca-category-form">
                    <h3>{{ $editingCategoryId ? 'Edit category' : 'New category' }}</h3>
                    <label class="ca-label" for="category-name-en">Name (English)</label>
                    <input id="category-name-en" type="text" wire:model="categoryName" maxlength="100" class="ca-input" required>
                    @error('categoryName')<p class="ca-error">{{ $message }}</p>@enderror
                    <label class="ca-label mt-2" for="category-name-si">නම (සිංහල)</label>
                    <input id="category-name-si" type="text" wire:model="categoryNameSi" maxlength="100" class="ca-input" lang="si" required>
                    @error('categoryNameSi')<p class="ca-error">{{ $message }}</p>@enderror
                    <div class="mt-3 flex justify-end gap-2">
                        <button type="button" wire:click="closeCategoryForm" class="ca-soft-button">Cancel</button>
                        <button type="submit" class="ca-primary !min-h-0 !px-3 !py-2 !text-xs">Save</button>
                    </div>
                </form>
            @endif

            <p class="ca-filter-label">Library</p>
            <div class="ca-category-list">
                <button type="button" wire:click="selectCategory(null)" @class(['ca-side-link', 'active' => $selectedCategory === null])>
                    <span class="ca-side-label"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>All courses</span>
                    <span class="ca-count">{{ $courseCounts['all'] }}</span>
                </button>
                @foreach ($categories as $category)
                    <div wire:key="category-filter-{{ $category->id }}" class="group flex items-center gap-1">
                        <button type="button" wire:click="selectCategory({{ $category->id }})"
                            @class(['ca-side-link min-w-0 flex-1', 'active' => $selectedCategory === $category->id])>
                            <span class="ca-side-label min-w-0"><i class="fa-solid fa-folder" aria-hidden="true"></i><span class="truncate">{{ $category->name }}</span></span>
                            <span class="ca-count">{{ $category->courses_count }}</span>
                        </button>
                        @can('courses.categories.manage')<span class="ca-category-tools opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                            <button type="button" wire:click="openCategoryForm({{ $category->id }})" class="ca-mini-icon" title="Edit category" aria-label="Edit {{ $category->name }}">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            </button>
                            <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Remove this category? Its courses will remain uncategorized."
                                class="ca-mini-icon danger" title="Delete category" aria-label="Delete {{ $category->name }}">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>
                        </span>@endcan
                    </div>
                @endforeach
            </div>

            <div class="ca-divider"></div>
            <p class="ca-filter-label">Visibility</p>
            <button type="button" wire:click="$set('statusFilter', 'all')" @class(['ca-side-link', 'active' => $statusFilter === 'all'])>
                <span class="ca-side-label"><i class="fa-solid fa-list" aria-hidden="true"></i>Any status</span>
            </button>
            <button type="button" wire:click="$set('statusFilter', 'active')" @class(['ca-side-link', 'active' => $statusFilter === 'active'])>
                <span class="ca-side-label"><i class="fa-solid fa-eye" aria-hidden="true"></i>Active</span>
                <span class="ca-count">{{ $courseCounts['active'] }}</span>
            </button>
            <button type="button" wire:click="$set('statusFilter', 'inactive')" @class(['ca-side-link', 'active' => $statusFilter === 'inactive'])>
                <span class="ca-side-label"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i>Inactive</span>
                <span class="ca-count">{{ $courseCounts['inactive'] }}</span>
            </button>
        </aside>

        <section class="ca-panel" aria-label="Course list">
            @if ($formOpen)
                <form wire:submit="save" class="ca-form">
                    <div class="ca-form-heading">
                        <div>
                            <h2><i class="fa-solid {{ $editingId ? 'fa-pen-to-square' : 'fa-circle-plus' }} mr-2 text-emerald-700" aria-hidden="true"></i>{{ $editingId ? 'Edit course' : 'Add a course' }}</h2>
                            <p>Enter the course details in both languages and choose where it belongs.</p>
                        </div>
                        <button type="button" wire:click="cancel" class="ca-mini-icon" aria-label="Close course form"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>
                    @if ($errors->any())
                        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                        </div>
                    @endif
                    <div class="ca-form-grid">
                        <div class="ca-field full">
                            <label for="course-name" class="ca-label">Course name · English</label>
                            <input id="course-name" type="text" wire:model="name" maxlength="255" required class="ca-input">
                            @error('name')<p class="ca-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ca-field full">
                            <label for="course-name-si" class="ca-label">පාඨමාලාවේ නම · සිංහල</label>
                            <input id="course-name-si" type="text" wire:model="nameSi" maxlength="255" required lang="si" class="ca-input">
                            @error('nameSi')<p class="ca-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ca-field">
                            <label for="course-category" class="ca-label">Category</label>
                            <select id="course-category" wire:model="courseCategoryId" class="ca-input">
                                <option value="">Choose a category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} · {{ $category->name_si }}</option>
                                @endforeach
                            </select>
                            @error('courseCategoryId')<p class="ca-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ca-field">
                            <label class="ca-label">Public visibility</label>
                            <label class="ca-check rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                <input type="checkbox" wire:model="isActive" class="rounded border-zinc-300 text-emerald-700">
                                <span><i class="fa-solid {{ $isActive ? 'fa-eye text-emerald-700' : 'fa-eye-slash text-zinc-400' }} mr-1" aria-hidden="true"></i>{{ $isActive ? 'Active — visible to visitors' : 'Inactive — hidden from visitors' }}</span>
                            </label>
                        </div>
                        <div class="ca-field">
                            <label for="course-start-date" class="ca-label">Start date <span class="font-normal text-zinc-400">(optional)</span></label>
                            <input id="course-start-date" type="date" wire:model="startDate" class="ca-input">
                            @error('startDate')<p class="ca-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ca-field">
                            <label for="course-end-date" class="ca-label">End date <span class="font-normal text-zinc-400">(optional)</span></label>
                            <input id="course-end-date" type="date" wire:model="endDate" class="ca-input">
                            @error('endDate')<p class="ca-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ca-field full">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <label class="ca-label mb-0">Qualifications</label>
                                    <p class="ca-help">Add each requirement as a separate item.</p>
                                </div>
                                <button type="button" wire:click="addQualification" class="ca-soft-button">
                                    <i class="fa-solid fa-plus text-emerald-700" aria-hidden="true"></i>Add qualification
                                </button>
                            </div>
                            @foreach ($qualifications as $index => $qualification)
                                <div wire:key="course-qualification-{{ $index }}" class="ca-qual-row">
                                    <textarea wire:model="qualifications.{{ $index }}" rows="2" maxlength="500" aria-label="Qualification {{ $index + 1 }}" class="ca-input" placeholder="English qualification"></textarea>
                                    <textarea wire:model="qualificationsSi.{{ $index }}" rows="2" maxlength="500" lang="si" aria-label="Sinhala qualification {{ $index + 1 }}" placeholder="සිංහල සුදුසුකම" class="ca-input"></textarea>
                                    <x-admin.icon-action label="Remove qualification" icon="trash" tone="danger" type="button" wire:click="removeQualification({{ $index }})" />
                                </div>
                                @error('qualifications.'.$index)<p class="ca-error">{{ $message }}</p>@enderror
                            @endforeach
                        </div>
                    </div>
                    <div class="ca-form-actions">
                        <button type="button" wire:click="cancel" class="ca-soft-button">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="ca-primary">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>{{ $editingId ? 'Save changes' : 'Create course' }}
                        </button>
                    </div>
                </form>
            @endif

            <div class="ca-toolbar">
                <div class="ca-results-heading">
                    <h2>{{ $selectedCategory ? ($categories->firstWhere('id', $selectedCategory)?->name ?? 'Courses') : 'All courses' }}</h2>
                    <p>{{ $courses->total() }} {{ $courses->total() === 1 ? 'course' : 'courses' }} in this view</p>
                </div>
                <div class="ca-filters">
                    <label class="ca-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" wire:model.live.debounce.300ms="search" aria-label="Search courses" placeholder="Search English or Sinhala…" class="ca-input">
                    </label>
                </div>
            </div>

            @forelse ($courses as $course)
                <article wire:key="course-{{ $course->id }}" class="ca-course">
                    <div class="ca-course-main">
                        <div class="ca-course-title-row">
                            <h3>{{ $course->name }}</h3>
                            <span @class(['ca-badge', 'active' => $course->is_active, 'inactive' => ! $course->is_active])>
                                <i class="fa-solid {{ $course->is_active ? 'fa-circle-check' : 'fa-circle-minus' }}" aria-hidden="true"></i>
                                {{ $course->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if ($course->category)
                                <span class="ca-badge category"><i class="fa-solid fa-folder" aria-hidden="true"></i>{{ $course->category->name }}</span>
                            @endif
                        </div>
                        <p class="ca-course-si" lang="si">{{ $course->name_si }}</p>
                        <div class="ca-course-meta">
                            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                @if ($course->start_date || $course->end_date)
                                    {{ $course->start_date?->format('d M Y') ?? 'Not set' }} – {{ $course->end_date?->format('d M Y') ?? 'Not set' }}
                                @else
                                    Dates not set
                                @endif
                            </span>
                            <span><i class="fa-solid fa-list-check" aria-hidden="true"></i>{{ $course->qualifications->count() }} {{ $course->qualifications->count() === 1 ? 'qualification' : 'qualifications' }}</span>
                        </div>
                        @if ($course->qualifications->isNotEmpty())
                            <div class="ca-qualification-list">
                                @foreach ($course->qualifications as $qualification)
                                    <span class="ca-qualification"><i class="fa-solid fa-check mr-1 text-emerald-700" aria-hidden="true"></i>{{ $qualification->qualification }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="ca-actions">
                        @can('courses.update')<x-admin.icon-action label="Edit course" icon="pencil-square" tone="info" type="button" wire:click="edit({{ $course->id }})" />@endcan
                        @can('courses.publish')<x-admin.icon-action
                            :label="$course->is_active ? 'Deactivate course' : 'Activate course'"
                            :icon="$course->is_active ? 'eye-slash' : 'eye'"
                            :tone="$course->is_active ? 'warning' : 'success'"
                            type="button" wire:click="toggleActive({{ $course->id }})"
                        />
                        @endcan @can('courses.delete')<x-admin.icon-action label="Delete course" icon="trash" tone="danger" type="button"
                            wire:click="delete({{ $course->id }})" wire:confirm="Delete this course and its qualifications?" />@endcan
                    </div>
                </article>
            @empty
                <div class="ca-empty">
                    <span class="ca-empty-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <h3>No courses match this view</h3>
                    <p>Try another category or search term, or add a new course.</p>
                </div>
            @endforelse
            @if ($courses->hasPages())
                <div class="border-t border-zinc-100 px-4 py-3">{{ $courses->links() }}</div>
            @endif
        </section>
    </div>
</div>
