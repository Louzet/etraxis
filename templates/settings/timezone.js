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
 * 'Timezone' form ('Settings' page).
 */
new Vue({
    el: '#vue-timezone',
    store: eTraxis.store,

    created() {
        this.loadCities();
    },

    data: {

        // Current country.
        country: eTraxis.country,

        // List of cities in the current country.
        cities: [],

        // Form errors.
        errors: {},
    },

    computed: {

        /**
         * @property {string} User's timezone.
         */
        timezone: {
            get() {
                return this.$store.state.timezone;
            },
            set(value) {
                this.$store.commit('setTimezone', value);
            }
        },
    },

    methods: {

        /**
         * Saves the changes.
         */
        submit() {

            let data = {
                timezone: this.timezone,
            };

            ui.block();

            axios.patch(url('/api/my/profile'), data)
                .then(() => {
                    this.errors = {};
                    ui.info(i18n['text.changes_saved']);
                })
                .catch(exception => (this.errors = ui.errors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Loads list of cities for the current country.
         */
        loadCities() {

            if (this.country === 'UTC') {
                this.cities = { 'UTC': 'UTC' };
                this.timezone = 'UTC';
            }
            else {

                axios.get(url('/settings/cities/' + this.country))
                    .then(response => {
                        this.cities = response.data;
                        this.timezone = (this.country === eTraxis.country) ? this.timezone : Object.keys(response.data)[0];
                    })
                    .catch(exception => ui.errors(exception));
            }
        },
    },

    watch: {

        /**
         * The country has been changed.
         */
        country() {
            this.loadCities();
        },
    },
});
