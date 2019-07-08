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

        // Template permissions.
        permissions: {},

        // Currently selected permission.
        permission: null,

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
    },

    methods: {

        /**
         * Reloads template info.
         */
        reloadTemplate() {

            ui.block();

            this.permissions = Object.keys(eTraxis.template_permissions).map(permission => ({
                permission: permission,
                roles:      [],
                groups:     [],
            }));

            this.actions = {};

            axios.get(url(`/api/templates/${this.templateId}`))
                .then(response => {
                    this.template = response.data;
                    this.$store.commit('templates/update', this.template);
                })
                .then(() => {
                    axios.get(url(`/api/templates/${this.templateId}/permissions`))
                        .then(response => {

                            for (let entry of response.data.roles) {
                                let permission = this.permissions.find(x => x.permission === entry.permission);
                                permission.roles.push(entry.role);
                            }

                            for (let entry of response.data.groups) {
                                let permission = this.permissions.find(x => x.permission === entry.permission);
                                permission.groups.push(entry.group);
                            }

                            let permission = this.permissions.find(x => x.permission === this.permission);

                            this.roles  = permission ? permission.roles : [];
                            this.groups = permission ? permission.groups : [];
                        });
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

        /**
         * Selects all roles and groups for currently selected permission of the template.
         */
        selectAllPermission() {

            this.roles = Object.keys(eTraxis.system_roles);

            this.groups = Array.concat(
                this.localGroups.map(group => group.id),
                this.globalGroups.map(group => group.id),
            );
        },

        /**
         * Saves currently selected permission of the template.
         */
        savePermission() {

            let data = {
                permission: this.permission,
                roles:      this.roles,
                groups:     this.groups,
            };

            ui.block();

            axios.put(url(`/api/templates/${this.templateId}/permissions`), data)
                .then(() => {

                    ui.info(i18n['text.changes_saved'], () => {
                        let permission = this.permissions.find(x => x.permission === this.permission);

                        permission.roles  = this.roles;
                        permission.groups = this.groups;
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

            if (id !== null) {
                this.reloadTemplate();
            }
        },

        /**
         * Another permission has been selected.
         *
         * @param {null|string} value Permission ID.
         */
        permission(value) {

            if (value === null) {
                this.roles  = [];
                this.groups = [];
            }
            else {
                let permission = this.permissions.find(x => x.permission === value);

                this.roles  = permission.roles;
                this.groups = permission.groups;
            }
        },
    },
});
