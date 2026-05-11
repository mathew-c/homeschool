<div>
    <x-button :text="__('Create New User')" wire:click="$toggle('modal')" sm />

    <x-modal :title="__('Create New User')" wire x-on:open="setTimeout(() => $refs.name.focus(), 250)">
        <form id="user-create" wire:submit="save" class="space-y-5">
            <div>
                <x-input label="{{ __('Name') }} *" x-ref="name" wire:model="user.name" required />
            </div>

            <div>
                <x-input label="{{ __('Email') }} *" wire:model="user.email" required />
            </div>

            <div>
                <label for="create-user-role" class="mb-1 block text-sm font-bold text-slate-700">Role *</label>
                <select id="create-user-role"
                        wire:model.live="role"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    @foreach ($this->roleOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs font-semibold text-muted-ink">Role permissions use the household defaults unless you override them later.</p>
            </div>

            @if (in_array($role, ['student', 'evaluator'], true))
                <div>
                    <label for="create-user-student" class="mb-1 block text-sm font-bold text-slate-700">
                        {{ $role === 'evaluator' ? 'Evaluator is tied to' : 'Student login for' }} *
                    </label>
                    <select id="create-user-student"
                            wire:model="studentId"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100">
                        <option value="">Choose a student</option>
                        @foreach ($this->studentOptions as $student)
                            <option value="{{ $student['id'] }}">{{ $student['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs font-semibold text-muted-ink">
                        {{ $role === 'evaluator' ? 'Evaluators get read-only access to this one student.' : 'Student users only see their own record.' }}
                    </p>
                </div>
            @endif

            <div>
                <x-password label="{{ __('Password') }} *"
                            wire:model="password"
                            rules
                            generator
                            x-on:generate="$wire.set('password_confirmation', $event.detail.password)"
                            required />
            </div>

            <div>
                <x-password :label="__('Password')" wire:model="password_confirmation" rules required />
            </div>

            @if ($role !== 'owner')
                <section class="permission-editor">
                    <div class="permission-editor-heading">
                        <div>
                            <h3>Permissions</h3>
                            <p>Sensible defaults are checked. Override only when this person needs a tighter lane.</p>
                        </div>

                        <button type="button" class="user-action-button reset" wire:click="resetPermissionsToRoleDefaults">
                            Reset defaults
                        </button>
                    </div>

                    <div class="permission-grid">
                        @foreach ($this->permissionGroups as $group => $groupPermissions)
                            <fieldset class="permission-group">
                                <legend>{{ $group }}</legend>

                                @foreach ($groupPermissions as $permission)
                                    <label class="permission-option">
                                        <input type="checkbox"
                                               wire:model.live="permissions"
                                               value="{{ $permission['value'] }}"
                                               @checked(in_array($permission['value'], $permissions, true))>
                                        <span>
                                            {{ $permission['label'] }}
                                            @if ($permission['default'])
                                                <em>Default</em>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endforeach
                    </div>
                </section>
            @else
                <p class="rounded-lg bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-900">
                    Owner accounts always keep the full permission set to avoid lockout.
                </p>
            @endif
        </form>
        <x-slot:footer>
            <x-button type="submit" form="user-create">
                @lang('Save')
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
