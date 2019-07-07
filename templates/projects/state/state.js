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
        state: {},

        // Admin actions available for the state.
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
         * @property {Array<Object>} All states of the current template.
         */
        states() {
            return this.$store.state.states.list;
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

            this.actions = {};

            axios.get(url(`/api/states/${this.stateId}`))
                .then(response => {
                    this.state = response.data;
                    this.$store.commit('states/update', this.state);
                })
                .then(() => {
                    axios.get(url(`/admin/states/actions/${this.stateId}`))
                        .then(response => this.actions = response.data);
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
    },

    watch: {

        /**
         * Another state has been selected.
         *
         * @param {null|number} id State ID.
         */
        stateId(id) {

            if (id !== null) {
                this.reloadState();
            }
        }
    },
});
