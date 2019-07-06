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
 * 'Projects' page (template view).
 */
new Vue({
    el: '#vue-template',
    store: eTraxis.store,

    components: {
        'tab':  Tab,
        'tabs': Tabs,
    },

    data: {

        // Template info.
        template: {},
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
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
    },

    methods: {

        /**
         * Reloads template info.
         */
        reloadTemplate() {

            ui.block();

            axios.get(url(`/api/templates/${this.templateId}`))
                .then(response => {
                    this.template = response.data;
                    this.$store.commit('templates/update', this.template);
                })
                .catch(exception => ui.errors(exception))
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

            if (id !== null) {
                this.reloadTemplate();
            }
        }
    },
});
