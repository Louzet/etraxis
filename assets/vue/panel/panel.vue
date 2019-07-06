<template>
    <div class="panel" :class="{ 'collapsed': collapsed }">
        <div class="panel-header" @click="collapsed = !collapsed">
            <span v-if="!collapsed" class="pull-after fa fa-chevron-up" :title="text.collapse"></span>
            <span v-if="collapsed" class="pull-after fa fa-chevron-down" :title="text.expand"></span>
            <span class="pull-after fa fa-plus" :title="text.create" @click.stop="$emit('create')"></span>
            <div>{{ header }}</div>
        </div>
        <div v-show="!collapsed">
            <slot></slot>
        </div>
    </div>
</template>

<script>

    /**
     * Panel.
     */
    export default {

        props: {

            /**
             * Header text.
             */
            header: {
                type: String,
                required: true,
            },
        },

        data: () => ({

            // Whether the panel is collapsed or expanded.
            collapsed: false,

            // Translation resources.
            text: {
                create:   i18n['button.create'],
                collapse: i18n['button.collapse'],
                expand:   i18n['button.expand'],
            },
        }),
    };

</script>
