<?php

namespace Shieldo\GuzzlePromisePlugin;

use Shieldo\GuzzlePromisePlugin\GuzzlePromisePlugin;
use Solarium\Core\Plugin\AbstractPlugin;

class GuzzlePromisePluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GuzzlePromisePlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->plugin = new GuzzlePromisePlugin();
    }

    public function testIsPlugin()
    {
        $this->assertInstanceOf(AbstractPlugin::class, $this->plugin);
    }
}
