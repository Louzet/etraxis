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

import Column    from 'components/datatable/column';
import DataTable from 'components/datatable/datatable.vue';
import ui        from 'utilities/ui';
import url       from 'utilities/url';

import { PROVIDER_ETRAXIS   } from 'utilities/const';
import { PROVIDER_LDAP      } from 'utilities/const';
import { PROVIDER_BITBUCKET } from 'utilities/const';
import { PROVIDER_GITHUB    } from 'utilities/const';
import { PROVIDER_GOOGLE    } from 'utilities/const';

import DlgUser from './dlg_user.vue';

/**
 * 'Users' page.
 */
new Vue({
    el: '#vue-users',

    created() {

        // 'Permissions' column filter.
        this.columns[2].filter = {
            1: i18n['role.admin'],
            0: i18n['role.user'],
        };

        // 'Authentication' column filter.
        this.columns[3].filter = {};
        this.columns[3].filter[PROVIDER_ETRAXIS]   = 'eTraxis';
        this.columns[3].filter[PROVIDER_LDAP]      = 'LDAP';
        this.columns[3].filter[PROVIDER_BITBUCKET] = 'Bitbucket';
        this.columns[3].filter[PROVIDER_GITHUB]    = 'GitHub';
        this.columns[3].filter[PROVIDER_GOOGLE]    = 'Google';
    },

    components: {
        'datatable': DataTable,
        'dlg-user':  DlgUser,
    },

    data: {

        // Table columns definition.
        columns: [
            new Column('fullname',    i18n['user.fullname']),
            new Column('email',       i18n['user.email']),
            new Column('admin',       i18n['user.permissions']),
            new Column('provider',    i18n['user.authentication']),
            new Column('description', i18n['user.description'], '100%'),
        ],

        // List of user IDs whose rows are checked.
        checked: [],

        // Form contents.
        values: {},
        errors: {},
    },

    computed: {

        // Translation resources.
        i18n: () => i18n,
    },

    methods: {

        /**
         * Data provider for the table.
         *
         * @param  {Request} request Request from the DataTable component.
         * @return {Promise} Promise of response.
         */
        users(request) {

            return axios.datatable(url('/api/users'), request, user => {

                let status = null, provider = 'Unknown';

                if (user.locked) {
                    status = 'attention';
                }
                else if (user.disabled) {
                    status = 'muted';
                }
                else if (user.expired) {
                    status = 'pending';
                }

                if (user.provider === 'etraxis') {
                    provider = 'eTraxis';
                }
                else if (user.provider === 'ldap') {
                    provider = 'LDAP';
                }

                return {
                    DT_id:        user.id,
                    DT_class:     status,
                    DT_checkable: user.id !== eTraxis.currentUser,
                    fullname:     user.fullname,
                    email:        user.email,
                    admin:        user.admin ? i18n['role.admin'] : i18n['role.user'],
                    provider:     provider,
                    description:  user.description,
                };
            });
        },

        /**
         * A set of checked rows in the table is changed.
         *
         * @param {Array} ids List of checked rows (user IDs).
         */
        onCheck(ids) {
            this.checked = ids;
        },

        /**
         * A row with an account is clicked.
         *
         * @param {number} id Account ID.
         */
        viewUser(id) {
            location.href = url('/admin/users/' + id);
        },

        /**
         * Shows 'New user' dialog.
         */
        showNewUserDialog() {

            this.values = {
                fullname:    null,
                email:       null,
                description: null,
                locale:      eTraxis.defaultLocale,
                theme:       eTraxis.defaultTheme,
                timezone:    eTraxis.defaultTimezone,
                admin:       false,
                disabled:    false,
            };

            this.errors = {};

            this.$refs.dlgUser.open();
        },

        /**
         * Creates new user.
         *
         * @param {Object} event Event data.
         */
        createUser(event) {

            let data = {
                fullname:    event.fullname,
                email:       event.email,
                description: event.description,
                password:    event.password,
                locale:      event.locale,
                theme:       event.theme,
                timezone:    event.timezone,
                admin:       event.admin,
                disabled:    event.disabled,
            };

            ui.block();

            axios.post(url('/api/users'), data)
                .then(() => {
                    ui.info(i18n['user.successfully_created'], () => {
                        this.$refs.dlgUser.close();
                        this.$refs.users.refresh();
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Disables selected users.
         */
        disableUsers() {

            ui.block();

            let data = {
                users: this.checked,
            };

            axios.post(url('/api/users/disable'), data)
                .then(() => this.$refs.users.refresh())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Enables selected users.
         */
        enableUsers() {

            ui.block();

            let data = {
                users: this.checked,
            };

            axios.post(url('/api/users/enable'), data)
                .then(() => this.$refs.users.refresh())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
