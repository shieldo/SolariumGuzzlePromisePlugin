<?php

namespace Shieldo\GuzzlePromisePlugin;

use Solarium\Core\Client\Adapter\Guzzle as GuzzleAdapter;
use Solarium\Core\Plugin\AbstractPlugin;

class GuzzlePromisePlugin extends AbstractPlugin
{
    

    protected function initPluginType()
    {
        $this->client->setAdapter(GuzzleAdapter::class);
    }
}
