<template>

    <modal ref="modal" :header="header" @submit="submit">
        <div class="fieldset">

            <div v-if="!isExternal" class="grid-row">
                <div class="grid-33">
                    <label for="fullname" :title="i18n['user.fullname']">{{ i18n['user.fullname'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="text" id="fullname" :placeholder="i18n['input.required']" v-model="values.fullname">
                    <p class="attention">{{ errors.fullname }}</p>
                </div>
            </div>

            <div v-if="!isExternal" class="grid-row">
                <div class="grid-33">
                    <label for="email" :title="i18n['user.email']">{{ i18n['user.email'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="text" id="email" :placeholder="i18n['input.required']" v-model="values.email">
                    <p class="attention">{{ errors.email }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="description" :title="i18n['user.description']">{{ i18n['user.description'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="text" id="description" v-model="values.description">
                    <p class="attention">{{ errors.description }}</p>
                </div>
            </div>

            <div v-if="showPassword" class="grid-row">
                <div class="grid-33">
                    <label for="password" :title="i18n['user.password']">{{ i18n['user.password'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="password" id="password" autocomplete="off" :placeholder="i18n['input.required']" v-model="values.password">
                    <p class="attention">{{ errors.password }}</p>
                </div>
            </div>

            <div v-if="showPassword" class="grid-row">
                <div class="grid-33">
                    <label for="confirm" :title="i18n['password.confirm']">{{ i18n['password.confirm'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="password" id="confirm" autocomplete="off" :placeholder="i18n['input.required']" v-model="values.confirm">
                    <p class="attention">{{ errors.confirm }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="locale" :title="i18n['user.language']">{{ i18n['user.language'] }}:</label>
                </div>
                <div class="grid-66">
                    <select class="grid-100 mobile-grid-100" id="locale" v-model="values.locale">
                        <option v-for="(name, locale) in locales" :value="locale">{{ name }}</option>
                    </select>
                    <p class="attention">{{ errors.locale }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="theme" :title="i18n['user.theme']">{{ i18n['user.theme'] }}:</label>
                </div>
                <div class="grid-66">
                    <select class="grid-100 mobile-grid-100" id="theme" v-model="values.theme">
                        <option v-for="(name, theme) in themes" :value="theme">{{ name }}</option>
                    </select>
                    <p class="attention">{{ errors.theme }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="timezone" :title="i18n['user.timezone']">{{ i18n['user.timezone'] }}:</label>
                </div>
                <div class="grid-66">
                    <select class="grid-100 mobile-grid-100" id="timezone" v-model="values.timezone">
                        <option v-for="(name, timezone) in timezones" :value="timezone">{{ name }}</option>
                    </select>
                    <p class="attention">{{ errors.timezone }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-66 prefix-33">
                    <label :title="i18n['role.admin']">
                        <input type="checkbox" v-model="values.admin">
                        <span>{{ i18n['role.admin'] }}</span>
                    </label>
                    <p class="attention">{{ errors.admin }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-66 prefix-33">
                    <label :title="i18n['user.disabled']">
                        <input type="checkbox" v-model="values.disabled">
                        <span>{{ i18n['user.disabled'] }}</span>
                    </label>
                    <p class="attention">{{ errors.disabled }}</p>
                </div>
            </div>

        </div>
    </modal>

</template>

<script>

    import Modal from 'components/modal/modal.vue';
    import ui    from 'utilities/ui';

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
             * Whether an account is external.
             */
            isExternal: {
                type: Boolean,
                default: false,
            },

            /**
             * Whether to show 'password' fields.
             */
            showPassword: {
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
                fullname:    null,
                email:       null,
                description: null,
                password:    null,
                confirm:     null,
                locale:      null,
                theme:       null,
                timezone:    null,
                admin:       false,
                disabled:    false,
            },
        }),

        computed: {

            // List of available locales.
            locales: () => eTraxis.locales,

            // List of available UI themes.
            themes: () => eTraxis.themes,

            // List of available timezones.
            timezones: () => eTraxis.timezones,

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

            /**
             * @external Opens the dialog.
             */
            open() {

                Object.assign(this.values, this.default);

                this.values.password = null;
                this.values.confirm  = null;

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
                if (this.showPassword && this.values.password !== this.values.confirm) {
                    ui.alert(i18n['password.dont_match']);
                }
                else {
                    this.$emit('submit', this.values);
                }
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
