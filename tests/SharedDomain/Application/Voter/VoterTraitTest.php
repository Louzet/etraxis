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

namespace eTraxis\SharedDomain\Application\Voter;

use eTraxis\SharedDomain\Model\Collection\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * @coversDefaultClass \eTraxis\SharedDomain\Application\Voter\VoterTrait
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
                'delete' => [User::class, Collection::class],
            ];

            protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
            {
                return true;
            }
        };
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testSupportedAttribute()
    {
        $object1 = new User('artem', 'secret');
        $object2 = new Collection();

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $object1, ['update']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, [$object1, $object2], ['delete']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testUnsupportedAttribute()
    {
        $object1 = new User('artem', 'secret');
        $object2 = new Collection();

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $object1, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object1, $object2], ['unknown']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testMissingClass()
    {
        $object1 = new User('artem', 'secret');
        $object2 = new Collection();

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object1], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object2], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object1, null], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [null, $object2], ['delete']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testWrongClass()
    {
        $object1 = new User('artem', 'secret');
        $object2 = new Collection();

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object1, new \stdClass()], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [new \stdClass(), $object2], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$object2, $object1], ['delete']));
    }
}
