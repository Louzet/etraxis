<template>

    <div class="datatable" :class="{ 'simple': simplified }">

        <div :class="{ 'header': !simplified }">
            <slot></slot>
            <span v-if="!simplified" class="search">
                <span class="buttonset">
                    <button class="fa fa-refresh" :title="i18n['button.refresh']" :disabled="blocked" @click="refresh"></button>
                    <button class="fa fa-filter" :title="i18n['button.reset_filters']" :disabled="blocked" @click="resetFilters"></button>
                </span>
                <input type="text" :placeholder="i18n['button.search']" :readonly="blocked" v-model.trim="search">
            </span>
            <div class="clearfix"></div>
        </div>

        <table :class="{ 'hover': clickable && !blocked, 'checkboxes': checkboxes }">

            <thead>
            <tr>
                <th v-if="checkboxes" @click="totalFilters === 0 ? checkedAll = !checkedAll : null">
                    <input v-if="totalFilters === 0 && total !== 0" type="checkbox" :indeterminate.prop="!checkedAll && checked.length !== 0" :disabled="blocked" @click.stop v-model="checkedAll">
                </th>
                <th v-for="column in columns" :class="{ 'sortable': column.sortable }" :width="column.width" :data-name="column.name" @click="toggleSorting">
                    <span class="pull-after fa" :class="{ 'fa-caret-up': column.sortable && sortDirection(column.name) === 'asc', 'fa-caret-down': column.sortable && sortDirection(column.name) === 'desc' }"></span>
                    <span>{{ column.title }}</span>
                </th>
            </tr>
            </thead>

            <tfoot v-if="totalFilters !== 0">
            <tr>
                <td v-if="checkboxes" @click="checkedAll = !checkedAll">
                    <input v-if="total !== 0" type="checkbox" :indeterminate.prop="!checkedAll && checked.length !== 0" :disabled="blocked" @click.stop v-model="checkedAll">
                </td>
                <td v-for="(column, index) in columns">
                    <input v-if="column.filterable && column.filter.length === 0" type="text" :readonly="blocked" v-model.trim="filters[index]">
                    <select v-if="column.filterable && column.filter.length !== 0" :disabled="blocked" v-model.trim="filters[index]">
                        <option></option>
                        <option v-for="(value, key) in column.filter" :value="key">{{ value }}</option>
                    </select>
                </td>
            </tr>
            </tfoot>

            <tbody>
            <tr v-if="total === 0" class="empty">
                <td :colspan="checkboxes ? columns.length + 1 : columns.length">{{ i18n['table.empty'] }}</td>
            </tr>
            <tr v-for="row in rows" :class="row.DT_class">
                <td v-if="checkboxes" @click="row.DT_checkable !== false ? toggleCheck(row.DT_id) : null">
                    <input type="checkbox" :disabled="blocked || row.DT_checkable === false" :value="row.DT_id" @click.stop v-model="checked">
                </td>
                <td v-for="column in columns" :class="{ 'wrappable': column.width }" @click="clickable ? $emit('click', row.DT_id, column.name) : null">
                    <span>{{ row[column.name] ? row[column.name] : '&mdash;' }}</span>
                </td>
            </tr>
            </tbody>

        </table>

        <div class="footer">
            <select class="size" :disabled="blocked" v-model="pageSize">
                <option :value="10">{{ i18n['table.size'].replace('%size%', 10) }}</option>
                <option :value="20">{{ i18n['table.size'].replace('%size%', 20) }}</option>
                <option :value="50">{{ i18n['table.size'].replace('%size%', 50) }}</option>
                <option :value="100">{{ i18n['table.size'].replace('%size%', 100) }}</option>
            </select>
            <span class="buttonset paging">
                <button class="fa first-page" :disabled="blocked || pages === 0 || page === 1" :title="i18n['page.first']" @click="page = 1"></button>
                <button class="fa fa-lg previous-page" :disabled="blocked || pages === 0 || page === 1" :title="i18n['page.previous']" @click="page -= 1"></button>
                <input class="page" type="text" :readonly="blocked" :disabled="pages === 0" :title="i18n['table.pages'].replace('%number%', pages)" v-model.trim.lazy.number="userPage">
                <button class="fa fa-lg next-page" :disabled="blocked || pages === 0 || page === pages" :title="i18n['page.next']" @click="page += 1"></button>
                <button class="fa last-page" :disabled="blocked || pages === 0 || page === pages" :title="i18n['page.last']" @click="page = pages"></button>
            </span>
            <p class="status">{{ status }}</p>
            <div class="clear"></div>
        </div>

    </div>

</template>

<script src="./datatable.js"></script>
