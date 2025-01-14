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

namespace eTraxis\Voter;

/**
 * A trait for supported attributes.
 *
 * The trait requires an array which must be declared as property named '$attributes'.
 * Each key of the array is an attribute name, value - class of the subject (use 'null' if subject is not required).
 *
 * Example:
 *
 * protected $attributes = [
 *     'create' => null,
 *     'update' => MyEntity::class,
 *     'delete' => AnotherEntity::class,
 * ];
 */
trait VoterTrait
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        // Whether the attribute is supported.
        if (!array_key_exists($attribute, $this->attributes)) {
            return false;
        }

        $expectedClass = $this->attributes[$attribute];

        // Whether the subject is not required.
        if ($subject === null && $expectedClass === null) {
            return true;
        }

        // The subject must be an object.
        if (!is_object($subject)) {
            return false;
        }

        // Subject may be a Doctrine Proxy class,
        // e.g. 'Proxies\__CG__\App\Entity\MyEntity' instead of 'App\Entity\MyEntity'.
        $class = mb_substr(get_class($subject), -mb_strlen($expectedClass));

        // The subject must be of expected class.
        return $class === $expectedClass;
    }
}
