<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\InvalidClientIdentifierKey;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\ForbiddenException;

final class AuthorizationEndpointGetController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    // see https://tools.ietf.org/html/rfc6749#section-4.1.1
    private const RESPONSE_TYPE_PARAMETER = 'response_type';
    private const CLIENT_ID_PARAMETER     = 'client_id';
    private const REDIRECT_URI_PARAMETER  = 'redirect_uri';
    public const  SCOPE_PARAMETER         = 'scope';
    private const ALLOWED_RESPONSE_TYPE   = 'code';
    public const  STATE_PARAMETER         = 'state';
    // see https://tools.ietf.org/html/rfc6749#section-4.1.2.1
    public const  ERROR_PARAMETER            = 'error';
    private const ERROR_CODE_INVALID_REQUEST = 'invalid_request';
    private const ERROR_CODE_INVALID_SCOPE   = 'invalid_scope';
    private const ERROR_CODE_ACCESS_DENIED   = 'access_denied';
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var AuthorizationFormRenderer
     */
    private $form_renderer;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var \URLRedirect
     */
    private $login_redirect;
    /**
     * @var RedirectURIBuilder
     */
    private $client_uri_redirect_builder;
    /**
     * @var ScopeExtractor
     */
    private $scope_extractor;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        AuthorizationFormRenderer $form_renderer,
        \UserManager $user_manager,
        AppFactory $app_factory,
        \URLRedirect $login_redirect,
        RedirectURIBuilder $client_uri_redirect_builder,
        ScopeExtractor $scope_extractor,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory            = $response_factory;
        $this->form_renderer               = $form_renderer;
        $this->user_manager                = $user_manager;
        $this->app_factory                 = $app_factory;
        $this->login_redirect              = $login_redirect;
        $this->client_uri_redirect_builder = $client_uri_redirect_builder;
        $this->scope_extractor             = $scope_extractor;
    }

    /**
     * @throws ForbiddenException
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            return $this->response_factory->createResponse(302)
                ->withHeader('Location', $this->login_redirect->buildReturnToLogin($request->getServerParams()));
        }

        $query_params = $request->getQueryParams();
        $client_id    = (string) ($query_params[self::CLIENT_ID_PARAMETER] ?? '');
        try {
            $client_identifier = ClientIdentifier::fromClientId($client_id);
            $client_app        = $this->app_factory->getAppMatchingClientId($client_identifier);
        } catch (InvalidClientIdentifierKey | OAuth2AppNotFoundException $exception) {
            throw new ForbiddenException();
        }

        $redirect_uri = (string) ($query_params[self::REDIRECT_URI_PARAMETER] ?? '');
        if ($redirect_uri !== $client_app->getRedirectEndpoint()) {
            throw new ForbiddenException();
        }

        $state_value = $query_params[self::STATE_PARAMETER] ?? null;

        if (! isset($query_params[self::RESPONSE_TYPE_PARAMETER]) || $query_params[self::RESPONSE_TYPE_PARAMETER] !== self::ALLOWED_RESPONSE_TYPE) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST, $redirect_uri, $state_value);
        }

        try {
            $scopes = $this->scope_extractor->extractScopes($query_params);
        } catch (InvalidOAuth2ScopeException $e) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_SCOPE, $redirect_uri, $state_value);
        }

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);
        $deny_authorization_uri = $this->client_uri_redirect_builder->buildRedirectURI(
            $redirect_uri,
            $state_value,
            self::ERROR_CODE_ACCESS_DENIED
        );
        return $this->form_renderer->renderForm($client_app, $deny_authorization_uri, $layout, ...$scopes);
    }

    /**
     * @psalm-param self::ERROR_CODE_* $error_code See https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     */
    private function buildErrorResponse(
        string $error_code,
        string $redirect_uri,
        ?string $state_value
    ): ResponseInterface {
        $error_uri = $this->client_uri_redirect_builder->buildRedirectURI($redirect_uri, $state_value, $error_code);
        return $this->response_factory->createResponse(302)
            ->withHeader('Location', (string) $error_uri);
    }
}
