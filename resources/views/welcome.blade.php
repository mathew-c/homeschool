<x-guest-layout>
    <main class="min-h-screen bg-slate-950 px-6 py-12 text-white">
        <section class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-normal text-accent-300">Northstar Homeschool</p>
                <h1 class="mt-4 text-5xl font-black leading-tight md:text-7xl">
                    Turn paper planning into a daily board.
                </h1>
                <p class="mt-5 max-w-2xl text-lg text-slate-300">
                    A TALL stack homeschool cockpit for assigning the day, dragging work through progress,
                    and preserving evidence for a college-bound transcript.
                </p>
            </div>

            <x-card class="bg-white text-slate-950">
                <div class="space-y-4">
                    @auth
                        <x-button href="{{ route('dashboard') }}" color="primary" text="Open Daily Board" />
                    @else
                        <x-button href="{{ route('login') }}" color="primary" text="Login" />
                        <x-button href="{{ route('register') }}" color="secondary" text="Create parent account" />
                    @endauth
                </div>
            </x-card>
        </section>
    </main>
</x-guest-layout>
