<x-layouts.app :title="$profile['name'].' — '.$profile['role']" :description="$profile['tagline']" :image="asset($profile['og_image'])">
    {{-- Top bar --}}
    <header class="sticky top-0 z-20 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-950/80">
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="#top" class="font-semibold tracking-tight">{{ $profile['name'] }}</a>

            <div class="flex items-center gap-6">
                <div class="hidden gap-6 text-sm text-zinc-600 sm:flex dark:text-zinc-400">
                    <a href="#goals" class="hover:text-accent dark:hover:text-zinc-100">Goals</a>
                    <a href="#certifications" class="hover:text-accent dark:hover:text-zinc-100">Certifications</a>
                    <a href="#projects" class="hover:text-accent dark:hover:text-zinc-100">Projects</a>
                    <a href="#contact" class="hover:text-accent dark:hover:text-zinc-100">Contact</a>
                </div>

                {{-- Theme toggle (Alpine ships with Livewire) --}}
                <button
                    x-data
                    @click="
                        const dark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('theme', dark ? 'dark' : 'light');
                    "
                    type="button"
                    aria-label="Toggle theme"
                    class="rounded-lg border border-zinc-300 p-2 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    <span class="hidden dark:inline">☀</span>
                    <span class="inline dark:hidden">☾</span>
                </button>
            </div>
        </nav>
    </header>

    <main id="top" class="mx-auto max-w-5xl px-6">
        {{-- Hero --}}
        <section class="py-20 sm:py-28">
            <p class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $profile['role'] }} · {{ $profile['location'] }}</p>
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">{{ $profile['name'] }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">{{ $profile['tagline'] }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#contact" class="rounded-lg bg-accent px-5 py-2.5 font-medium text-white hover:bg-accent-hover dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    Get in touch
                </a>
                @if (! empty($profile['cv_path']) && file_exists(public_path($profile['cv_path'])))
                    <a href="{{ asset($profile['cv_path']) }}" download class="rounded-lg border border-zinc-300 px-5 py-2.5 font-medium hover:border-accent hover:bg-accent/5 hover:text-accent dark:border-zinc-700 dark:hover:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                        Download Resume
                    </a>
                @endif
                @foreach ($profile['socials'] as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="rounded-lg border border-zinc-300 px-5 py-2.5 font-medium hover:border-accent hover:bg-accent/5 hover:text-accent dark:border-zinc-700 dark:hover:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                        {{ $social['label'] }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Current goals --}}
        <section id="goals" class="scroll-mt-20 border-t border-zinc-200 py-16 dark:border-zinc-800">
            <h2 class="mb-8 text-2xl font-bold tracking-tight">Current goals</h2>
            <div class="grid gap-5 grid-cols-2">
                @foreach ($goals as $goal)
                    <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
                        <div class="mb-2 gap-4 flex items-center justify-between">
                            <h3 class="font-semibold">{{ $goal->title }}</h3>
                            @if ($goal->target)
                                <span class="shrink-0 whitespace-nowrap text-xs text-zinc-400">{{ $goal->target }}</span>
                            @endif
                        </div>
                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $goal->blurb }}</p>
                        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-accent dark:bg-zinc-100" style="width: {{ $goal->progress }}%"></div>
                        </div>
                        <p class="mt-2 text-xs uppercase tracking-wide text-zinc-400">{{ str_replace('_', ' ', $goal->status) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Certifications --}}
        <section id="certifications" class="scroll-mt-20 border-t border-zinc-200 py-16 dark:border-zinc-800">
            <h2 class="mb-8 text-2xl font-bold tracking-tight">Certifications</h2>
            <ul class="space-y-6">
                @foreach ($certifications as $certification)
                    <li class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                        <span class="w-20 shrink-0 text-sm text-zinc-400">{{ $certification->date }}</span>
                        <div>
                            <h3 class="font-semibold">{{ $certification->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $certification->issuer }}@if ($certification->instructor) · {{ $certification->instructor }}@endif
                                @if ($certification->url)
                                    <a href="{{ $certification->url }}" target="_blank" rel="noopener"
                                       class="ml-1 font-medium text-accent underline-offset-4 hover:underline dark:text-zinc-100">View credential</a>
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Projects (Vue island) --}}
        <section id="projects" class="scroll-mt-20 border-t border-zinc-200 py-16 dark:border-zinc-800">
            <h2 class="mb-8 text-2xl font-bold tracking-tight">Projects</h2>

            {{-- Vue hydrates from this JSON blob; hex flags keep it safe inside the attribute. --}}
            <div
                id="projects-explorer"
                data-projects="{{ json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
            >
                {{-- SSR fallback so projects exist without JS (SEO + no-JS). --}}
                <noscript>
                    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($projects as $project)
                            <li class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
                                <h3 class="font-semibold">{{ $project->title }} <span class="text-xs text-zinc-400">{{ $project->year }}</span></h3>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $project->snippet }}</p>
                            </li>
                        @endforeach
                    </ul>
                </noscript>
            </div>
        </section>

        {{-- Contact (Livewire island) --}}
        <section id="contact" class="scroll-mt-20 border-t border-zinc-200 py-16 dark:border-zinc-800">
            <h2 class="mb-2 text-2xl font-bold tracking-tight">Get in touch</h2>
            <p class="mb-8 max-w-xl text-zinc-600 dark:text-zinc-400">Have a project or role in mind? Send me a note.</p>
            <div class="max-w-xl">
                <livewire:contact-form />
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 px-6 py-10 text-sm text-zinc-500 sm:flex-row dark:text-zinc-400">
            <p>© {{ date('Y') }} {{ $profile['name'] }}</p>
            <div class="flex gap-5">
                @foreach ($profile['socials'] as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="hover:text-accent dark:hover:text-zinc-100">{{ $social['label'] }}</a>
                @endforeach
            </div>
        </div>
    </footer>
</x-layouts.app>
