<?php

/**
 * Isolated Address Book view for Site Administrator.
 * Omits the root 'admin' account from directory listings while maintaining
 * standard provider and contact directory capabilities.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../../../../globals.php");
require_once(\OpenEMR\Core\OEGlobalsBag::getInstance()->getSrcDir() . "/options.inc.php");

use OpenEMR\Common\Acl\AccessDeniedHelper;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;

if (!AclMain::aclCheckCore('admin', 'practice')) {
    AccessDeniedHelper::denyWithTemplate("ACL check failed for admin/practice: Address Book", xl("Address Book"));
}

$session = SessionWrapperFactory::getInstance()->getActiveSession();
if (!empty($_POST)) {
    CsrfUtils::checkCsrfInput(INPUT_POST, dieOnFail: true);
}

$popup = empty($_GET['popup']) ? 0 : 1;
$rtn_selection = 0;
if ((!empty($_GET['popup']) && $_GET['popup'] == 2) || (!empty($_POST['popup']) && $_POST['popup'] == 2)) {
    $rtn_selection = 2;
}

$form_fname = trim($_POST['form_fname'] ?? '');
$form_lname = trim($_POST['form_lname'] ?? '');
$form_specialty = trim($_POST['form_specialty'] ?? '');
$form_organization = trim($_POST['form_organization'] ?? '');
$form_npi = trim($_POST['form_npi'] ?? '');
$form_abook_type = trim($_REQUEST['form_abook_type'] ?? '');
$form_external = !empty($_POST['form_external']) ? 1 : 0;

$sqlBindArray = [];
// Strictly filter out username = 'admin'
$query = "SELECT u.*, lo.option_id AS ab_name, lo.option_value as ab_option FROM users AS u " .
    "LEFT JOIN list_options AS lo ON " .
    "list_id = 'abook_type' AND option_id = u.abook_type AND activity = 1 " .
    "WHERE u.active = 1 AND ( u.authorized = 1 OR ( u.username = '' OR u.username IS NULL )) " .
    "AND (u.username != 'admin' OR u.username IS NULL OR u.username = '') ";

if ($form_organization) {
    $query .= "AND u.organization LIKE ? ";
    array_push($sqlBindArray, $form_organization . "%");
}

if ($form_lname) {
    $query .= "AND u.lname LIKE ? ";
    array_push($sqlBindArray, $form_lname . "%");
}

if ($form_fname) {
    $query .= "AND u.fname LIKE ? ";
    array_push($sqlBindArray, $form_fname . "%");
}

if ($form_specialty) {
    $query .= "AND u.specialty LIKE ? ";
    array_push($sqlBindArray, "%" . $form_specialty . "%");
}

if ($form_npi) {
    $query .= "AND u.npi LIKE ? ";
    array_push($sqlBindArray, "%" . $form_npi . "%");
}

if ($form_abook_type) {
    $query .= "AND u.abook_type LIKE ? ";
    array_push($sqlBindArray, $form_abook_type);
}

if ($form_external) {
    $query .= "AND u.abook_type = 'external_provider' ";
}

if ($form_lname) {
    $query .= "ORDER BY u.lname, u.fname, u.mname";
} elseif ($form_organization) {
    $query .= "ORDER BY u.organization";
} else {
    $query .= "ORDER BY u.organization, u.lname, u.fname";
}

$query .= " LIMIT 500";
$res = sqlStatement($query, $sqlBindArray);
?>
<!DOCTYPE html>
<html>
<head>
<?php Header::setupHeader(['common']); ?>
<title><?php echo xlt('Address Book'); ?></title>
</head>
<body class="body_top">
<div class="container-fluid">
    <div class="nav navbar-fixed-top body_title">
        <div class="col-md-12">
            <h3><?php echo xlt('Address Book'); ?></h3>
            <form class='navbar-form' method='post' action='<?php echo attr($_SERVER['SCRIPT_NAME']); ?>' onsubmit='return top.restoreSession()'>
                <input type="hidden" name="csrf_token_form" value="<?php echo CsrfUtils::collectCsrfToken(session: $session); ?>" />
                <input type="hidden" name="popup" value="<?php echo attr($rtn_selection); ?>" />
                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-2">
                            <label for="form_organization"><?php echo xlt('Organization') ?>:</label>
                            <input type='text' class="form-control inputtext" name='form_organization' size='10' value='<?php echo attr($form_organization); ?>' title='<?php echo xla("All or part of the organization") ?>'/>&nbsp;
                        </div>
                        <div class="col-sm-2">
                            <label for="form_fname"><?php echo xlt('First Name') ?>:</label>
                            <input type='text' class="form-control inputtext" name='form_fname' size='10' value='<?php echo attr($form_fname); ?>' title='<?php echo xla("All or part of the first name") ?>'/>&nbsp;
                        </div>
                        <div class="col-sm-2">
                            <label for="form_lname"><?php echo xlt('Last Name') ?>:</label>
                            <input type='text' class="form-control inputtext" name='form_lname' size='10' value='<?php echo attr($form_lname); ?>' title='<?php echo xla("All or part of the last name") ?>'/>&nbsp;
                        </div>
                        <div class="col-sm-2">
                            <label for="form_specialty"><?php echo xlt('Specialty') ?>:</label>
                            <input type='text' class="form-control inputtext" name='form_specialty' size='10' value='<?php echo attr($form_specialty); ?>' title='<?php echo xla("Any part of the desired specialty") ?>'/>&nbsp;
                        </div>
                        <div class="col-sm-2">
                            <label for="form_npi"><?php echo xlt('NPI') ?>:</label>
                            <input type='text' class="form-control inputtext" name='form_npi' size='10' value='<?php echo attr($form_npi); ?>' title='<?php echo xla("Any part of the desired NPI") ?>'/>&nbsp;
                        </div>
                        <div class="col-sm-2">
                            <?php
                            echo '<label>' . xlt('Type') . ": " . '</label>';
                            echo generate_select_list("form_abook_type", "abook_type", $form_abook_type, '', 'All');
                            ?>
                        </div>
                    </div>
                    <input type='checkbox' id="formExternal" name='form_external' value='1'<?php echo ($form_external) ? ' checked ' : ''; ?> title='<?php echo xla("Omit internal users?") ?>' />
                    <label for="formExternal"><?php echo xlt('External Only') ?></label>
                    <input type='button' class='btn btn-primary' value='<?php echo xla("Add New"); ?>' onclick='doedclick_add(document.forms[0].form_abook_type.value)' />&nbsp;&nbsp;
                    <input type='submit' title='<?php echo xla("Use % alone in a field to just sort on that column") ?>' class='btn btn-primary btn-search' name='form_search' value='<?php echo xla("Search") ?>'/>
                </div>
            </form>
        </div>
    </div>

    <div style="margin-top: 110px;" class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover">
            <thead>
                <th title='<?php echo xla('Click to view or edit'); ?>'><?php echo xlt('Organization'); ?></th>
                <th><?php echo xlt('Name'); ?></th>
                <th><?php echo xlt('Local'); ?></th>
                <th><?php echo xlt('Type'); ?></th>
                <th><?php echo xlt('Specialty'); ?></th>
                <th><?php echo xlt('NPI'); ?></th>
                <th><?php echo xlt('Phone(W)'); ?></th>
                <th><?php echo xlt('Mobile'); ?></th>
                <th><?php echo xlt('Fax'); ?></th>
                <th><?php echo xlt('Email'); ?></th>
                <th><?php echo xlt('Street'); ?></th>
                <th><?php echo xlt('City'); ?></th>
                <th><?php echo xlt('State'); ?></th>
                <th><?php echo xlt('Postal'); ?></th>
            </thead>
            <?php
            $encount = 0;
            while ($row = sqlFetchArray($res)) {
                ++$encount;
                $username = $row['username'];
                if (!$row['active']) {
                    $username = '--';
                }

                $displayName = $row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'];
                if ($row['suffix'] > '') {
                    $displayName .= ", " . $row['suffix'];
                }

                if (AclMain::aclCheckCore('admin', 'practice') || (empty($username) && empty($row['ab_name']))) {
                    $trTitle = xl('Edit') . ' ' . $displayName;
                    echo " <tr class='address_names detail' style='cursor:pointer' " .
                        "onclick='doedclick_edit(" . attr_js($row['id']) . ")' title='" . attr($trTitle) . "'>\n";
                } else {
                    $trTitle = $displayName . " (" . xl("Not Allowed to Edit") . ")";
                    echo " <tr class='address_names detail' title='" . attr($trTitle) . "'>\n";
                }

                echo "  <td>" . text($row['organization']) . "</td>\n";
                echo "  <td>" . text($displayName) . "</td>\n";
                echo "  <td>" . ($username ? '*' : '') . "</td>\n";
                echo "  <td>" . generate_display_field(['data_type' => '1', 'list_id' => 'abook_type'], $row['ab_name']) . "</td>\n";
                echo "  <td>" . text($row['specialty']) . "</td>\n";
                echo "  <td>" . text($row['npi']) . "</td>\n";
                echo "  <td>" . text($row['phonew1']) . "</td>\n";
                echo "  <td>" . text($row['phonecell']) . "</td>\n";
                echo "  <td>" . text($row['fax']) . "</td>\n";
                echo "  <td>" . text($row['email']) . "</td>\n";
                echo "  <td>" . text($row['street']) . "</td>\n";
                echo "  <td>" . text($row['city']) . "</td>\n";
                echo "  <td>" . text($row['state']) . "</td>\n";
                echo "  <td>" . text($row['zip']) . "</td>\n";
                echo " </tr>\n";
            }
            ?>
        </table>
    </div>

    <?php if ($popup) { ?>
        <?php echo Header::setupAssets(['topdialog']); ?>
    <?php } ?>
    <script>
    <?php if ($popup) {
        require(OEGlobalsBag::getInstance()->getSrcDir() . "/restoreSession.php");
    } ?>

    function refreshme() {
        document.forms[0].submit();
    }

    function doedclick_add(type) {
        top.restoreSession();
        let webroot = (typeof top.webroot_url !== 'undefined') ? top.webroot_url : '';
        let url = webroot + '/interface/usergroup/addrbook_edit.php?type=' + encodeURIComponent(type);
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("popup")) {
            url += "&popup=" + urlParams.get("popup");
        }
        dlgopen(url, '_blank', 650, (screen.availHeight * 75/100));
    }

    function doedclick_edit(userid) {
        let rtn_selection = <?php echo js_escape($rtn_selection); ?>;
        if (rtn_selection) {
            dlgclose('contactCallBack', userid);
        }
        top.restoreSession();
        let webroot = (typeof top.webroot_url !== 'undefined') ? top.webroot_url : '';
        dlgopen(webroot + '/interface/usergroup/addrbook_edit.php?userid=' + encodeURIComponent(userid), '_blank', 650, (screen.availHeight * 75/100));
    }
    </script>
</div>
</body>
</html>

