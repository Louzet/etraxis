<template>

    <div class="grid-row">

        <dlg-user ref="dlgUser" :header="i18n['user.edit']" :default="profile" :is-external="profile.provider !== 'etraxis'" :show-password="false" :errors="errors" @submit="updateUser"></dlg-user>
        <dlg-password ref="dlgPassword" :errors="errors" @submit="setPassword"></dlg-password>

        <div class="grid-100">
            <button @click="goBack">{{ i18n['button.back'] }}</button>
            <button v-if="profile.options['user.update']" @click="showEditUserDialog">{{ i18n['button.edit'] }}</button>
            <button v-if="profile.options['user.password']" @click="showPasswordDialog">{{ i18n['password.change'] }}</button>
            <button v-if="profile.options['user.disable'] && !profile.disabled" @click="disableUser">{{ i18n['button.disable'] }}</button>
            <button v-if="profile.options['user.enable'] && profile.disabled" @click="enableUser">{{ i18n['button.enable'] }}</button>
            <button v-if="profile.options['user.unlock'] && profile.locked" @click="unlockUser">{{ i18n['button.unlock'] }}</button>
            <button v-if="profile.options['user.delete']" class="danger" @click="deleteUser">{{ i18n['button.delete'] }}</button>
        </div>

        <div class="grid-50 mobile-grid-100">
            <div class="fieldset">
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.fullname'] }}:</p></div>
                    <div class="grid-75"><p>{{ profile.fullname }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.email'] }}:</p></div>
                    <div class="grid-75"><p>{{ profile.email }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.permissions'] }}:</p></div>
                    <div class="grid-75" v-if="profile.admin"><p>{{ i18n['role.admin'] }}</p></div>
                    <div class="grid-75" v-if="!profile.admin"><p>{{ i18n['role.user'] }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.authentication'] }}:</p></div>
                    <div class="grid-75"><p>{{ provider }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.description'] }}:</p></div>
                    <div class="grid-75"><p>{{ profile.description || '&mdash;' }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.status'] }}:</p></div>
                    <div class="grid-75" v-if="profile.disabled"><p>{{ i18n['user.disabled'] }}</p></div>
                    <div class="grid-75" v-if="!profile.disabled"><p>{{ i18n['user.enabled'] }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.language'] }}:</p></div>
                    <div class="grid-75"><p>{{ language }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.theme'] }}:</p></div>
                    <div class="grid-75"><p>{{ theme }}</p></div>
                </div>
                <div class="grid-row">
                    <div class="grid-25"><p class="label">{{ i18n['user.timezone'] }}:</p></div>
                    <div class="grid-75"><p>{{ profile.timezone }}</p></div>
                </div>
            </div>
        </div>

    </div>

</template>

<script>

    import ui  from 'utilities/ui';
    import url from 'utilities/url';

    import DlgPassword from './dlg_password.vue';
    import DlgUser     from './dlg_user.vue';

    export default {

        components: {
            'dlg-password': DlgPassword,
            'dlg-user':     DlgUser,
        },

        props: {

            /**
             * User's profile.
             */
            profile: {
                type: Object,
                required: true,
            },
        },

        data: () => ({

            // Form contents.
            errors: {},
        }),

        computed: {

            /**
             * @property {string} Human-readable provider.
             */
            provider() {
                return eTraxis.providers[this.profile.provider];
            },

            /**
             * @property {string} Human-readable language.
             */
            language() {
                return eTraxis.locales[this.profile.locale];
            },

            /**
             * @property {string} Human-readable theme.
             */
            theme() {
                return eTraxis.themes[this.profile.theme];
            },

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

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

                this.errors = {};

                this.$refs.dlgUser.open();
            },

            /**
             * Updates the user.
             *
             * @param {Object} event Event data.
             */
            updateUser(event) {

                let data = {
                    fullname:    event.fullname,
                    email:       event.email,
                    description: event.description,
                    locale:      event.locale,
                    theme:       event.theme,
                    timezone:    event.timezone,
                    admin:       event.admin,
                    disabled:    event.disabled,
                };

                ui.block();

                axios.put(url(`/api/users/${eTraxis.userId}`), data)
                    .then(() => {
                        ui.info(i18n['text.changes_saved'], () => {
                            this.$refs.dlgUser.close();
                            this.$emit('reload');
                        });
                    })
                    .catch(exception => (this.errors = ui.errors(exception)))
                    .then(() => ui.unblock());
            },

            /**
             * Shows 'Change password' dialog.
             */
            showPasswordDialog() {

                this.errors = {};

                this.$refs.dlgPassword.open();
            },

            /**
             * Sets user's password.
             *
             * @param {Object} event Event data.
             */
            setPassword(event) {

                let data = {
                    password: event.password,
                };

                ui.block();

                axios.put(url(`/api/users/${eTraxis.userId}/password`), data)
                    .then(() => {
                        ui.info(i18n['password.changed']);
                        this.$refs.dlgPassword.close();
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
                    .then(() => this.$emit('reload'))
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
                    .then(() => this.$emit('reload'))
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            },

            /**
             * Unlocks the user.
             */
            unlockUser() {

                ui.block();

                axios.post(url(`/api/users/${eTraxis.userId}/unlock`))
                    .then(() => this.$emit('reload'))
                    .catch(exception => ui.errors(exception))
                    .then(() => ui.unblock());
            },
        },
    };

</script>
