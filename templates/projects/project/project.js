//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

import Modal from 'components/modal/modal.vue';
import Tab   from 'components/tabs/tab.vue';
import Tabs  from 'components/tabs/tabs.vue';
import epoch from 'utilities/epoch';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (project view).
 */
new Vue({
    el: '#vue-project',
    store: eTraxis.store,

    components: {
        'modal': Modal,
        'tab':   Tab,
        'tabs':  Tabs,
    },

    data: {

        // Project info.
        project: {},

        // Admin actions available for the project.
        actions: {},

        // Form contents.
        values: {},
        errors: {},
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
        },

        /**
         * @property {null|number} Currently selected project.
         */
        projectId: {
            get() {
                return this.$store.state.projects.currentId;
            },
            set(value) {
                this.$store.commit('projects/current', value);
            },
        },

        /**
         * @property {null|string} Human-readable start date.
         */
        startDate() {
            return this.project.created ? epoch.date(this.project.created) : null;
        },
    },

    methods: {

        /**
         * Reloads project info.
         */
        reloadProject() {

            ui.block();

            this.actions = {};

            axios.get(url(`/api/projects/${this.projectId}`))
                .then(response => {
                    this.project = response.data;
                    this.$store.commit('projects/update', this.project);
                })
                .then(() => {
                    axios.get(url(`/admin/projects/actions/${this.projectId}`))
                        .then(response => this.actions = response.data);
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit project' dialog.
         */
        showEditProjectDialog() {

            this.values = {
                name:        this.project.name,
                description: this.project.description,
                suspended:   this.project.suspended,
            };

            this.errors = {};

            this.$refs.dlgEditProject.open();
        },

        /**
         * Updates the project.
         */
        updateProject() {

            let data = {
                name:        this.values.name,
                description: this.values.description,
                suspended:   this.values.suspended,
            };

            ui.block();

            axios.put(url(`/api/projects/${this.projectId}`), data)
                .then(() => {
                    this.reloadProject();
                    this.$refs.dlgEditProject.close();
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the project.
         */
        deleteProject() {

            ui.confirm(i18n['confirm.project.delete'], () => {

                ui.block();

                axios.delete(url(`/api/projects/${this.projectId}`))
                    .then(() => {
                        this.projectId = null;
                        this.$store.dispatch('projects/load');
                    })
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Suspends the project.
         */
        suspendProject() {

            ui.block();

            axios.post(url(`/api/projects/${this.projectId}/suspend`))
                .then(() => {
                    this.reloadProject();
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Resumes the project.
         */
        resumeProject() {

            ui.block();

            axios.post(url(`/api/projects/${this.projectId}/resume`))
                .then(() => {
                    this.reloadProject();
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another project has been selected.
         *
         * @param {null|number} id Project ID.
         */
        projectId(id) {

            if (id !== null) {
                this.reloadProject();
            }
        }
    },
});
