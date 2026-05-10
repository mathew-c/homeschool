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

                @can('delete', $row)
                    <livewire:users.delete :user="$row" :key="uniqid('', true)" @deleted="$refresh" />
                @endcan
            </div>
            @endinteract
        </x-table>
    </section>

    <livewire:users.update @updated="$refresh" />
</div>
