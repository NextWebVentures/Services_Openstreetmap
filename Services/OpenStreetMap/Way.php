<?php
/**
 * Way.php
 * 25-Apr-2011
 *
 * PHP Version 5
 *
 * @category Services
 * @package  Services_OpenStreetMap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  Release: @package_version@
 * @link     Way.php
 */

/**
 * Services_OpenStreetMap_Way
 *
 * @category Services
 * @package  Services_OpenStreetMap
 * @author   Ken Guest <kguest@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     Way.php
 */
class Services_OpenStreetMap_Way extends Services_OpenStreetMap_Object
{
    protected $type = 'way';
    protected $nodes = array();
    protected $nodesNew = array();
    protected $dirtyNodes = false;

    /**
     * Return true if the way can be considered 'closed'.
     *
     * @return boolean
     */
    public function isClosed()
    {
        // Not closed if there's just one node.
        // Otherwise a way is considered closed if the first node has
        // the same id as the last.
        if (empty($this->nodes)) {
            $nodes = $this->getNodes();
        } else {
            $nodes = $this->nodes;
        }
        if (sizeof($nodes) == 1) {
            $closed = false;
        } else {
            $closed = ($nodes[0]) == ($nodes[count($nodes) - 1]);
        }
        return $closed;
    }

    /**
     * Return an array containing the IDs of all nodes in the way.
     *
     * @return array
     */
    public function getNodes()
    {
        if (empty($this->nodes)) {
            $obj = simplexml_load_string($this->xml);
            $nds = $obj->xpath('//nd');
            $nodes = array();
            foreach ($nds as $node) {
                $nodes[] = (string) $node->attributes()->ref;
            }
            $this->nodes = $nodes;
        }
        return $this->nodes;
    }

    /**
     * Add a node to the way.
     *
     * @param node $node An Services_OpenStreetMap_Node object.
     *
     * @return Services_OpenStreetMap_Way
     */
    public function addNode(Services_OpenStreetMap_Node $node)
    {
        $id = $node->getId();
        $pos = array_search($id, $this->nodes);
        if ($pos === false) {
            $this->action  = 'modify';
            $this->nodes[] = $id;
            $this->dirty   = true;
            $this->dirtyNodes = true;
            $this->nodesNew[] = $id;
        }
        return $this;
    }

    /**
     * Remove a node from the way.
     *
     * @param node $node Either a Node object or an id/ref of such an object.
     *
     * @return Services_OpenStreetMap_Way
     * @throws Services_OpenStreetMap_InvalidArgumentException
     */
    public function removeNode($node)
    {
        if (empty($this->nodes)) {
            $this->nodes = $this->getNodes();
        }
        $id = null;
        if (is_numeric($node)) {
            $id = $node;
        } elseif ($node instanceof Services_OpenStreetMap_Node) {
            $id = $node->id;
        } else {
            throw new Services_OpenStreetMap_InvalidArgumentException(
                '$node must be either ' .
                'an instance of Services_OpenStreetMap_Node or a numeric id'
            );
        }
        $pos = array_search($id, $this->nodes);
        if ($pos !== false) {
            unset($this->nodes[$pos]);
            $this->dirty  = true;
            $this->action = 'modify';
            $this->dirtyNodes = true;
        }
        return $this;
    }

    /**
     * Amend osmChangeXml with specific updates pertinent to this Way object.
     *
     * @param string $xml OSM Change XML as generated by getOsmChangeXml
     *
     * @return string
     * @see    getOsmChangeXml
     * @link   http://wiki.openstreetmap.org/wiki/OsmChange
     */
    public function osmChangeXml($xml)
    {
        if ($this->dirtyNodes) {
            $domd = new DomDocument();
            $domd->loadXml($xml);
            $xpath = new DomXPath($domd);
            $nodelist = $xpath->query('//' . $this->action . '/way');
            $nd = $xpath->query("//{$this->action}/way/nd");

            // Remove nodes if appropriate.
            for ($i = 0; $i < $nd->length; $i++) {
                $ref = $nd->item($i)->getAttribute('ref');
                if (array_search($ref, $this->nodes) === false) {
                    $nodelist->item(0)->removeChild($nd->item($i));
                }
            }

            // Add new nodes.
            foreach ($this->nodesNew as $new) {
                $el = $domd->createElement('nd');
                $el->setAttribute('ref', $new);
                $nodelist->item(0)->appendChild($el);
            }

            // Remove blank lines in XML - minimise bandwidth usage.
            return preg_replace(
                "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/",
                '',
                $domd->saveXml($nodelist->item(0))
            );


            return $domd->saveXml($nodelist->item(0));
        } else {
            return $xml;
        }
    }

    /**
     * Return address [tags], as an array, if set on a closed way.
     *
     * @return array
     */
    public function getAddress()
    {
        if (!$this->isClosed()) {
            return null;
        }

        $ret  = array(
            'addr_housename' => null,
            'addr_housenumber' => null,
            'addr_street' => null,
            'addr_city' => null,
            'addr_country' => null
        );
        $tags = $this->getTags();
        $detailsSet = false;
        foreach ($tags as $key => $value) {
            if (strpos($key, 'addr') === 0) {
                $ret[str_replace(':', '_', $key)] = $value;
                $detailsSet = true;
            }
        }
        if (!$detailsSet) {
            $ret = null;
        }
        return $ret;
    }

}
// vim:set et ts=4 sw=4:
?>
