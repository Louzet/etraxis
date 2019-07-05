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
 * A group page.
 */
new Vue({
    el: '#vue-group',

    created() {

        // Load group.
        this.reloadGroup();

        // Load list of group members.
        this.reloadMembers();

        // Load all available users.
        const loadAllUsers = (offset = 0) => {

            let headers = {
                'X-Sort': JSON.stringify({
                    fullname: 'ASC',
                    email: 'ASC'
                }),
            };

            axios.get(url(`/api/users?offset=${offset}`), { headers })
                .then(response => {

                    for (let user of response.data.data) {
                        this.allUsers.push(user);
                    }

                    if (response.data.to + 1 < response.data.total) {
                        loadAllUsers(response.data.to + 1);
                    }
                })
                .catch(exception => ui.errors(exception));
        };

        loadAllUsers();
    },

    components: {
        'modal': Modal,
        'tab':   Tab,
        'tabs':  Tabs,
    },

    data: {

        // Group's information.
        group: {},

        // Form contents.
        values: {},
        errors: {},

        // All existing users.
        allUsers: [],

        // Group members.
        groupMembers: [],

        // users selected to add to the group.
        usersToAdd: [],

        // Users selected to remove from the group.
        usersToRemove: [],
    },

    computed: {

        /**
         * @returns {Array<Object>} List of all users who are not members of the group.
         */
        otherUsers() {

            let ids = this.groupMembers.map(user => user.id);

            return this.allUsers.filter(user => ids.indexOf(user.id) === -1);
        },
    },

    methods: {

        /**
         * Reloads group.
         */
        reloadGroup() {

            ui.block();

            axios.get(url(`/api/groups/${eTraxis.groupId}`))
                .then(response => this.group = response.data)
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Reloads list of group members.
         */
        reloadMembers() {

            ui.block();

            axios.get(url(`/api/groups/${eTraxis.groupId}/members`))
                .then(response => {
                    this.groupMembers = response.data.sort((user1, user2) => {
                        if (user1.fullname === user2.fullname) {
                            return user1.email.localeCompare(user2.email);
                        }
                        else {
                            return user1.fullname.localeCompare(user2.fullname);
                        }
                    });
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Redirects back to list of groups.
         */
        goBack() {
            location.href = url('/admin/groups');
        },

        /**
         * Shows 'Edit group' dialog.
         */
        showEditGroupDialog() {

            this.values = {
                name:        this.group.name,
                description: this.group.description,
            };

            this.errors = {};

            this.$refs.dlgEditGroup.open();
        },

        /**
         * Updates the group.
         */
        updateGroup() {

            ui.block();

            axios.put(url(`/api/groups/${eTraxis.groupId}`), this.values)
                .then(() => {
                    ui.info(i18n['text.changes_saved'], () => {
                        this.$refs.dlgEditGroup.close();
                        this.reloadGroup();
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the group.
         */
        deleteGroup() {

            ui.confirm(i18n['confirm.group.delete'], () => {

                ui.block();

                axios.delete(url(`/api/groups/${eTraxis.groupId}`))
                    .then(() => {
                        location.href = url('/admin/groups');
                    })
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Adds selected users to the group.
         */
        addUsers() {

            ui.block();

            let data = {
                add: this.usersToAdd,
            };

            axios.patch(url(`/api/groups/${eTraxis.groupId}/members`), data)
                .then(() => this.reloadMembers())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Removes selected users from the group.
         */
        removeUsers() {

            ui.block();

            let data = {
                remove: this.usersToRemove,
            };

            axios.patch(url(`/api/groups/${eTraxis.groupId}/members`), data)
                .then(() => this.reloadMembers())
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
