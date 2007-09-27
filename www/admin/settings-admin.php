<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| Copyright (c) 2000-2003 the phpAdsNew developers                          |
| For contact details, see: http://www.phpadsnew.com/                       |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

/**
 * Obtain the server timezone information *before* the init script is
 * called, to ensure that the timezone information from the server is
 * not affected by any calls to date_default_timezone_set() or
 * putenv("TZ=...") to set the timezone manually.
 */
require_once '../../lib/OA/Admin/Timezones.php';
$aTimezone = OA_Admin_Timezones::getTimezone();

// Require the initialisation file
require_once '../../init.php';

// Required files
require_once MAX_PATH . '/lib/max/Admin/Languages.php';
require_once MAX_PATH . '/lib/max/Admin/Preferences.php';
require_once MAX_PATH . '/lib/max/Admin/Redirect.php';
require_once MAX_PATH . '/www/admin/lib-settings.inc.php';

// Security check
MAX_Permission::checkAccess(phpAds_Admin);

$errormessage = array();
if (isset($_POST['submitok']) && $_POST['submitok'] == 'true') {
    // Register input variables
    phpAds_registerGlobalUnslashed('admin', 'pwold', 'pw', 'pw2', 'admin_fullname', 'admin_email',
                                   'company_name', 'language', 'updates_enabled', 'admin_novice',
                                   'userlog_email', 'timezone_location', 'maintenance_autoMaintenance');

    // Set up the config object
    $config = new OA_Admin_Config();

    //  Update config with timezone changes
    if (isset($timezone_location)) {
        $timezone_location = OA_Admin_Timezones::getConfigTimezoneValue($timezone_location, $aTimezone);
        $config->setConfigChange('timezone', 'location', $timezone_location);
    }

    $config->setConfigChange('maintenance', 'autoMaintenance', isset($maintenance_autoMaintenance));

    if (!$config->writeConfigChange()) {
        // Unable to write the config file out
        $errormessage[0][] = $strUnableToWriteConfig;
    }

    // Set up the preferences object
    $preferences = new MAX_Admin_Preferences();
    if (isset($admin)) {
        if (!strlen($admin)) {
            $errormessage[0][] = $strInvalidUsername;
        } elseif (!MAX_Permission::isUsernameAllowed($pref['admin'], $admin)) {
            $errormessage[0][] = $strDuplicateClientName;
        } else {
            $preferences->setPrefChange('admin', $admin);
        }
    }
    if (isset($pwold) && strlen($pwold) || isset($pw) && strlen($pw) || isset($pw2) && strlen($pw2)) {
        $pref = $GLOBALS['_MAX']['PREF'];
        if (md5($pwold) != $pref['admin_pw']) {
            $errormessage[0][] = $strPasswordWrong;
        } elseif (!strlen($pw)  || strstr("\\", $pw)) {
            $errormessage[0][] = $strInvalidPassword;
        } elseif (strcmp($pw, $pw2)) {
            $errormessage[0][] = $strNotSamePasswords;
        } else {
            $admin_pw = $pw;
            $preferences->setPrefChange('admin_pw', md5($admin_pw));
        }
    }
    if (isset($admin_fullname)) {
        $preferences->setPrefChange('admin_fullname', $admin_fullname);
    }
    if (isset($admin_email)) {
        $preferences->setPrefChange('admin_email', $admin_email);
    }
    if (isset($company_name)) {
        $preferences->setPrefChange('company_name', $company_name);
    }
    if (isset($language)) {
        $preferences->setPrefChange('language', $language);
    }
    $preferences->setPrefChange('updates_enabled', isset($updates_enabled));
    $preferences->setPrefChange('admin_novice', isset($admin_novice));
    $preferences->setPrefChange('userlog_email', isset($userlog_email));

    if (!count($errormessage)) {
        if (!$preferences->writePrefChange()) {
            // Unable to update the preferences
            $errormessage[0][] = $strUnableToWritePrefs;
        } else {
            MAX_Admin_Redirect::redirect('settings-banner.php');
        }
    }

}

if (isset($message)) {
    phpAds_ShowMessage($message);
}
phpAds_PageHeader("5.1");
phpAds_ShowSections(array("5.1", "5.3", "5.4", "5.2", "5.5", "5.6"));
phpAds_SettingsSelection("admin");

$unique_users   = MAX_Permission::getUniqueUserNames($pref['admin']);

$aTimezones = OA_Admin_Timezones::availableTimezones(true);
$configTimezone = trim($GLOBALS['_MAX']['CONF']['timezone']['location']);
if (empty($configTimezone)) {
    // There is no value stored in the configuration file, as it
    // is not required (ie. the TZ comes from the environment) -
    // so set that environment value in the config file now
    $GLOBALS['_MAX']['CONF']['timezone']['location'] = $aTimezone['tz'];
}
// What display string do we need to show for the timezone?
if (empty($configTimezone) && $aTimezone['calculated']) {
    $strTimezoneToDisplay = $strTimezoneEstimated . '<br />' . $strTimezoneGuessedValue;
} else {
    $strTimezoneToDisplay = $strTimezone;
}

$settings = array (
    array (
        'text'  => $strLoginCredentials,
        'items' => array (
            array (
                'type'    => 'text',
                'name'    => 'admin',
                'text'    => $strAdminUsername,
                'check'   => 'unique',
                'unique'  => $unique_users
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'password',
                'name'    => 'pwold',
                'text'    => $strOldPassword
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'password',
                'name'    => 'pw',
                'text'    => $strNewPassword,
                'depends' => 'pwold!=""'
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'password',
                'name'    => 'pw2',
                'text'    => $strRepeatPassword,
                'depends' => 'pwold!=""',
                'check'   => 'compare:pw'
            )
        )
    ),
    array (
        'text'  => $strBasicInformation,
        'items' => array (
            array (
                'type'    => 'text',
                'name'    => 'admin_fullname',
                'text'    => $strAdminFullName,
                'size'    => 35
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'text',
                'name'    => 'admin_email',
                'text'    => $strAdminEmail,
                'size'    => 35,
                'check'   => 'email'
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'text',
                'name'    => 'company_name',
                'text'    => $strCompanyName,
                'size'    => 35
            )
        )
    ),
    array (
        'text'  => $strPreferences,
        'items' => array (
            array (
                'type'    => 'select',
                'name'    => 'language',
                'text'    => $strLanguage,
                'items'   => MAX_Admin_Languages::AvailableLanguages()
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'checkbox',
                'name'    => 'admin_novice',
                'text'    => $strAdminNovice
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'checkbox',
                'name'    => 'userlog_email',
                'text'    => $strUserlogEmail
            ),
            array (
                'type'    => 'break'
            ),
            array (
                'type'    => 'checkbox',
                'name'    => 'maintenance_autoMaintenance',
                'text'	  => $strEnableAutoMaintenance
            ),
        )
    ),
    array (
        'text'    => $GLOBALS['strSyncSettings'],
        'items'   => array (
            array (
                'type'    => 'checkbox',
                'name'    => 'updates_enabled',
                'text'    => $strAdminCheckUpdates,
            )
        )
    ),
    array (
        'text'  => $strTimezoneInformation,
        'items' => array (
            array (
                'type'    => 'select',
                'name'    => 'timezone_location',
                'text'    => $strTimezoneToDisplay,
                'items'   => $aTimezones
            )
        )
    )
);

phpAds_ShowSettings($settings, $errormessage);
phpAds_PageFooter();

?>
