//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

import ui      from 'utilities/ui';
import Request from './request';

/**
 * Data table.
 */
export default {

    created() {

        // Set initial values for column filters.
        this.filters.length = this.columns.length;
        this.filters.fill('');

        // Restore saved table state (single values).
        this.page     = parseInt(this.loadState('page')) || 1;
        this.pageSize = parseInt(this.loadState('pageSize')) || 10;
        this.search   = this.loadState('search') || '';

        // Build array of columns names.
        let columns = this.columns.map(column => {
            return column.name;
        });

        // Restore saved table state (sorting).
        const sorting = this.loadState('sorting');

        for (let column in sorting) {
            if (sorting.hasOwnProperty(column) && columns.indexOf(column) !== -1) {
                this.sorting[column] = sorting[column];
            }
        }

        if (Object.keys(this.sorting).length === 0) {
            this.sorting[this.columns[0].name] = 'asc';
        }

        // Restore saved table state (filters).
        const filters = this.loadState('filters');

        for (let column in filters) {
            if (filters.hasOwnProperty(column)) {
                const index = columns.indexOf(column);
                if (index !== -1) {
                    this.filters[index] = filters[column];
                }
            }
        }

        this.refreshWithDelay();
    },

    props: {

        /**
         * Table name to save its state.
         */
        name: {
            type: String,
            required: true,
        },

        /**
         * Table columns specification.
         * Each item is an object of the "Column" class (see "column.js").
         */
        columns: {
            type: Array,
            required: true,
        },

        /**
         * Table rows data provider.
         *
         * The function takes a single parameter of the "Request" class (see "request.js")
         * and must return a promise which resolves an object of the "Response" class (see "response.js").
         * In case of error the promise should reject with an error message.
         */
        data: {
            type: Function,
            required: true,
        },

        /**
         * Whether to emit an event when a table row is clicked.
         */
        clickable: {
            type: Boolean,
            default: true,
        },

        /**
         * Whether to show a column with checkboxes.
         */
        checkboxes: {
            type: Boolean,
            default: true,
        },

        /**
         * Whether to show header and footer of the table.
         */
        simplified: {
            type: Boolean,
            default: false,
        },
    },

    data: () => ({

        // Current page number, one-based.
        page: 1,

        // Manually entered page number, one-based.
        userPage: 1,

        // Page size.
        pageSize: 10,

        // First row index, zero-based.
        from: 0,

        // Last row index, zero-based.
        to: 0,

        // Total rows.
        total: 0,

        // Rows data.
        rows: [],

        // Checked rows (array of associated IDs).
        checked: [],

        // Whether all rows are checked.
        checkedAll: false,

        // Whether the table is blocked from user's interaction.
        blocked: false,

        // Refresh timer.
        timer: null,

        // Global "Search" value.
        search: '',

        // Column filters values.
        filters: [],

        // Current columns sorting.
        sorting: [],

        // Translation resources.
        text: {
            empty:        i18n['table.empty'],
            first:        i18n['page.first'],
            last:         i18n['page.last'],
            next:         i18n['page.next'],
            pages:        i18n['table.pages'],
            pleaseWait:   i18n['text.please_wait'],
            previous:     i18n['page.previous'],
            refresh:      i18n['button.refresh'],
            resetFilters: i18n['button.reset_filters'],
            search:       i18n['button.search'],
            size:         i18n['table.size'],
            status:       i18n['table.status'],
        },
    }),

    computed: {

        /**
         * @return {string} Status string for the table's footer.
         */
        status() {

            if (this.blocked) {
                return this.text.pleaseWait;
            }

            return this.total === 0
                   ? null
                   : this.text.status
                       .replace('%from%', this.from + 1)
                       .replace('%to%', this.to + 1)
                       .replace('%total%', this.total);
        },

        /**
         * @return {number} Total number of pages.
         */
        pages() {
            return Math.ceil(this.total / this.pageSize);
        },

        /**
         * @return {number} Number of filterable columns.
         */
        totalFilters() {

            const filterables = this.columns.filter(column => {
                return column.filterable;
            });

            return filterables.length;
        },
    },

    methods: {

        /**
         * Saves specified value to the local storage.
         *
         * @param {string} name  Name to use in the storage.
         * @param {*}      value Value to store.
         */
        saveState(name, value) {

            if (typeof value === 'object') {

                let values = {};

                for (let index in value) {
                    if (value.hasOwnProperty(index)) {
                        values[index] = value[index];
                    }
                }

                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(values);
            }
            else {
                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(value);
            }
        },

        /**
         * Retrieves value from the local storage.
         *
         * @param  {string} name Name used in the storage.
         * @return {*}      Retrieved value.
         */
        loadState(name) {
            return JSON.parse(localStorage[`DT_${this.name}_${name}`] || null);
        },

        /**
         * @external Reloads the table data.
         */
        refresh() {

            let filters = {};

            for (let index in this.columns) {
                if (this.columns.hasOwnProperty(index)) {
                    if (typeof this.filters[index] !== 'string' || this.filters[index].length !== 0) {
                        const column = this.columns[index];
                        filters[column.name] = this.filters[index];
                    }
                }
            }

            let sorting = {};

            for (let index in this.sorting) {
                if (this.sorting.hasOwnProperty(index)) {
                    sorting[index] = this.sorting[index];
                }
            }

            const request = new Request((this.page - 1) * this.pageSize, this.pageSize, this.search, filters, sorting);

            this.blocked    = true;
            this.checked    = [];
            this.checkedAll = false;

            this.data(request)
                .then(response => {
                    this.from  = response.from;
                    this.to    = response.to;
                    this.total = response.total;
                    this.rows  = response.data;

                    if (this.page > this.pages) {
                        this.page = this.pages || 1;
                    }

                    this.blocked = false;
                })
                .catch(error => {
                    ui.alert(error);
                    this.blocked = false;
                });
        },

        /**
         * Reloads the table data with delay.
         */
        refreshWithDelay() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.refresh, 400);
        },

        /**
         * Clears all filters.
         */
        resetFilters() {

            this.search  = '';
            this.filters = [];

            for (let i = 0; i < this.columns.length; i++) {
                this.filters.push('');
            }
        },

        /**
         * Toggles checkbox status of the specified row.
         *
         * @param {string} id ID of the row (`DT_id` property).
         */
        toggleCheck(id) {

            const index = this.checked.indexOf(id);

            if (index === -1) {
                this.checked.push(id);
            }
            else {
                this.checked.splice(index, 1);
            }
        },

        /**
         * Returns current sort direction of the specified column.
         *
         * @param  {string} columnName Column ID.
         * @return {string} 'asc', 'desc', or empty.
         */
        sortDirection(columnName) {
            return this.sorting[columnName] || '';
        },

        /**
         * Toggles sorting of the clicked column.
         *
         * @param {MouseEvent} event Click event.
         */
        toggleSorting(event) {

            const target = event.target.tagName === 'TH'
                           ? event.target
                           : event.target.parentNode;

            if (target.classList.contains('sortable')) {

                const name = target.dataset.name;
                const direction = (this.sorting[name] || '') === 'asc' ? 'desc' : 'asc';

                if (event.ctrlKey) {
                    delete this.sorting[name];
                    this.sorting[name] = direction;
                    this.saveState('sorting', this.sorting);
                }
                else {
                    this.sorting = [];
                    this.sorting[name] = direction;
                }

                this.refresh();
            }
        },
    },

    watch: {

        /**
         * Current page is changed.
         */
        page() {
            this.userPage = this.page;
            this.saveState('page', this.page);
            this.refresh();
        },

        /**
         * User entered new page number.
         *
         * @param {number} value New page number.
         */
        userPage(value) {

            if (typeof value === 'number' && value >= 1 && value <= this.pages) {
                this.page = value;
            }
            else {
                this.userPage = this.page;
            }
        },

        /**
         * Page size is changed.
         *
         * @param {number} value New page size.
         */
        pageSize(value) {

            if ([10, 20, 50, 100].indexOf(value) === -1) {
                this.pageSize = 10;
                return;
            }

            this.saveState('pageSize', value);
            this.refreshWithDelay();
        },

        /**
         * 'Check all' checkbox is toggled.
         *
         * @param {boolean} value New value of the checkbox.
         */
        checkedAll(value) {

            const rows = this.rows.filter(row => {
                return row.DT_checkable !== false;
            });

            if (!value && this.checked.length === rows.length) {
                this.checked = [];
            }

            if (value) {
                this.checked = rows.map(row => row.DT_id);
            }
        },

        /**
         * Set if checked rows is changed.

         * @param {Array<string>} value New set of checked rows (`DT_id` property).
         */
        checked(value) {

            const rows = this.rows.filter(row => {
                return row.DT_checkable !== false;
            });

            if (this.checkedAll && rows.length !== 0 && value.length === rows.length - 1) {
                this.checkedAll = false;
            }

            if (!this.checkedAll && rows.length !== 0 && value.length === rows.length) {
                this.checkedAll = true;
            }

            this.$emit('check', value);
        },

        /**
         * The global search value is changed.
         */
        search() {
            this.saveState('search', this.search);
            this.refreshWithDelay();
        },

        /**
         * A column filter is changed.
         */
        filters() {

            let filters = {};

            for (let i = 0; i < this.columns.length; i++) {
                filters[this.columns[i].name] = this.filters[i];
            }

            this.saveState('filters', filters);
            this.refreshWithDelay();
        },

        /**
         * Sorting is changed.
         */
        sorting() {
            this.saveState('sorting', this.sorting);
            this.refresh();
        },
    },
};
