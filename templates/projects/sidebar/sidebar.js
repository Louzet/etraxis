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

import List  from 'components/panel/list.vue';
import Panel from 'components/panel/panel.vue';

// State types.
const STATE_INITIAL      = 'initial';
const STATE_INTERMEDIATE = 'intermediate';
const STATE_FINAL        = 'final';

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
        'panel': Panel,
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
