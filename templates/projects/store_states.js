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
 * States store module.
 */
module.exports = {

    namespaced: true,

    state: {

        // All states of the current template.
        list: [],

        // Current state.
        currentId: null,
    },

    mutations: {

        /**
         * Sets current state.
         *
         * @param {Object} state Store's state.
         * @param {number} id    State ID.
         */
        current(state, id) {
            state.currentId = id;
        },

        /**
         * Updates specified state.
         *
         * @param {Object} state Store's state.
         * @param {Object} data  State info.
         */
        update(state, data) {

            let entry = state.list.find(state => state.id === data.id);

            if (entry) {
                entry.title = data.name;
                entry.type  = data.type;
            }
        },
    },

    actions: {

        /**
         * Loads all states of the specified template.
         *
         * @param {Vuex.Store} context Store context.
         * @param {number}     id      Template ID.
         */
        async load(context, id) {

            let headers = {
                'X-Filter': JSON.stringify({ 'template': id }),
                'X-Sort':   JSON.stringify({ 'name': 'ASC' }),
            };

            let states = [];
            let offset = 0;

            while (offset !== -1) {

                await axios.get(url(`/api/states?offset=${offset}`), { headers })
                    .then(response => {

                        for (let state of response.data.data) {
                            states.push({
                                id:    state.id,
                                title: state.name,
                                type:  state.type,
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

            context.state.list = states;
        },
    },
};
