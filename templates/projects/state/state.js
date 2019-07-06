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

import Tab  from 'components/tabs/tab.vue';
import Tabs from 'components/tabs/tabs.vue';
import ui   from 'utilities/ui';
import url  from 'utilities/url';

/**
 * 'Projects' page (state view).
 */
new Vue({
    el: '#vue-state',
    store: eTraxis.store,

    components: {
        'tab':  Tab,
        'tabs': Tabs,
    },

    data: {

        // State info.
        state: {},
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
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

            axios.get(url(`/api/states/${this.stateId}`))
                .then(response => {
                    this.state = response.data;
                    this.$store.commit('states/update', this.state);
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
