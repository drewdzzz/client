<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Authenticate;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class AuthMiddleware implements Middleware
{
    private $username;
    private $password;

    public function __construct(string $username, string $password = '')
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $connection = $handler->getConnection();

        if ($connection->isClosed()) {
            $handler->handle(new Authenticate(
                $connection->open(),
                $this->username,
                $this->password
            ));
        }

        return $handler->handle($request);
    }
}