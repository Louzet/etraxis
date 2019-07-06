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
 * 'Projects' page (field view).
 */
new Vue({
    el: '#vue-field',
    store: eTraxis.store,

    components: {
        'tab':  Tab,
        'tabs': Tabs,
    },

    data: {

        // Field info.
        field: {},
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
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

        /**
         * @property {string} Human-readable field type.
         */
        type() {
            return i18n[eTraxis.field_types[this.field.type]];
        },

        /**
         * @property {string} Human-readable field's default value.
         */
        defaultValue() {

            if (this.field.default !== null) {

                if (this.field.type === 'checkbox') {
                    return this.field.default ? i18n['field.on'] : i18n['field.off'];
                }

                if (this.field.type === 'list') {
                    return this.field.default.text;
                }
            }

            return this.field.default;
        },
    },

    methods: {

        /**
         * Reloads field info.
         */
        reloadField() {

            ui.block();

            axios.get(url(`/api/fields/${this.fieldId}`))
                .then(response => {
                    this.field = response.data;
                    this.$store.commit('fields/update', this.field);
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another field has been selected.
         *
         * @param {null|number} id Field ID.
         */
        fieldId(id) {

            if (id !== null) {
                this.reloadField();
            }
        }
    },
});
