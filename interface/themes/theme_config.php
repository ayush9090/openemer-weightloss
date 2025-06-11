<?php
/**
 * Theme Configuration
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// Define the themes
$GLOBALS['themes'] = array(
    'modern' => array(
        'name' => 'Modern',
        'css' => array(
            'style_modern.css'
        ),
        'fonts' => array(
            'https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600&family=Manrope:wght@400;500;700&display=swap'
        )
    ),
    'default' => array(
        'name' => 'Default',
        'css' => array(
            'style_light.css'
        )
    ),
    'dark' => array(
        'name' => 'Dark',
        'css' => array(
            'style_dark.css'
        )
    )
); 