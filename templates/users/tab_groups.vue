<template>

    <div class="grid-row flex">

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="legend">{{ i18n['user.groups'] }}</div>
                <div class="grid-row">
                    <div class="grid-100">
                        <select class="grid-100 mobile-grid-100" size="20" multiple="multiple" :disabled="userGroups.length === 0" v-model="groupsToRemove">
                            <option v-for="group in userGroups" :value="group.id">{{ group.name }} ({{ group.global ? i18n['group.global'].toLowerCase() : group.project.name }})</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-10 mobile-grid-100 flex-center">
            <div class="grid-row hide-on-mobile">
                <div class="grid-100">
                    <button class="full-width fa fa-arrow-right" :title="i18n['button.remove']" :disabled="groupsToRemove.length === 0" @click="removeGroups"></button>
                </div>
                <div class="grid-100">
                    <button class="full-width fa fa-arrow-left" :title="i18n['button.add']" :disabled="groupsToAdd.length === 0" @click="addGroups"></button>
                </div>
            </div>
            <div class="grid-row hide-on-desktop text-center">
                <div class="mobile-grid-50">
                    <button class="full-width fa fa-arrow-down" :title="i18n['button.remove']" :disabled="groupsToRemove.length === 0" @click="removeGroups"></button>
                </div>
                <div class="mobile-grid-50">
                    <button class="full-width fa fa-arrow-up" :title="i18n['button.add']" :disabled="groupsToAdd.length === 0" @click="addGroups"></button>
                </div>
            </div>
        </div>

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="legend">{{ i18n['groups.others'] }}</div>
                <div class="grid-row">
                    <div class="grid-100">
                        <select class="grid-100 mobile-grid-100" size="20" multiple="multiple" :disabled="otherGroups.length === 0" v-model="groupsToAdd">
                            <option v-for="group in otherGroups" :value="group.id">{{ group.name }} ({{ group.global ? i18n['group.global'].toLowerCase() : group.project.name }})</option>
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

            // Load all available groups.
            const loadAllGroups = (offset = 0) => {

                let headers = {
                    'X-Sort': JSON.stringify({
                        project: 'ASC',
                        name: 'ASC'
                    }),
                };

                axios.get(url(`/api/groups?offset=${offset}`), { headers })
                    .then(response => {

                        for (let group of response.data.data) {
                            this.allGroups.push(group);
                        }

                        if (response.data.to + 1 < response.data.total) {
                            loadAllGroups(response.data.to + 1);
                        }
                    })
                    .catch(exception => ui.errors(exception));
            };

            loadAllGroups();
        },

        props: {

            /**
             * Groups the user is a member of.
             */
            groups: {
                type: Array,
                required: true,
            },
        },

        data: () => ({

            // All existing groups.
            allGroups: [],

            // Groups selected to add.
            groupsToAdd: [],

            // Groups selected to remove.
            groupsToRemove: [],
        }),

        computed: {

            /**
             * @property {Array<Object>} List of groups the user is a member of.
             */
            userGroups() {
                return this.groups;
            },

            /**
             * @property {Array<Object>} List of all groups which the user is not a member of.
             */
            otherGroups() {

                let ids = this.groups.map(group => group.id);

                return this.allGroups.filter(group => ids.indexOf(group.id) === -1);
            },

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

            /**
             * Adds the user to selected groups.
             */
            addGroups() {

                ui.block();

                let data = {
                    add: this.groupsToAdd,
                };

                axios.patch(url(`/api/users/${eTraxis.userId}/groups`), data)
                    .then(() => this.$emit('reload'))
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            },

            /**
             * Removes the user from selected groups.
             */
            removeGroups() {

                ui.block();

                let data = {
                    remove: this.groupsToRemove,
                };

                axios.patch(url(`/api/users/${eTraxis.userId}/groups`), data)
                    .then(() => this.$emit('reload'))
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            },
        },
    };

</script>
