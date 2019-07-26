<template>

    <div class="grid-row flex">

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="legend">{{ i18n['group.members'] }}</div>
                <div class="grid-row">
                    <div class="grid-100">
                        <select class="grid-100 mobile-grid-100" size="20" multiple="multiple" :disabled="groupMembers.length === 0" v-model="usersToRemove">
                            <option v-for="user in groupMembers" :value="user.id">{{ user.fullname }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-10 mobile-grid-100 flex-center">
            <div class="grid-row hide-on-mobile">
                <div class="grid-100">
                    <button class="full-width fa fa-arrow-right" :title="i18n['button.remove']" :disabled="usersToRemove.length === 0" @click="removeUsers"></button>
                </div>
                <div class="grid-100">
                    <button class="full-width fa fa-arrow-left" :title="i18n['button.add']" :disabled="usersToAdd.length === 0" @click="addUsers"></button>
                </div>
            </div>
            <div class="grid-row hide-on-desktop text-center">
                <div class="mobile-grid-50">
                    <button class="full-width fa fa-arrow-down" :title="i18n['button.remove']" :disabled="usersToRemove.length === 0" @click="removeUsers"></button>
                </div>
                <div class="mobile-grid-50">
                    <button class="full-width fa fa-arrow-up" :title="i18n['button.add']" :disabled="usersToAdd.length === 0" @click="addUsers"></button>
                </div>
            </div>
        </div>

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="legend">{{ i18n['users.others'] }}</div>
                <div class="grid-row">
                    <div class="grid-100">
                        <select class="grid-100 mobile-grid-100" size="20" multiple="multiple" :disabled="otherUsers.length === 0" v-model="usersToAdd">
                            <option v-for="user in otherUsers" :value="user.id">{{ user.fullname }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </div>

</template>

<script>

    import ui  from 'utilities/ui';
    import url from 'utilities/url';

    export default {

        created() {

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

        props: {

            /**
             * Group members.
             */
            members: {
                type: Array,
                required: true,
            },
        },

        data: () => ({

            // All existing users.
            allUsers: [],

            // users selected to add to the group.
            usersToAdd: [],

            // Users selected to remove from the group.
            usersToRemove: [],
        }),

        computed: {

            /**
             * @property {Array<Object>} List of group members.
             */
            groupMembers() {
                return this.members;
            },

            /**
             * @property {Array<Object>} List of all users who are not members of the group.
             */
            otherUsers() {

                let ids = this.members.map(user => user.id);

                return this.allUsers.filter(user => ids.indexOf(user.id) === -1);
            },

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

            /**
             * Adds selected users to the group.
             */
            addUsers() {

                ui.block();

                let data = {
                    add: this.usersToAdd,
                };

                axios.patch(url(`/api/groups/${eTraxis.groupId}/members`), data)
                    .then(() => this.$emit('reload'))
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
                    .then(() => this.$emit('reload'))
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            },
        },
    };

</script>
