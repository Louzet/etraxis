<template>
    <div class="tabs">
        <ul>
            <li v-for="tab in tabs" :id="tab.id" :class="{ 'active': tab.active }">
                <a @click="activateTab(tab.id)">{{ tab.caption }}</a>
            </li>
        </ul>
        <slot></slot>
    </div>
</template>

<script>

    /**
     * Tabs.
     */
    export default {

        created() {

            // Autoregister all tabs.
            this.tabs = this.$children;

            // Sync proxy value.
            this.proxyValue = this.value;
        },

        mounted() {

            let tab = this.tabs.find(tab => tab.id === this.value);

            // Make the first tab active, if active tab is not specified explicitly.
            if (tab) {
                tab.active = true;
            }
            else {
                this.proxyValue = this.tabs[0].id;
                this.$emit('input', this.tabs[0].id);
            }
        },

        props: {

            /**
             * ID of the active tab.
             */
            value: {
                type: String,
                required: false,
            },
        },

        data: () => ({

            // List of tabs.
            tabs: [],

            // ID of the active tab.
            proxyValue: null,
        }),

        methods: {

            /**
             * Makes the specified tab active.
             *
             * @param {string} id Tab's ID.
             */
            activateTab(id) {
                if (this.proxyValue !== id) {
                    this.proxyValue = id;
                    this.$emit('input', id);
                }
            },
        },

        watch: {

            /**
             * Another tab is activated.
             *
             * @param {string} id Tab's ID.
             */
            value(id) {
                this.proxyValue = id;
            },

            /**
             * Another tab is activated.
             *
             * @param {string} id Tab's ID.
             */
            proxyValue(id) {
                for (let tab of this.tabs) {
                    tab.active = (tab.id === id);
                }
            },
        },
    };

</script>
