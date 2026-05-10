<div>
    <x-modal :title="__('Edit user / reset password')" wire>
        <form id="user-update-{{ $user?->id }}" wire:submit="save" class="space-y-5">
            <div>
                <x-input label="{{ __('Name') }} *" wire:model="user.name" required />
            </div>

            <div>
                <x-input label="{{ __('Email') }} *" wire:model="user.email" required />
            </div>

            <div>
                <label for="update-user-role-{{ $user?->id }}" class="mb-1 block text-sm font-bold text-slate-700">Role *</label>
                <select id="update-user-role-{{ $user?->id }}"
                        wire:model.live="role"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    @foreach ($this->roleOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs font-semibold text-muted-ink">Changing a role swaps the default permission baseline for this family member.</p>
            </div>

            @if (in_array($role, ['student', 'evaluator'], true))
                <div>
                    <label for="update-user-student-{{ $user?->id }}" class="mb-1 block text-sm font-bold text-slate-700">
                        {{ $role === 'evaluator' ? 'Evaluator is tied to' : 'Student login for' }} *
                    </label>
                    <select id="update-user-student-{{ $user?->id }}"
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
                <x-password :label="__('Password')"
                            hint="The password will only be updated if you set the value of this field"
                            wire:model="password"
                            rules
                            generator
                            x-on:generate="$wire.set('password_confirmation', $event.detail.password)" />
            </div>

            <div>
                <x-password :label="__('Confirm password')" wire:model="password_confirmation" rules />
            </div>

            <section class="password-reset-box">
                <div>
                    <h3>Temporary password</h3>
                    <p>Generate one for the family member, then tell them to change it after login.</p>
                </div>

                <button type="button" class="user-action-button reset" wire:click="resetPassword" wire:loading.attr="disabled" wire:target="resetPassword">
                    Generate
                </button>

                @if ($temporaryPassword)
                    <label class="password-reset-result">
                        New password
                        <input type="text" readonly value="{{ $temporaryPassword }}" onclick="this.select()">
                    </label>
                @endif
            </section>

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
                        @foreach ($this->permissionGroups as $group => $permissions)
                            <fieldset class="permission-group">
                                <legend>{{ $group }}</legend>

                                @foreach ($permissions as $permission)
                                    <label class="permission-option">
                                        <input type="checkbox" wire:model="permissions" value="{{ $permission['value'] }}">
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
            <x-button type="submit" form="user-update-{{ $user?->id }}" loading="save">
                @lang('Save')
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
