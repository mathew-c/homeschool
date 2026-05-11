<div class="northstar-page">
    <section class="page-heading">
        <div>
            <p class="eyebrow">Family access</p>
            <h1>Users & roles</h1>
            <p>
                Owner-only account administration for parents, students, and evaluators. Each role starts with sensible defaults; fine-grained overrides can layer on later.
            </p>
        </div>

        <div class="hero-actions">
            <livewire:users.create @created="$refresh" />
        </div>
    </section>

    <section class="surface-panel">
        <x-table :$headers :$sort :rows="$this->rows" paginate simple-pagination filter loading :quantity="[2, 5, 15, 25]">
            @interact('column_created_at', $row)
            {{ $row->created_at->diffForHumans() }}
            @endinteract

            @interact('column_role', $row)
            <span class="status-chip neutral">{{ $row->role?->label() ?? 'Unassigned' }}</span>
            @if ($row->id === auth()->id())
                <span class="status-chip orange">You</span>
            @endif
            @endinteract

            @interact('column_access', $row)
            @php
                $grant = $row->studentAccessGrants->first();
                $student = $row->studentProfile ?? $grant?->student;
            @endphp

            @if ($row->hasRole(\App\Enums\UserRole::Owner, \App\Enums\UserRole::Parent))
                <span class="access-summary">All students</span>
            @elseif ($student)
                <span class="access-summary">{{ $student->name }}</span>
            @else
                <span class="status-chip amber">Needs student</span>
            @endif
            @endinteract

            @interact('column_permissions', $row)
            <div class="permission-summary">
                <span>{{ count($row->permissionValues()) }} allowed</span>
                <span class="status-chip {{ $row->permissions === null ? 'neutral' : 'amber' }}">
                    {{ $row->permissions === null ? 'Role defaults' : 'Custom' }}
                </span>
            </div>
            @endinteract

            @interact('column_status', $row)
            @if ($row->isDisabled())
                <span class="status-chip red">Disabled</span>
            @else
                <span class="status-chip blue">Active</span>
            @endif
            @endinteract

            @interact('column_action', $row)
            <div class="user-admin-actions">
                @can('update', $row)
                    <button class="user-action-button" type="button" wire:click="$dispatch('load::user', { 'user' : '{{ $row->id }}'})">
                        Edit
                    </button>

                    <button class="user-action-button reset" type="button" wire:click="$dispatch('load::user', { 'user' : '{{ $row->id }}'})">
                        Reset password
                    </button>
                @endcan

                @canany(['disable', 'enable'], $row)
                    <livewire:users.status-toggle :user="$row" :key="'status-'.$row->id.'-'.$row->disabled_at?->timestamp" @user-status-updated="$refresh" />
                @endcan
            </div>
            @endinteract
        </x-table>
    </section>

    <livewire:users.update @updated="$refresh" />
</div>
