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
 * Current user's profile.
 */
eTraxis.store = new Vuex.Store({

    state: {
        email: null,
        fullname: null,
        locale: null,
        theme: null,
        timezone: null,
    },

    mutations: {

        /**
         * Sets email to the specified value.
         *
         * @param {Object} state Current state.
         * @param {string} value New value.
         */
        setEmail(state, value) {
            state.email = value;
        },

        /**
         * Sets full name to the specified value.
         *
         * @param {Object} state Current state.
         * @param {string} value New value.
         */
        setFullname(state, value) {
            state.fullname = value;
        },

        /**
         * Sets locale to the specified value.
         *
         * @param {Object} state Current state.
         * @param {string} value New value.
         */
        setLocale(state, value) {
            state.locale = value;
        },

        /**
         * Sets theme to the specified value.
         *
         * @param {Object} state Current state.
         * @param {string} value New value.
         */
        setTheme(state, value) {
            state.theme = value;
        },

        /**
         * Sets timezone to the specified value.
         *
         * @param {Object} state Current state.
         * @param {string} value New value.
         */
        setTimezone(state, value) {
            state.timezone = value;
        },
    },

    actions: {

        /**
         * Loads user's profile from the server.
         *
         * @param {Object} context Current context.
         */
        loadProfile(context) {

            ui.block();

            axios.get(url('/api/my/profile'))
                .then(response => {
                    context.commit('setEmail',    response.data.email);
                    context.commit('setFullname', response.data.fullname);
                    context.commit('setLocale',   response.data.locale);
                    context.commit('setTheme',    response.data.theme);
                    context.commit('setTimezone', response.data.timezone);
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
