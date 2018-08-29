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

namespace eTraxis\SecurityDomain\Framework\Controller;

use eTraxis\SecurityDomain\Application\Command\Users as Command;
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use League\Tactician\CommandBus;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/my' resource.
 *
 * @Route("/api/my")
 *
 * @API\Tag(name="My Account")
 */
class ApiMyController extends Controller
{
    /**
     * Returns profile of the current user.
     *
     * # Sample response
     * ```
     * {
     *     "id":       123,
     *     "email":    "anna@example.com",
     *     "fullname": "Anna Rodygina",
     *     "provider": "eTraxis",
     *     "locale":   "en_NZ",
     *     "theme":    "azure",
     *     "timezone": "Pacific/Auckland"
     * }
     * ```
     *
     * @Route("/profile", name="api_profile_get", methods={"GET"})
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'id'       => $user->id,
            'email'    => $user->email,
            'fullname' => $user->fullname,
            'provider' => AccountProvider::get($user->account->provider),
        ]);
    }

    /**
     * Updates profile of the current user.
     *
     * @Route("/profile", name="api_profile_update", methods={"PATCH"})
     *
     * @API\Parameter(name="email",    in="formData", type="string", required=false, description="Email address (RFC 5322).<br>Ignored for external accounts.")
     * @API\Parameter(name="fullname", in="formData", type="string", required=false, description="Full name (up to 50 characters).<br>Ignored for external accounts.")
     * @API\Parameter(name="locale",   in="formData", type="string", required=false, description="Locale ('xx' or 'xx_XX', see ISO 639-1 / ISO 3166-1).")
     * @API\Parameter(name="theme",    in="formData", type="string", required=false, description="Theme.")
     * @API\Parameter(name="timezone", in="formData", type="string", required=false, description="Timezone (IANA database value).")
     *
     * @API\Response(response=200, description="Success.")
     * @Api\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @Api\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateProfile(Request $request, CommandBus $commandBus): JsonResponse
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        $profile = new Command\UpdateProfileCommand([
            'email'    => $request->request->get('email', $user->email),
            'fullname' => $request->request->get('fullname', $user->fullname),
        ]);

        $settings = new Command\UpdateSettingsCommand([
            'locale'   => $request->request->get('locale', $user->locale),
            'theme'    => $request->request->get('theme', $user->theme),
            'timezone' => $request->request->get('timezone', $user->timezone),
        ]);

        if (!$user->isAccountExternal()) {
            $commandBus->handle($profile);
        }

        $commandBus->handle($settings);

        return $this->json(null);
    }
}