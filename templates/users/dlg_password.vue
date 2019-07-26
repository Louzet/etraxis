<template>

    <modal ref="modal" :header="i18n['password.change']" @submit="submit">
        <div class="fieldset">

            <div class="grid-row">
                <div class="grid-33">
                    <label for="password" :title="i18n['password.new']">{{ i18n['password.new'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="password" id="password" autocomplete="off" :placeholder="i18n['input.required']" v-model="values.password">
                    <p class="attention">{{ errors.password }}</p>
                </div>
            </div>

            <div class="grid-row">
                <div class="grid-33">
                    <label for="confirm" :title="i18n['password.confirm']">{{ i18n['password.confirm'] }}:</label>
                </div>
                <div class="grid-66">
                    <input class="grid-100 mobile-grid-100" type="password" id="confirm" autocomplete="off" :placeholder="i18n['input.required']" v-model="values.confirm">
                    <p class="attention">{{ errors.confirm }}</p>
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
                password: null,
                confirm:  null,
            },
        }),

        computed: {

            // Translation resources.
            i18n: () => i18n,
        },

        methods: {

            /**
             * @external Opens the dialog.
             */
            open() {

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

                if (this.values.password !== this.values.confirm) {
                    ui.alert(i18n['password.dont_match']);
                }
                else {
                    this.$emit('submit', { password: this.values.password });
                }
            },
        },
    };

</script>
