<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const activeTag = ref(null);
const sort = ref('year-desc');

// Unique, sorted list of tags across all projects for the filter chips.
const tags = computed(() => {
    const set = new Set();
    props.projects.forEach((p) => (p.tags ?? []).forEach((t) => set.add(t)));
    return [...set].sort((a, b) => a.localeCompare(b));
});

const visible = computed(() => {
    const q = search.value.trim().toLowerCase();

    let result = props.projects.filter((p) => {
        const matchesTag = !activeTag.value || (p.tags ?? []).includes(activeTag.value);
        const matchesSearch =
            !q ||
            p.title.toLowerCase().includes(q) ||
            (p.snippet ?? '').toLowerCase().includes(q) ||
            (p.tags ?? []).some((t) => t.toLowerCase().includes(q));
        return matchesTag && matchesSearch;
    });

    result = [...result].sort((a, b) =>
        sort.value === 'year-asc' ? a.year - b.year : b.year - a.year
    );

    return result;
});

function toggleTag(tag) {
    activeTag.value = activeTag.value === tag ? null : tag;
}
</script>

<template>
    <div>
        <!-- Controls -->
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <input
                v-model="search"
                type="search"
                placeholder="Search projects…"
                class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:focus:border-zinc-500 dark:focus:ring-0 md:max-w-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />

            <select
                v-model="sort"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:focus:border-zinc-500 dark:focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
                <option value="year-desc">Newest first</option>
                <option value="year-asc">Oldest first</option>
            </select>
        </div>

        <!-- Tag chips -->
        <div v-if="tags.length" class="mb-8 flex flex-wrap gap-2">
            <button
                v-for="tag in tags"
                :key="tag"
                type="button"
                @click="toggleTag(tag)"
                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                :class="
                    activeTag === tag
                        ? 'border-accent bg-accent text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                        : 'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-400'
                "
            >
                {{ tag }}
            </button>
        </div>

        <!-- Grid -->
        <div v-if="visible.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="project in visible"
                :key="project.slug"
                class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ project.title }}</h3>
                    <span class="text-xs text-zinc-400">{{ project.year }}</span>
                </div>

                <p class="mb-4 flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ project.snippet }}</p>

                <div class="mb-4 flex flex-wrap gap-1.5">
                    <span
                        v-for="tag in project.tags"
                        :key="tag"
                        class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                    >
                        {{ tag }}
                    </span>
                </div>

                <div class="flex gap-4 text-sm">
                    <a
                        v-if="project.links?.live"
                        :href="project.links.live"
                        target="_blank"
                        rel="noopener"
                        class="font-medium text-accent underline-offset-4 hover:underline dark:text-zinc-100"
                    >
                        Live
                    </a>
                    <a
                        v-if="project.links?.repo"
                        :href="project.links.repo"
                        target="_blank"
                        rel="noopener"
                        class="font-medium text-zinc-500 underline-offset-4 hover:underline dark:text-zinc-400"
                    >
                        Code
                    </a>
                </div>
            </article>
        </div>

        <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">No projects match your filters.</p>
    </div>
</template>
