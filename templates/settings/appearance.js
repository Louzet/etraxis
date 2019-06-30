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
 * 'Appearance' form ('Settings' page).
 */
new Vue({
    el: '#vue-appearance',
    store: eTraxis.store,

    data: {

        // Form errors.
        errors: {},
    },

    computed: {

        // Store's data.
        locale: {
            get() {
                return this.$store.state.locale;
            },
            set(value) {
                this.$store.commit('setLocale', value);
            }
        },

        // Store's data.
        theme: {
            get() {
                return this.$store.state.theme;
            },
            set(value) {
                this.$store.commit('setTheme', value);
            }
        },
    },

    methods: {

        /**
         * Saves the changes.
         */
        submit() {

            let data = {
                locale: this.locale,
                theme:  this.theme,
            };

            ui.block();

            axios.patch(url('/api/my/profile'), data)
                .then(() => {
                    ui.info(i18n['text.changes_saved'], () => {
                        location.href = url('/settings');
                    });
                })
                .catch(exception => {
                    this.errors = ui.errors(exception);
                    ui.unblock();
                });
        },
    },
});
