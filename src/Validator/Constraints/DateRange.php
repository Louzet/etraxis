<?php

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

namespace eTraxis\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * A constraint to check that value belongs to specified date range.
 *
 * @Annotation
 */
class DateRange extends Constraint
{
    public const PCRE_PATTERN = '/^\d{4}\-[0-1]\d\-[0-3]\d$/';

    /** @var string This required option is the "min" value. Validation will fail if the given value is less than this min value. */
    public $min;

    /** @var string This required option is the "max" value. Validation will fail if the given value is greater than this max value. */
    public $max;

    /** @var string The message that will be shown if the underlying value is less than the min option. */
    public $minMessage = 'This value should be {{ limit }} or more.';

    /** @var string The message that will be shown if the underlying value is more than the max option. */
    public $maxMessage = 'This value should be {{ limit }} or less.';

    /** @var string The message that will be shown if the underlying value is not a date. */
    public $invalidMessage = 'This value is not valid.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->min === null && $this->max === null) {
            throw new MissingOptionsException(sprintf('Either option "min" or "max" must be given for constraint "%s".', __CLASS__), ['min', 'max']);
        }

        if ($this->min !== null && !preg_match(self::PCRE_PATTERN, $this->min)) {
            throw new InvalidOptionsException(sprintf('The "min" option given for constraint "%s" is invalid.', __CLASS__), ['min']);
        }

        if ($this->max !== null && !preg_match(self::PCRE_PATTERN, $this->max)) {
            throw new InvalidOptionsException(sprintf('The "max" option given for constraint "%s" is invalid.', __CLASS__), ['max']);
        }
    }
}
