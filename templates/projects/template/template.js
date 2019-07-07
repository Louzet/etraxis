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
 * 'Projects' page (template view).
 */
new Vue({
    el: '#vue-template',
    store: eTraxis.store,

    components: {
        'modal': Modal,
        'tab':   Tab,
        'tabs':  Tabs,
    },

    data: {

        // Template info.
        template: {},

        // Admin actions available for the template.
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

            this.actions = {};

            axios.get(url(`/api/templates/${this.templateId}`))
                .then(response => {
                    this.template = response.data;
                    this.$store.commit('templates/update', this.template);
                })
                .then(() => {
                    axios.get(url(`/admin/templates/actions/${this.templateId}`))
                        .then(response => this.actions = response.data);
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit template' dialog.
         */
        showEditTemplateDialog() {

            this.values = {
                name:        this.template.name,
                prefix:      this.template.prefix,
                description: this.template.description,
                critical:    this.template.critical,
                frozen:      this.template.frozen,
            };

            this.errors = {};

            this.$refs.dlgEditTemplate.open();
        },

        /**
         * Updates the template.
         */
        updateTemplate() {

            let data = {
                name:        this.values.name,
                prefix:      this.values.prefix,
                description: this.values.description,
                critical:    this.values.critical,
                frozen:      this.values.frozen,
            };

            ui.block();

            axios.put(url(`/api/templates/${this.templateId}`), data)
                .then(() => {
                    this.reloadTemplate();
                    this.$refs.dlgEditTemplate.close();
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the template.
         */
        deleteTemplate() {

            ui.confirm(i18n['confirm.template.delete'], () => {

                ui.block();

                axios.delete(url(`/api/templates/${this.templateId}`))
                    .then(() => {
                        this.$store.dispatch('templates/load', this.template.project.id);
                        this.templateId = null;
                    })
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Locks the template.
         */
        lockTemplate() {

            ui.block();

            axios.post(url(`/api/templates/${this.templateId}/lock`))
                .then(() => {
                    this.reloadTemplate();
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Unlocks the template.
         */
        unlockTemplate() {

            ui.block();

            axios.post(url(`/api/templates/${this.templateId}/unlock`))
                .then(() => {
                    this.reloadTemplate();
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
