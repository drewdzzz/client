<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class AuthenticateTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     * @dataProvider provideValidCredentials
     *
     * @lua create_user('user_foo', 'foo')
     * @lua create_user('user_empty', '')
     * @lua create_user('user_big', '123456789012345678901234567890123456789012345678901234567890')
     */
    public function testAuthenticateWithValidCredentials(string $username, string $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        $client->ping();
    }

    public function provideValidCredentials() : iterable
    {
        return [
            ['guest', ''],
            ['user_foo', 'foo'],
            ['user_empty', ''],
            ['user_big', '123456789012345678901234567890123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider provideInvalidCredentials
     */
    public function testAuthenticateWithInvalidCredentials(string $errorMessagePattern, $username, $password) : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => $username,
            'password' => $password,
        ])->build();

        try {
            $client->ping();
            self::fail(sprintf('Client must throw an exception on authenticating "%s" with the password "%s"', $username, $password));
        } catch (RequestFailed $e) {
            self::assertMatchesRegularExpression($errorMessagePattern, $e->getMessage());
        }
    }

    public function provideInvalidCredentials() : iterable
    {
        return [
            ["/(User 'non_existing_user' is not found|User not found or supplied credentials are invalid)/", 'non_existing_user', 'password'],
            ["/(Incorrect password supplied for user 'guest'|User not found or supplied credentials are invalid)/", 'guest', 'password'],
        ];
    }

    /**
     * @lua create_user('user_foo', 'foo')
     * @lua create_space('test_auth_reconnect'):create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
     */
    public function testUseCredentialsAfterReconnect() : void
    {
        $client = ClientBuilder::createFromEnv()->setOptions([
            'username' => 'user_foo',
            'password' => 'foo',
        ])->build();

        $client->getHandler()->getConnection()->close();

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("Space 'test_auth_reconnect' does not exist");

        $client->getSpace('test_auth_reconnect');
    }

    public function testAuthenticateOnceOnOpenedPersistentConnection() : void
    {
        $this->expectAuthRequestToBeCalledOnce();

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        // Ensure that no persistent connection is opened
        $connection = $client->getHandler()->getConnection();
        $connection->open();
        $connection->close();

        $client->ping();
        $client->ping();

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        $client->ping();
        $client->ping();

        $connection->close();
    }

    public function testReauthenticateOnClosedPersistentConnection() : void
    {
        $this->expectAuthRequestToBeCalled(2);

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        // Ensure that no persistent connection is opened
        $connection = $client->getHandler()->getConnection();
        $connection->open();
        $connection->close();

        $client->ping();
        $client->ping();

        $connection->close();

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['persistent' => true])
            ->setOptions(['username' => 'guest'])
            ->build();

        $client->ping();
        $client->ping();

        $connection->close();
    }
}
