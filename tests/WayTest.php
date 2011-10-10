<?php
/**
 * WayTest.php
 * 10-Oct-2011
 *
 * PHP Version 5
 *
 * @category Services
 * @package  Services_Openstreetmap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  Release: @package_version@
 * @link     WayTest.php
 */

$version = '@package_version@';
if (strstr($version, 'package_version')) {
    set_include_path(dirname(dirname(__FILE__)) . ':' . get_include_path());
}

require_once 'Services/Openstreetmap.php';

require_once 'HTTP/Request2.php';
require_once 'HTTP/Request2/Adapter/Mock.php';

class WayTest extends PHPUnit_Framework_TestCase
{

    public function testGetWay()
    {
        $id = 25978036;

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(fopen(__DIR__ . '/responses/capabilities.xml', 'rb'));
        $mock->addResponse(fopen(__DIR__ . '/responses/way.xml', 'rb'));

        $config = array(
            'adapter' => $mock,
            'server' => 'http://www.openstreetmap.org/'
        );
        $osm = new Services_Openstreetmap($config);
        $way = $osm->getWay($id);
        $getTags = $way->getTags();
        $this->assertEquals($id, (int) $way->getAttributes()->id);
        $this->assertEquals($getTags['highway'], 'service');
        $this->assertEquals($way->getUid(), 1379);
        $this->assertEquals($way->getVersion(), 1);
        $this->assertEquals($way->getUser(), "AndrewMcCarthy");
        $this->assertEquals($way->getNodes(), array("283393706","283393707"));
    }

    public function testGetClosedWay()
    {
        $id = 18197393;

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(fopen(__DIR__ . '/responses/capabilities.xml', 'rb'));
        $mock->addResponse(fopen(__DIR__ . '/responses/way_closed.xml', 'rb'));

        $config = array(
            'adapter' => $mock,
            'server' => 'http://www.openstreetmap.org/'
        );
        $osm = new Services_Openstreetmap($config);
        $way = $osm->getWay($id);
        $getTags = $way->getTags();
        $this->assertEquals($id, (int) $way->getAttributes()->id);
        $this->assertEquals($getTags['building'], 'yes');
        $this->assertTrue($way->isClosed());
    }

    public function testOpenWay()
    {
        $id = 23010474;

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(fopen(__DIR__ . '/responses/capabilities.xml', 'rb'));
        $mock->addResponse(fopen(__DIR__ . '/responses/way_open.xml', 'rb'));

        $config = array(
            'adapter' => $mock,
            'server' => 'http://www.openstreetmap.org'
        );
        $osm = new Services_Openstreetmap($config);
        $way = $osm->getWay($id);
        $getTags = $way->getTags();
        $this->assertEquals($id, (int) $way->getAttributes()->id);
        $this->assertFalse($way->isClosed());
    }

    public function testWayWithOneNode()
    {
        $id = 23010475;

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(fopen(__DIR__ . '/responses/capabilities.xml', 'rb'));
        $mock->addResponse(fopen(__DIR__ . '/responses/way_one_node.xml', 'rb'));

        $config = array(
            'adapter' => $mock,
            'server' => 'http://www.openstreetmap.org'
        );
        $osm = new Services_Openstreetmap($config);
        $way = $osm->getWay($id);
        $getTags = $way->getTags();
        $this->assertEquals($id, (int) $way->getAttributes()->id);
        $this->assertFalse($way->isClosed());
    }

}

// vim:set et ts=4 sw=4:
?>
