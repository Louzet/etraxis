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
 * Templates store module.
 */
module.exports = {

    namespaced: true,

    state: {

        // All templates of the current project.
        list: [],

        // Current template.
        currentId: null,
    },

    mutations: {

        /**
         * Sets current template.
         *
         * @param {Object} state Store's state.
         * @param {number} id    Template ID.
         */
        current(state, id) {
            state.currentId = id;
        },

        /**
         * Updates specified template.
         *
         * @param {Object} state Store's state.
         * @param {Object} data  Template info.
         */
        update(state, data) {

            let entry = state.list.find(template => template.id === data.id);

            if (entry) {
                entry.title = data.name;
                entry.class = data.locked ? 'attention' : null;
            }
        },
    },

    actions: {

        /**
         * Loads all templates of the specified project.
         *
         * @param {Vuex.Store} context Store context.
         * @param {number}     id      Project ID.
         */
        async load(context, id) {

            let headers = {
                'X-Filter': JSON.stringify({ 'project': id }),
                'X-Sort':   JSON.stringify({ 'name': 'ASC' }),
            };

            let templates = [];
            let offset    = 0;

            while (offset !== -1) {

                await axios.get(url(`/api/templates?offset=${offset}`), { headers })
                    .then(response => {

                        for (let template of response.data.data) {
                            templates.push({
                                id:     template.id,
                                title:  template.name,
                                locked: template.locked,
                                class:  template.locked ? 'attention' : null,
                            });
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

            context.state.list = templates;
        },
    },
};
