<?php
/**
 * News.
 *
 * @copyright Michael Ueberschaer (MU)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Michael Ueberschaer <info@homepages-mit-zikula.de>.
 * @link https://homepages-mit-zikula.de
 * @link https://ziku.la
 * @version Generated by ModuleStudio (https://modulestudio.de).
 */

namespace MU\NewsModule\Container;

use MU\NewsModule\Container\Base\AbstractLinkContainer;
use Zikula\Core\LinkContainer\LinkContainerInterface;

/**
 * This is the link container service implementation class.
 */
class LinkContainer extends AbstractLinkContainer
{
    // feel free to add own extensions here
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $links =  parent::getLinks($type);
        //return $links;
        if (LinkContainerInterface::TYPE_ADMIN == $type) {
            if ($this->permissionHelper->hasPermission(ACCESS_ADMIN)) {
                $links[] = [
                    'url' => $this->router->generate('munewsmodule_message_importnewsarticles'),
                    'text' => $this->__('Import Old News', 'munewsmodule'),
                    'title' => $this->__('Import news entries from an old news site. For this to work your data base must have a \'news\' table that was generated from the News Module', 'munewsmodule'),
                    'icon' => 'upload'
                ];
            }
        }
        return $links;
    }
}

