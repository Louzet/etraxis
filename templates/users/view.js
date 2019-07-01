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
 * A user page.
 */
new Vue({
    el: '#vue-user',

    created() {

        // Load user's profile.
        this.reloadProfile();
    },

    components: {
        'modal': Modal,
        'tab':   Tab,
        'tabs':  Tabs,
    },

    data: {

        // User's profile.
        profile: {},

        // Form contents.
        values: {},
        errors: {},
    },

    computed: {

        /**
         * @returns {string} Human-readable provider.
         */
        provider() {
            return eTraxis.providers[this.profile.provider];
        },

        /**
         * @returns {string} Human-readable language.
         */
        language() {
            return eTraxis.locales[this.profile.locale];
        },

        /**
         * @returns {string} Human-readable theme.
         */
        theme() {
            return eTraxis.themes[this.profile.theme];
        },
    },

    methods: {

        /**
         * Reloads user's profile.
         */
        reloadProfile() {

            ui.block();

            axios.get(url(`/api/users/${eTraxis.userId}`))
                .then(response => this.profile = response.data)
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Redirects back to list of users.
         */
        goBack() {
            location.href = url('/admin/users');
        },

        /**
         * Shows 'Edit user' dialog.
         */
        showEditUserDialog() {

            this.values = {
                fullname:    this.profile.fullname,
                email:       this.profile.email,
                description: this.profile.description,
                locale:      this.profile.locale,
                theme:       this.profile.theme,
                timezone:    this.profile.timezone,
                admin:       this.profile.admin,
                disabled:    this.profile.disabled,
            };

            this.errors = {};

            this.$refs.dlgEditUser.open();
        },

        /**
         * Updates the user.
         */
        updateUser() {

            let data = {
                fullname:    this.values.fullname,
                email:       this.values.email,
                description: this.values.description,
                locale:      this.values.locale,
                theme:       this.values.theme,
                timezone:    this.values.timezone,
                admin:       this.values.admin,
                disabled:    this.values.disabled,
            };

            ui.block();

            axios.put(url(`/api/users/${eTraxis.userId}`), data)
                .then(() => {
                    ui.info(i18n['text.changes_saved'], () => {
                        this.$refs.dlgEditUser.close();
                        this.reloadProfile();
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the user.
         */
        deleteUser() {

            ui.confirm(i18n['confirm.user.delete'], () => {

                ui.block();

                axios.delete(url(`/api/users/${eTraxis.userId}`))
                    .then(() => {
                        location.href = url('/admin/users');
                    })
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Disables the user.
         */
        disableUser() {

            ui.block();

            let data = {
                users: [eTraxis.userId],
            };

            axios.post(url('/api/users/disable'), data)
                .then(() => this.reloadProfile())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Enables the user.
         */
        enableUser() {

            ui.block();

            let data = {
                users: [eTraxis.userId],
            };

            axios.post(url('/api/users/enable'), data)
                .then(() => this.reloadProfile())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
