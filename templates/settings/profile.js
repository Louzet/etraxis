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
 * 'Profile' form ('Settings' page).
 */
new Vue({
    el: '#vue-profile',
    store: eTraxis.store,

    created() {
        this.$store.dispatch('loadProfile');
    },

    data: {

        // Whether the form is disabled.
        disabled: eTraxis.external,

        // Form errors.
        errors: {},
    },

    computed: {

        // Store's data.
        fullname: {
            get() {
                return this.$store.state.fullname;
            },
            set(value) {
                this.$store.commit('setFullname', value);
            }
        },

        // Store's data.
        email: {
            get() {
                return this.$store.state.email;
            },
            set(value) {
                this.$store.commit('setEmail', value);
            }
        },
    },

    methods: {

        /**
         * Saves the changes.
         */
        submit() {

            if (this.disabled) {
                return;
            }

            let data = {
                fullname: this.fullname,
                email:    this.email,
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
    },
});
