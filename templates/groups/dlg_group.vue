<template>

    <modal ref="modal" :header="header" @submit="submit">
        <div class="fieldset">

            <div class="grid-row">
                <div class="grid-33">
                    <label for="name" :title="i18n['group.name']">{{ i18n['group.name'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="text" id="name" :placeholder="i18n['input.required']" v-model="values.name">
                    <p class="attention">{{ errors.name }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="description" :title="i18n['group.description']">{{ i18n['group.description'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="text" id="description" v-model="values.description">
                    <p class="attention">{{ errors.description }}</p>
                </div>
            </div>

            <div v-if="showProject" class="grid-row">
                <div class="grid-33">
                    <label for="project" :title="i18n['project']">{{ i18n['project'] }}:</label>
                </div>
                <div class="grid-66">
                    <select class="grid-100 mobile-grid-100" id="project" v-model="values.project">
                        <option value="">{{ i18n['group.global'] }}</option>
                        <option v-for="project in projects" :value="project.id">{{ project.name }}</option>
                    </select>
                    <p class="attention">{{ errors.project }}</p>
                </div>
            </div>

        </div>
    </modal>

</template>

<script>

    import Modal from 'components/modal/modal.vue';

    export default {

        components: {
            'modal': Modal,
        },

        props: {

            /**
             * Dialog header.
             */
            header: {
                type: String,
                required: true,
            },

            /**
             * Default form values.
             */
            default: {
                type: Object,
                required: true,
            },

            /**
             * Whether to show 'Project' field.
             */
            showProject: {
                type: Boolean,
                default: true,
            },

            /**
             * Form errors.
             */
            errors: {
                type: Object,
                required: true,
            },
        },

        data: () => ({

            // Current form values.
            values: {
                name:        null,
                description: null,
                project:     null,
            },
        }),

        computed: {

            // List of available projects.
            projects: () => eTraxis.projects,

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

            /**
             * @external Opens the dialog.
             */
            open() {
                Object.assign(this.values, this.default);
                this.$refs.modal.open();
            },

            /**
             * @external Closes the dialog.
             */
            close() {
                this.$refs.modal.close();
            },

            /**
             * Submits current form values.
             */
            submit() {
                this.$emit('submit', this.values);
            },
        },

        watch: {

            /**
             * Default values has been changed.
             *
             * @param {Object} values New default values.
             */
            default(values) {
                Object.assign(this.values, values);
            }
        },
    };

</script>
