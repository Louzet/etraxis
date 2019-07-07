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
import List  from 'components/panel/list.vue';
import Panel from 'components/panel/panel.vue';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

// State types.
const STATE_INITIAL      = 'initial';
const STATE_INTERMEDIATE = 'intermediate';
const STATE_FINAL        = 'final';

// State responsibility values.
const STATE_KEEP   = 'keep';
const STATE_ASSIGN = 'assign';
const STATE_REMOVE = 'remove';

/**
 * 'Projects' page (left side with panels).
 */
new Vue({
    el: '#vue-sidebar',
    store: eTraxis.store,

    created() {
        this.$store.dispatch('projects/load');
    },

    components: {
        'list':  List,
        'modal': Modal,
        'panel': Panel,
    },

    data: {

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
         * @property {Array<Object>} All existing projects.
         */
        projects() {
            return this.$store.state.projects.list;
        },

        /**
         * @property {Array<Object>} All templates of the current project.
         */
        templates() {
            return this.$store.state.templates.list;
        },

        /**
         * @property {Array<Object>} All states of the current template.
         */
        states() {
            return this.$store.state.states.list;
        },

        /**
         * @property {Array<Object>} Initial states of the current template.
         */
        initialStates() {
            return this.$store.state.states.list.filter(state => state.type === STATE_INITIAL);
        },

        /**
         * @property {Array<Object>} Intermediate states of the current template.
         */
        intermediateStates() {
            return this.$store.state.states.list.filter(state => state.type === STATE_INTERMEDIATE);
        },

        /**
         * @property {Array<Object>} Final states of the current template.
         */
        finalStates() {
            return this.$store.state.states.list.filter(state => state.type === STATE_FINAL);
        },

        /**
         * @property {Array<Object>} All fields of the current state.
         */
        fields() {
            return this.$store.state.fields.list;
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
         * @property {null|number} Currently selected template.
         */
        templateId: {
            get() {
                return this.$store.state.templates.currentId;
            },
            set(value) {
                this.$store.commit('templates/current', value);
            },
        },

        /**
         * @property {null|number} Currently selected state.
         */
        stateId: {
            get() {
                return this.$store.state.states.currentId;
            },
            set(value) {
                this.$store.commit('states/current', value);
            },
        },

        /**
         * @property {null|number} Currently selected field.
         */
        fieldId: {
            get() {
                return this.$store.state.fields.currentId;
            },
            set(value) {
                this.$store.commit('fields/current', value);
            },
        },
    },

    methods: {

        /**
         * Shows 'New project' dialog.
         */
        showNewProjectDialog() {

            this.values = {
                suspended: true,
            };

            this.errors = {};

            this.$refs.dlgNewProject.open();
        },

        /**
         * Creates new project.
         */
        createProject() {

            let data = {
                name:        this.values.name,
                description: this.values.description,
                suspended:   this.values.suspended,
            };

            ui.block();

            axios.post(url('/api/projects'), data)
                .then(async response => {
                    this.$refs.dlgNewProject.close();
                    await eTraxis.store.dispatch('projects/load')
                        .then(() => {
                            let location = response.headers.location;
                            this.projectId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'New template' dialog.
         */
        showNewTemplateDialog() {

            this.values = {};
            this.errors = {};

            this.$refs.dlgNewTemplate.open();
        },

        /**
         * Creates new template.
         */
        createTemplate() {

            let data = {
                project:     this.projectId,
                name:        this.values.name,
                prefix:      this.values.prefix,
                description: this.values.description,
                critical:    this.values.critical,
                frozen:      this.values.frozen,
            };

            ui.block();

            axios.post(url('/api/templates'), data)
                .then(async response => {
                    this.$refs.dlgNewTemplate.close();
                    await eTraxis.store.dispatch('templates/load', data.project)
                        .then(() => {
                            let location = response.headers.location;
                            this.templateId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'New state' dialog.
         */
        showNewStateDialog() {

            let template = eTraxis.store.state.templates.list.find(template => template.id === this.templateId);

            if (template.class === null) {
                ui.info(i18n['template.must_be_locked']);
                return;
            }

            this.values = {
                type:        STATE_INTERMEDIATE,
                responsible: STATE_KEEP,
            };

            this.errors = {};

            this.$refs.dlgNewState.open();
        },

        /**
         * Creates new state.
         */
        createState() {

            let data = {
                template:    this.templateId,
                name:        this.values.name,
                type:        this.values.type,
                responsible: this.values.type === STATE_FINAL ? STATE_REMOVE : this.values.responsible,
                next:        this.values.type === STATE_FINAL ? null : this.values.next,
            };

            ui.block();

            axios.post(url('/api/states'), data)
                .then(async response => {
                    this.$refs.dlgNewState.close();
                    await eTraxis.store.dispatch('states/load', data.template)
                        .then(() => {
                            let location = response.headers.location;
                            this.stateId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Project has been selected.
         *
         * @param {number} id Project ID.
         */
        projectId(id) {

            this.templateId = null;

            if (id !== null) {
                this.$store.dispatch('templates/load', id);
            }
        },

        /**
         * Template has been selected.
         *
         * @param {number} id Template ID.
         */
        templateId(id) {

            this.stateId = null;

            if (id !== null) {
                this.$store.dispatch('states/load', id);
            }
        },

        /**
         * State has been selected.
         *
         * @param {number} id State ID.
         */
        stateId(id) {

            this.fieldId = null;

            if (id !== null) {
                this.$store.dispatch('fields/load', id);
            }
        },
    },
});
