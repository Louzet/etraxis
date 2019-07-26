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

import TabGroup   from './tab_group.vue';
import TabMembers from './tab_members.vue';

/**
 * A group page.
 */
new Vue({
    el: '#vue-group',

    created() {

        // Load group.
        this.reloadGroup();
    },

    components: {

        'tab':  Tab,
        'tabs': Tabs,

        'tab-group':   TabGroup,
        'tab-members': TabMembers,
    },

    data: {

        // Group information.
        group: {
            options: {},
        },

        // Group members.
        members: [],
    },

    methods: {

        /**
         * Reloads group.
         */
        reloadGroup() {

            ui.block();

            axios.get(url(`/api/groups/${eTraxis.groupId}`))
                .then(response => this.group = response.data)
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Reloads list of group members.
         */
        reloadMembers() {

            ui.block();

            axios.get(url(`/api/groups/${eTraxis.groupId}/members`))
                .then(response => {
                    this.members = response.data.sort((user1, user2) => {
                        if (user1.fullname === user2.fullname) {
                            return user1.email.localeCompare(user2.email);
                        }
                        else {
                            return user1.fullname.localeCompare(user2.fullname);
                        }
                    });
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },
});
