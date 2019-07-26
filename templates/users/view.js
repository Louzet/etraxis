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

import TabProfile from './tab_profile.vue';
import TabGroups  from './tab_groups.vue';

/**
 * A user page.
 */
new Vue({
    el: '#vue-user',

    created() {

        // Load user's profile.
        this.reloadProfile();

        // Load groups the user is a member of.
        this.reloadGroups();
    },

    components: {

        'tab':  Tab,
        'tabs': Tabs,

        'tab-profile': TabProfile,
        'tab-groups':  TabGroups,
    },

    data: {

        // User's profile.
        profile: {
            options: {},
        },

        // Groups the user is a member of.
        groups: [],
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
         * Reloads list of groups the user is a member of.
         */
        reloadGroups() {

            ui.block();

            axios.get(url(`/api/users/${eTraxis.userId}/groups`))
                .then(response => {
                    this.groups = response.data.sort((group1, group2) => {
                        if (group1.project === group2.project) {
                            return group1.name.localeCompare(group2.name);
                        }
                        else {
                            if (group1.project === null) {
                                return -1;
                            }
                            if (group2.project === null) {
                                return +1;
                            }
                            return group1.project.name.localeCompare(group2.project.name);
                        }
                    });
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
