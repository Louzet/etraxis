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
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (state view).
 */
new Vue({
    el: '#vue-state',
    store: eTraxis.store,

    components: {
        'modal': Modal,
        'tab':   Tab,
        'tabs':  Tabs,
    },

    data: {

        // State info.
        state: {
            options: {},
        },

        // Form contents.
        values: {},
        errors: {},

        // State transitions.
        transitions: {},

        // Currently selected transition (state ID).
        transition: null,

        // List of system roles currently ticked.
        roles: [],

        // List of groups currently ticked.
        groups: [],
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
        },

        /**
         * @property {Array<Object>} All states of the current template.
         */
        states() {
            return this.$store.state.states.list;
        },

        /**
         * @property {Array<Object>} All global groups.
         */
        globalGroups() {
            return this.$store.state.groups.global;
        },

        /**
         * @property {Array<Object>} All local groups of the current project.
         */
        localGroups() {
            return this.$store.state.groups.local;
        },

        /**
         * @property {null|number} Currently selected template.
         */
        templateId() {
            return this.$store.state.templates.currentId;
        },

        /**
         * @property {boolean} Whether the current template is locked.
         */
        isLocked() {
            return this.$store.state.templates.list.find(template => template.id === this.$store.state.templates.currentId).locked;
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
         * @property {string} Human-readable state type.
         */
        type() {
            return i18n[eTraxis.state_types[this.state.type]];
        },

        /**
         * @property {string} Human-readable state responsible.
         */
        responsible() {
            return i18n[eTraxis.state_responsibles[this.state.responsible]];
        },

        /**
         * @property {null|string} Human-readable next state.
         */
        next() {
            let state = this.$store.state.states.list.find(state => state.id === this.state.next);
            return state ? state.title : null;
        },
    },

    methods: {

        /**
         * Reloads state info.
         */
        reloadState() {

            ui.block();

            this.transitions = this.states.map(state => ({
                state:  state.id,
                roles:  [],
                groups: [],
            }));

            axios.get(url(`/api/states/${this.stateId}`))
                .then(response => {
                    this.state = response.data;
                    this.$store.commit('states/update', this.state);
                })
                .then(() => {
                    axios.get(url(`/api/states/${this.stateId}/transitions`))
                        .then(response => {

                            for (let entry of response.data.roles) {
                                let transition = this.transitions.find(x => x.state === entry.state);
                                transition.roles.push(entry.role);
                            }

                            for (let entry of response.data.groups) {
                                let transition = this.transitions.find(x => x.state === entry.state);
                                transition.groups.push(entry.group);
                            }

                            let transition = this.transitions.find(x => x.state === this.transition);

                            this.roles  = transition ? transition.roles : [];
                            this.groups = transition ? transition.groups : [];
                        });
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit state' dialog.
         */
        showEditStateDialog() {

            this.values = {
                name:        this.state.name,
                type:        this.state.type,
                responsible: this.state.responsible,
                next:        this.state.next,
            };

            this.errors = {};

            this.$refs.dlgEditState.open();
        },

        /**
         * Updates the state.
         */
        updateState() {

            let data = {
                name:        this.values.name,
                responsible: this.values.responsible,
                next:        this.values.next,
            };

            ui.block();

            axios.put(url(`/api/states/${this.stateId}`), data)
                .then(() => {
                    this.reloadState();
                    this.$refs.dlgEditState.close();
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the state.
         */
        deleteState() {

            ui.confirm(i18n['confirm.state.delete'], () => {

                ui.block();

                axios.delete(url(`/api/states/${this.stateId}`))
                    .then(() => {
                        this.$store.dispatch('states/load', this.state.template.id);
                        this.stateId = null;
                    })
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Makes the state an initial one.
         */
        setInitial() {

            ui.block();

            axios.post(url(`/api/states/${this.stateId}/initial`))
                .then(() => {
                    this.$store.dispatch('states/load', this.state.template.id);
                    this.reloadState();
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Selects all roles and groups for currently selected transition of the state.
         */
        selectAllTransition() {

            this.roles = Object.keys(eTraxis.system_roles);

            this.groups = Array.concat(
                this.localGroups.map(group => group.id),
                this.globalGroups.map(group => group.id),
            );
        },

        /**
         * Saves currently selected transition of the state.
         */
        saveTransition() {

            let data = {
                state:  this.transition,
                roles:  this.roles,
                groups: this.groups,
            };

            ui.block();

            axios.put(url(`/api/states/${this.stateId}/transitions`), data)
                .then(() => {

                    ui.info(i18n['text.changes_saved'], () => {
                        let transition = this.transitions.find(x => x.state === this.transition);

                        transition.roles  = this.roles;
                        transition.groups = this.groups;
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another template has been selected.
         *
         * @param {null|number} id Template ID.
         */
        templateId(id) {

            this.transition = null;
        },

        /**
         * Another state has been selected.
         *
         * @param {null|number} id State ID.
         */
        stateId(id) {

            if (id !== null) {
                this.reloadState();
            }
        },

        /**
         * Another transition has been selected.
         *
         * @param {null|string} value State ID.
         */
        transition(value) {

            if (value === null) {
                this.roles  = [];
                this.groups = [];
            }
            else {
                let transition = this.transitions.find(x => x.state === value);

                this.roles  = transition.roles;
                this.groups = transition.groups;
            }
        },
    },
});
