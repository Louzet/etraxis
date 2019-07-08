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

import ui  from 'utilities/ui';
import url from 'utilities/url';

/**
 * Groups store module.
 */
module.exports = {

    namespaced: true,

    state: {

        // All global groups.
        global: [],

        // All local groups of the current project.
        local: [],
    },

    actions: {

        /**
         * Loads all groups of the specified project, or global ones.
         *
         * @param {Vuex.Store} context Store context.
         * @param {number}     id      Project ID (null for global groups).
         */
        async load(context, id = null) {

            let headers = {
                'X-Filter': JSON.stringify(id === null ? { 'global': true } : { 'project': id }),
                'X-Sort':   JSON.stringify({ 'name': 'ASC' }),
            };

            let groups = [];
            let offset = 0;

            while (offset !== -1) {

                await axios.get(url(`/api/groups?offset=${offset}`), { headers })
                    .then(response => {

                        for (let group of response.data.data) {
                            groups.push(group);
                        }

                        offset = response.data.to + 1 < response.data.total
                               ? response.data.to + 1
                               : -1;
                    })
                    .catch(exception => {
                        offset = -1;
                        ui.errors(exception);
                    });
            }

            if (id === null) {
                context.state.global = groups;
            }
            else {
                context.state.local = groups;
            }
        },
    },
};
