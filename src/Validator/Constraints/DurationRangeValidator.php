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
use Symfony\Component\Validator\ConstraintValidator;

/**
 * A validator for the DurationRange constraint.
 */
class DurationRangeValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param null|string   $value
     * @param DurationRange $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value !== null) {

            if (preg_match(DurationRange::PCRE_PATTERN, $value)) {

                $duration = $this->str2int($value);

                if ($constraint->min !== null && $duration < $this->str2int($constraint->min)) {
                    $this->context->addViolation($constraint->minMessage, ['{{ limit }}' => $constraint->min]);
                }

                if ($constraint->max !== null && $duration > $this->str2int($constraint->max)) {
                    $this->context->addViolation($constraint->maxMessage, ['{{ limit }}' => $constraint->max]);
                }
            }
            else {
                $this->context->addViolation($constraint->invalidMessage);
            }
        }
    }

    /**
     * Converts string with duration to its integer value.
     *
     * @param string $value
     *
     * @return int
     */
    private function str2int(string $value): int
    {
        [$hh, $mm] = explode(':', $value);

        return $hh * 60 + $mm;
    }
}
