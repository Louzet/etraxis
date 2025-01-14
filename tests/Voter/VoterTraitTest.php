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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * @coversDefaultClass \eTraxis\Voter\VoterTrait
 */
class VoterTraitTest extends TestCase
{
    /** @var Voter */
    protected $voter;

    protected function setUp()
    {
        parent::setUp();

        $this->voter = new class() extends Voter {
            use VoterTrait;

            protected $attributes = [
                'create' => null,
                'update' => User::class,
            ];

            protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
            {
                return true;
            }
        };
    }

    /**
     * @covers ::supports
     */
    public function testSupportedAttribute()
    {
        $object = new User('artem', 'secret');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $object, ['update']));
    }

    /**
     * @covers ::supports
     */
    public function testUnsupportedAttribute()
    {
        $object = new User('artem', 'secret');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $object, ['unknown']));
    }

    /**
     * @covers ::supports
     */
    public function testMissingClass()
    {
        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['update']));
    }

    /**
     * @covers ::supports
     */
    public function testWrongClass()
    {
        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), ['update']));
    }
}
