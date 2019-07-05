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

import Column    from 'components/datatable/column';
import DataTable from 'components/datatable/datatable.vue';
import Modal     from 'components/modal/modal.vue';
import ui        from 'utilities/ui';
import url       from 'utilities/url';

/**
 * 'Groups' page.
 */
new Vue({
    el: '#vue-groups',

    created() {

        // 'Project' column filter.
        this.columns[1].filter = {
            0: i18n['group.global'],
        };

        eTraxis.projects.map(project => {
            this.columns[1].filter[project.id] = project.name;
        });
    },

    components: {
        'datatable': DataTable,
        'modal':     Modal,
    },

    data: {

        // Table columns definition.
        columns: [
            new Column('name',        i18n['group.name']),
            new Column('project',     i18n['project']),
            new Column('description', i18n['group.description'], '100%'),
        ],

        // Form contents.
        values: {},
        errors: {},
    },

    methods: {

        /**
         * Data provider for the table.
         *
         * @param  {Request} request Request from the DataTable component.
         * @return {Promise} Promise of response.
         */
        groups(request) {

            if (parseInt(request.filters.project) === 0) {
                delete request.filters.project;
                request.filters.global = true;
            }

            return axios.datatable(url('/api/groups'), request, group => ({
                DT_id:       group.id,
                name:        group.name,
                project:     group.global ? i18n['group.global'] : group.project.name,
                description: group.description,
            }));
        },

        /**
         * A row with a group is clicked.
         *
         * @param {number} id Group ID.
         */
        viewGroup(id) {
            location.href = url('/admin/groups/' + id);
        },

        /**
         * Shows 'New group' dialog.
         */
        showNewGroupDialog() {

            this.values = {
                project: '',
            };

            this.errors = {};

            this.$refs.dlgNewGroup.open();
        },

        /**
         * Creates new group.
         */
        createGroup() {

            ui.block();

            axios.post(url('/api/groups'), this.values)
                .then(() => {
                    ui.info(i18n['group.successfully_created'], () => {
                        this.$refs.dlgNewGroup.close();
                        this.$refs.groups.refresh();
                    });
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },
    },
});
