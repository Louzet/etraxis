<template>

    <div class="grid-row">

        <dlg-group ref="dlgGroup" :header="i18n['group.edit']" :default="group" :show-project="false" :errors="errors" @submit="updateGroup"></dlg-group>

        <div class="grid-100">
            <button @click="goBack">{{ i18n['button.back'] }}</button>
            <button v-if="group.options['group.update']" @click="showEditGroupDialog">{{ i18n['button.edit'] }}</button>
            <button v-if="group.options['group.delete']" class="danger" @click="deleteGroup">{{ i18n['button.delete'] }}</button>
        </div>

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['group.name'] }}:</p></div>
                    <div class="grid-75"><p>{{ group.name }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['group.description'] }}:</p></div>
                    <div class="grid-75"><p>{{ group.description || '&mdash;' }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['project'] }}:</p></div>
                    <div class="grid-75"><p>{{ group.project ? group.project.name : '&mdash;' }}</p></div>
                </div>
            </div>
        </div>

    </div>

</template>

<script>

    import ui  from 'utilities/ui';
    import url from 'utilities/url';

    import DlgGroup from './dlg_group.vue';

    export default {

        components: {
            'dlg-group': DlgGroup,
        },

        props: {

            /**
             * Group's information.
             */
            group: {
                type: Object,
                required: true,
            },
        },

        data: () => ({

            // Form contents.
            errors: {},
        }),

        computed: {

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

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

                this.errors = {};

                this.$refs.dlgGroup.open();
            },

            /**
             * Updates the group.
             *
             * @param {Object} event Event data.
             */
            updateGroup(event) {

                let data = {
                    name:        event.name,
                    description: event.description,
                };

                ui.block();

                axios.put(url(`/api/groups/${eTraxis.groupId}`), data)
                    .then(() => {
                        ui.info(i18n['text.changes_saved'], () => {
                            this.$refs.dlgGroup.close();
                            this.$emit('reload');
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
        },
    };

</script>
