<?php
/**
 * Copyright (c) 2018 Mattheu Racine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mracine\IPTools;

interface IP
{
    const IPv4 = 4;
    const IPv6 = 6;

    public function version();
}