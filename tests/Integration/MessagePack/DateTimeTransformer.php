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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\TypeTransformer\Packable;
use MessagePack\TypeTransformer\Unpackable;

class DateTimeTransformer implements Packable, Unpackable
{
    private $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function pack(Packer $packer, $value) : ?string
    {
        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        return $packer->packExt($this->type,
            $packer->packStr($value->format('Y-m-d\TH:i:s.uP'))
        );
    }

    public function unpack(BufferUnpacker $unpacker, int $extLength)
    {
        return new \DateTimeImmutable($unpacker->unpackStr());
    }
}
