<?php

/**
 * dynamic_finder.php
 *
 * Sponsored by David Eschelbacher, MD
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2012-2016 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2019 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__FILE__) . "/../../globals.php");
require_once "$srcdir/user.inc.php";
require_once "$srcdir/options.inc.php";

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;
use OpenEMR\Events\UserInterface\PageHeadingRenderEvent;
use OpenEMR\Menu\BaseMenuItem;
use OpenEMR\OeUI\OemrUI;
use Symfony\Component\EventDispatcher\EventDispatcher;
use OpenEMR\Services\PatientService;

$uspfx = 'patient_finder.';
$patient_finder_exact_search = prevSetting($uspfx, 'patient_finder_exact_search', 'patient_finder_exact_search', ' ');

$popup = empty($_REQUEST['popup']) ? 0 : 1;
$searchAny = empty($_GET['search_any']) ? "" : $_GET['search_any'];
unset($_GET['search_any']);

// Generate some code based on the list of columns.
$colcount = 0;
$header0 = "";
$header = "";
$coljson = "";
$orderjson = "";
$colnames = array();

$res = sqlStatement("SELECT option_id, title, toggle_setting_1 FROM list_options WHERE list_id = 'ptlistcols' AND activity = 1 ORDER BY seq, title");
$sort_dir_map = generate_list_map('Sort_Direction');
while ($row = sqlFetchArray($res)) {
    $colname = $row['option_id'];
    $colnames[] = $colname;
    $colorder = $sort_dir_map[$row['toggle_setting_1']];
    $title = xl_list_label($row['title']);
    $title1 = ($title == xl('Full Name')) ? xl('Name') : $title;
    $header .= "   <th>";
    $header .= text($title);
    $header .= "</th>\n";
    $header0 .= "   <td ><input type='text' size='20' ";
    $header0 .= "value='' class='form-control search_init' placeholder='" . xla("Search by") . " " . $title1 . "'/></td>\n";
    if ($coljson) {
        $coljson .= ", ";
    }

    $coljson .= "{\"sName\": \"" . addcslashes($colname, "\t\r\n\"\\") . "\"";
    if ($title1 == xl('Name')) {
        $coljson .= ", \"mRender\": wrapInLink";
    }
    $coljson .= "}";
    if ($orderjson) {
        $orderjson .= ", ";
    }
    $orderjson .= "[\"$colcount\", \"" . addcslashes($colorder, "\t\r\n\"\\") . "\"]";
    ++$colcount;
}

function rp()
{
    global $colnames;
    if (empty($colnames)) {
        return array();
    }

    // Build the SELECT clause with proper table references
    $select_fields = array();
    foreach ($colnames as $col) {
        switch ($col) {
            case 'name':
                $select_fields[] = "CONCAT(pd.lname, ', ', pd.fname) as name";
                break;
            case 'DOB':
                $select_fields[] = "pd.DOB";
                break;
            case 'ss':
                $select_fields[] = "pd.ss";
                break;
            case 'pubpid':
                $select_fields[] = "pd.pubpid";
                break;
            case 'phone_home':
                $select_fields[] = "pd.phone_home";
                break;
            default:
                $select_fields[] = "pd." . $col;
        }
    }

    $sql = "SELECT " . implode(", ", $select_fields) . " FROM patient_data pd";

    // Add clinic-based filtering
    $clinic_id = $_SESSION['authUser']['clinic_id'] ?? null;
    if ($clinic_id !== null) {
        $sql .= " WHERE pd.clinic_id = ?";
        $sqlBindArray = array($clinic_id);
    } else {
        $sqlBindArray = array();
    }

    $res = sqlStatement($sql, $sqlBindArray);
    $headers = array();
    while ($row = sqlFetchArray($res)) {
        foreach ($row as $key => $value) {
            if (!in_array($key, $headers)) {
                $headers[] = $key;
            }
        }
    }
    return $headers;
}

$rp = rp();
$loading = "";

/** @var EventDispatcher */
$eventDispatcher = $GLOBALS['kernel']->getEventDispatcher();
$arrOeUiSettings = array(
    'heading_title' => xl('Patient Finder'),
    'include_patient_name' => false,
    'expandable' => true,
    'expandable_files' => array('dynamic_finder_xpd'),
    'action' => "search",
    'action_title' => "",
    'action_href' => "",
    'show_help_icon' => false,
    'help_file_name' => "",
    'page_id' => 'dynamic_finder',
);
$oemr_ui = new OemrUI($arrOeUiSettings);

$eventDispatcher->addListener(PageHeadingRenderEvent::EVENT_PAGE_HEADING_RENDER, function ($event) {
    if ($event->getPageId() !== 'dynamic_finder') {
        return;
    }

    $event->setPrimaryMenuItem(new BaseMenuItem([
        'displayText' => xl('Add New Patient'),
        'linkClassList' => ['btn-add'],
        'id' => $GLOBALS['webroot'] . '/interface/new/new.php',
        'acl' => ['patients', 'demo', ['write', 'addonly']]
    ]));
});

$templateVars = [
    'oeContainer' => $oemr_ui->oeContainer(),
    'oeBelowContainerDiv' => $oemr_ui->oeBelowContainerDiv(),
    'pageHeading' => $oemr_ui->pageHeading(),
    'header0' => $header0,
    'header' => $header,
    'colcount' => $colcount,
    'headers' => $rp,
];

$twig = new TwigContainer(null, $GLOBALS['kernel']);
$t = $twig->getTwig();
echo $t->render('patient_finder/finder.html.twig', $templateVars);

?>
</body>
</html>
