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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Schema\Criteria;

final class ClientSpaceCachingTest extends TestCase
{
    public function testCacheSpace() : void
    {
        $total = self::getTotalSelectCalls();

        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select(Criteria::key([]));
        $this->client->getSpace('space_conn')->select(Criteria::key([]));

        self::assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushSpaces() : void
    {
        $total = self::getTotalSelectCalls();

        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select(Criteria::key([]));
        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select(Criteria::key([]));

        self::assertSame(4, self::getTotalSelectCalls() - $total);
    }
}
