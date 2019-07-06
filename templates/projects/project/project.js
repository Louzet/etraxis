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

import Tab   from 'components/tabs/tab.vue';
import Tabs  from 'components/tabs/tabs.vue';
import epoch from 'utilities/epoch';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (project view).
 */
new Vue({
    el: '#vue-project',
    store: eTraxis.store,

    components: {
        'tab':  Tab,
        'tabs': Tabs,
    },

    data: {

        // Project info.
        project: {},
    },

    computed: {

        /**
         * @property {null|string} Current right-side application.
         */
        applicationId() {
            return this.$store.getters.applicationId;
        },

        /**
         * @property {null|number} Currently selected project.
         */
        projectId: {
            get() {
                return this.$store.state.projects.currentId;
            },
            set(value) {
                this.$store.commit('projects/current', value);
            },
        },

        /**
         * @property {null|string} Human-readable start date.
         */
        startDate() {
            return this.project.created ? epoch.date(this.project.created) : null;
        },
    },

    methods: {

        /**
         * Reloads project info.
         */
        reloadProject() {

            ui.block();

            axios.get(url(`/api/projects/${this.projectId}`))
                .then(response => {
                    this.project = response.data;
                    this.$store.commit('projects/update', this.project);
                })
                .catch(exception => ui.errors(exception))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another project has been selected.
         *
         * @param {null|number} id Project ID.
         */
        projectId(id) {

            if (id !== null) {
                this.reloadProject();
            }
        }
    },
});
