<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
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

define('SMARTY_DIR', MAX_PATH . '/lib/smarty/');

require_once MAX_PATH . '/lib/smarty/Smarty.class.php';
require_once MAX_PATH . '/lib/OA/Dll.php';

require_once('Date.php');


/**
 * @author     Matteo Beccati <matteo.beccati@openads.org>
 */
class OA_Admin_Template extends Smarty
{
    /**
     * @var string
     */
    var $templateName;

    /**
     * @var string
     */
    var $cacheId;

    /**
     * @var int
     */
    var $_tabIndex = 0;

    function OA_Admin_Template($templateName)
    {
        $this->template_dir = MAX_PATH . '/lib/templates/admin';
        $this->compile_dir  = MAX_PATH . '/var/templates_compiled';
        $this->cache_dir    = MAX_PATH . '/var/cache';

        $this->caching = 0;
        $this->cache_lifetime = 3600;

        $this->register_function('t', array('OA_Admin_Template',  '_function_t'));
        
        $this->register_function('tabindex', array('OA_Admin_Template',  '_function_tabindex'));

        $this->register_function('oa_icon', array('OA_Admin_Template',  '_function_oa_icon'));
        $this->register_function('oa_title_sort', array('OA_Admin_Template',  '_function_oa_title_sort'));

        $this->register_function('oac_captcha', array('OA_Admin_Template',  '_function_oac_captcha'));

        $this->register_function('phpAds_ShowBreak', array('OA_Admin_Template',  '_function_phpAds_ShowBreak'));
        $this->register_function('phpAds_DelConfirm', array('OA_Admin_Template',  '_function_phpAds_DelConfirm'));

        $this->register_function('showStatusText', array('OA_Admin_Template',  '_function_showStatusText'));

        $this->register_block('oa_edit', array('OA_Admin_Template',  '_block_edit'));

        $this->templateName = $templateName;

        $this->assign('phpAds_TextDirection',  $GLOBALS['phpAds_TextDirection']);
        $this->assign('phpAds_TextAlignLeft',  $GLOBALS['phpAds_TextAlignLeft']);
        $this->assign('phpAds_TextAlignRight', $GLOBALS['phpAds_TextAlignRight']);
    }

    /**
     * A method to set a cache id for the current page
     *
     * @param string $cacheId
     */
    function setCacheId($cacheId = null)
    {
        if (is_null($cacheId)) {
            $this->cacheId = null;
            $this->caching = 0;
        } else {
            $this->cacheId = md5($cacheId);
            $this->caching = 1;
        }
    }

    /**
     * A method to set the cached version of the template to be used until
     * a certain date/time
     *
     * @param Date $oDate
     */
    function setCacheExpireAt($oDate)
    {
        $timeStamp = strftime($oDate->format('%Y-%m-%d %H:%M:%S'));
        $this->cache_lifetime = $timeStamp - time();
        $this->caching = 2;
    }

    /**
     * A method to set the cached vertsion of the template to expire after
     * a time span
     *
     * @param Date_Span $oSpan
     */
    function setCacheLifetime($oSpan)
    {
        $this->cache_lifetime = $oSpan->toSeconds();
        $this->caching = 2;
    }

    function is_cached()
    {
        return parent::is_cached($this->templateName, $this->cacheId);
    }

    function display()
    {
        parent::display($this->templateName, $this->cacheId);
    }

    function _function_t($aParams, &$smarty)
    {
        if (!empty($aParams['str'])) {
            return $GLOBALS['str'.$aParams['str']];
        }
        if (!empty($aParams['key'])) {
            return $GLOBALS['key'.$aParams['key']];
        }
        $smarty->trigger_error("t: missing 'str' or 'key' parameters");
    }
    
