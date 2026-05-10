<div>
    <x-modal :title="__('Edit user / reset password')" wire>
        <form id="user-update-{{ $user?->id }}" wire:submit="save" class="space-y-4">
            <div>
                <x-input label="{{ __('Name') }} *" wire:model="user.name" required />
            </div>

            <div>
                <x-input label="{{ __('Email') }} *" wire:model="user.email" required />
            </div>

            <div>
                <label for="update-user-role-{{ $user?->id }}" class="mb-1 block text-sm font-bold text-slate-700">Role *</label>
                <select id="update-user-role-{{ $user?->id }}"
                        wire:model="role"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    @foreach ($this->roleOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs font-semibold text-muted-ink">Changing a role swaps the default permission baseline for this family member.</p>
            </div>

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
        </form>
        <x-slot:footer>
            <x-button type="submit" form="user-update-{{ $user?->id }}" loading="save">
                @lang('Save')
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
