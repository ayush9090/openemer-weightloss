<?php
/**
 * Supplement Tracker Module Configuration
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Your Name <your.email@example.com>
 * @copyright Copyright (c) 2024 Your Name
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Menu\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

function oe_module_supplement_tracker_menu(MenuEvent $event)
{
    $menu = $event->getMenu();
    
    // Add Admin menu item
    $menu->addChild('Supplement Tracker', [
        'uri' => '/interface/modules/custom/supplement_tracker/admin/index.php',
        'target' => '_self',
        'icon' => 'fa-pills',
        'order' => 100,
        'parent' => 'Administration',
        'requires_auth' => true,
        'requires_admin' => true
    ]);

    // Add Patient menu item
    $menu->addChild('Supplements', [
        'uri' => '/interface/modules/custom/supplement_tracker/patient/index.php',
        'target' => '_self',
        'icon' => 'fa-pills',
        'order' => 100,
        'parent' => 'Patient',
        'requires_auth' => true
    ]);
}

// Register menu items
$eventDispatcher = $GLOBALS['kernel']->getEventDispatcher();
$eventDispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_supplement_tracker_menu');

// Module configuration
$module_config = [
    'name' => 'Supplement Tracker',
    'version' => '1.0.0',
    'author' => 'Your Name',
    'description' => 'Track supplements per clinic and patient usage',
    'url' => 'https://github.com/yourusername/supplement-tracker',
    'license' => 'GPL',
    'compatibility' => '7.0.0',
    'dependencies' => [],
    'permissions' => [
        'admin' => [
            'supplement_tracker_admin',
            'supplement_tracker_edit'
        ],
        'user' => [
            'supplement_tracker_view',
            'supplement_tracker_assign'
        ]
    ]
]; 