    function _function_showStatusText($aParams, &$smarty)
    {
        global $strCampaignStatusRunning, $strCampaignStatusPaused, $strCampaignStatusAwaiting,
               $strCampaignStatusExpired, $strCampaignStatusApproval, $strCampaignStatusRejected;

        $status = $aParams['status'];
        $an_status = $aParams['an_status'];

        if (isset($status)) {
                switch ($status) {
                	case OA_ENTITY_STATUS_PENDING:
                	    if ($an_status == OA_ENTITY_ADNETWORKS_STATUS_APPROVAL) {
                    	    $class = 'sts sts-awaiting';
                    	    $text  = $strCampaignStatusApproval;
                	    }
                	    
                	    if ($an_status == OA_ENTITY_ADNETWORKS_STATUS_REJECTED) {
                    	    $class = 'sts sts-rejected';
                    	    $text  = $strCampaignStatusRejected;
                	    }
                		break;
                	case OA_ENTITY_STATUS_RUNNING:
                	    $class = 'sts sts-accepted';
                	    $text  = $strCampaignStatusRunning;
                		break;
                	case OA_ENTITY_STATUS_PAUSED:
                	    $class = 'sts sts-paused';
                	    $text  = $strCampaignStatusPaused;
                		break;
                	case OA_ENTITY_STATUS_AWAITING:
                	    $class = 'sts not-started';
                	    $text  = $strCampaignStatusAwaiting;
                		break;
                	case OA_ENTITY_STATUS_EXPIRED:
                	    $class = 'sts sts-finished';
                	    $text  = $strCampaignStatusExpired;
                		break;
                	case OA_ENTITY_STATUS_APPROVAL:
                	    $class = 'sts sts-awaiting';
                	    $text  = $strCampaignStatusApproval;
                		break;
                	case OA_ENTITY_STATUS_REJECTED:
                	    $class = 'sts sts-rejected';
                	    $text  = $strCampaignStatusRejected;
                		break;
                }
                if ($status == OA_ENTITY_STATUS_APPROVAL) {
                    $text = "<a href='campaign-edit.php?clientid=".$aParams['clientid']."&campaignid=".$aParams['campaignid']."'>" .
                            $text . "</a>";
                }
                return '<span class="'.$class.'">' . $text . '</span>';
        }
        
        $smarty->trigger_error("showStatusText: missing 'status' parameter");
    }

    function _function_tabindex($aParams, &$smarty)
    {
        return ' tabindex="'.++$smarty->_tabIndex.'"';
    }

    function _function_oa_icon($aParams, &$smarty)
    {
        if (!empty($aParams['banner']) && is_array($aParams['banner'])) {
            $type   = 'banner';
            $banner = $aParams['banner'];

            $campaign_active = isset($aParams['campaign']['status']) ? $aParams['campaign']['status'] == OA_ENTITY_STATUS_RUNNING : false;
            $active          = $banner['status'] == OA_ENTITY_STATUS_RUNNING && $campaign_active;

            switch ($banner['type'])
            {
                case 'html': $flavour = '-html'; break;
                case 'txt':  $flavour = '-text'; break;
                case 'url':  $flavour = '-url'; break;
                default:     $flavour = '-stored'; break;
            }
        }

        if (!empty($type)) {
            return 'images/icon-'.$type.$flavour.($active ? '' : '-d').'.gif';
        }

        $smarty->trigger_error("t: missing 'banner' parameter");
    }

    function _function_oa_title_sort($aParams, &$smarty)
    {
        if (!empty($aParams['str'])) {
            if (!empty($aParams['item'])) {
                $str  = $this->_function_t($aParams, $smarty);
                $item = $aParams['item'];

                $order = !empty($aParams['order']) ? $aParams['order'] : 'down';
                $url   = !empty($aParams['url'])     ? $aParams['url']     : '#';
                $url   .= strpos($url, '?') !== false ? '&' : '?';

                $buffer = '<a href="'.htmlspecialchars($url.'listorder='.$item).'">'.$str.'</a>';

                $listorder = $smarty->get_template_vars('listorder');
                $orderdirection = $smarty->get_template_vars('orderdirection');

                if (empty($listorder) && !empty($aParams['default']) && $aParams['default']) {
                    $listorder = $item;
                }

                if (empty($orderdirection)) {
                    $orderdirection = $order;
                }

                if ($listorder == $item) {
                    $order = $orderdirection == 'down' ? 'up' : 'down';
                    $caret = $orderdirection == 'down' ? 'ds'  : 'u';

                    $buffer .= ' <a href="'.htmlspecialchars($url.'orderdirection='.$order).'">';
                    $buffer .= '<img src="images/caret-'.$caret.'.gif" border="0" alt="" title="">';
                    $buffer .= '</a>';
                }

                return $buffer;
            } else {
                $smarty->trigger_error("t: missing 'item' parameter");
            }
        } else {
            $smarty->trigger_error("t: missing 'str'parameter");
        }
    }

    function _function_oac_captcha()
    {
        require_once MAX_PATH . '/lib/OA/Central/Common.php';

        return OA_Central_Common::getCaptchaUrl();
    }

    function _block_edit($aParams, $content, &$smarty, &$repeat)
    {
        static $break = false;

        if (!$repeat) {
            $aParams['content'] = $content;
            if (isset($aParams['params']) && is_array($aParams)) {
                $aParams += $aParams['params'];
            }
            if (!isset($aParams['break'])) {
                $aParams['break'] = $break;
            }

            $smarty->assign('_e', $aParams);
            $result = $smarty->fetch('edit-table/main.html');
            $smarty->clear_assign('_e');

            $break = $aParams['type'] != 'section';

            return $result;
        }
    }

    // OLD FUNCTIONS

    function _function_phpAds_DelConfirm($aParams, &$smarty)
    {
        return phpAds_DelConfirm($this->_function_t($aParams, $smarty));
    }

    function _function_phpAds_ShowBreak()
    {
        ob_start();
        phpAds_ShowBreak();
        return ob_get_clean();
    }
}

?>