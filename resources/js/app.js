import { createApp } from 'vue';
import ProjectsExplorer from './components/ProjectsExplorer.vue';

// Mount the Vue "island" for the projects section if it exists on the page.
// Project data is hydrated from a JSON blob embedded in the server-rendered HTML,
// so filtering/sorting runs entirely client-side with no extra requests.
const el = document.getElementById('projects-explorer');

if (el) {
    const projects = JSON.parse(el.dataset.projects ?? '[]');

    createApp(ProjectsExplorer, { projects }).mount(el);
}
