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

import Tab  from 'components/tabs/tab.vue';
import Tabs from 'components/tabs/tabs.vue';
import ui   from 'utilities/ui';
import url  from 'utilities/url';

/**
 * A user page.
 */
new Vue({
    el: '#vue-user',

    created() {

        // Load user's profile.
        this.reloadProfile();
    },

    components: {
        'tab':  Tab,
        'tabs': Tabs,
    },

    data: {

        // User's profile.
        profile: {},
    },

    computed: {

        /**
         * @returns {string} Human-readable provider.
         */
        provider() {
            return eTraxis.providers[this.profile.provider];
        },

        /**
         * @returns {string} Human-readable language.
         */
        language() {
            return eTraxis.locales[this.profile.locale];
        },

        /**
         * @returns {string} Human-readable theme.
         */
        theme() {
            return eTraxis.themes[this.profile.theme];
        },
    },

    methods: {

        /**
         * Reloads user's profile.
         */
        reloadProfile() {

            ui.block();

            axios.get(url(`/api/users/${eTraxis.userId}`))
                .then(response => this.profile = response.data)
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Redirects back to list of users.
         */
        goBack() {
            location.href = url('/admin/users');
        },
    },
});
