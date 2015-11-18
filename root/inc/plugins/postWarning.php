<?php
/**
 * This file is part of Post Warning plugin for MyBB.
 * Copyright (C) Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined("IN_MYBB")) exit;


/**
 * Add hooks
 * 
 */
$plugins->add_hook('newreply_end', ['postWarning', 'replyStart']);
$plugins->add_hook('datahandler_post_validate_post', ['postWarning', 'replyValidate']);
$plugins->add_hook('xmlhttp', ['postWarning', 'XMLHTTP']);
$plugins->add_hook('pre_output_page', ['postWarning', 'pluginThanks']);

/**
 * Standard MyBB info function
 * 
 */
function postWarning_info() {
    global $lang;
    
    $lang->load("postWarning");
    $lang->postWarningDesc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="3BTVZBUG6TMFQ">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->postWarningDesc;

    return Array(
        'name' => $lang->postWarningName,
        'description' => $lang->postWarningDesc,
        'website' => 'https://lukasztkacz.com',
        'author' => 'Lukasz Tkacz',
        'authorsite' => 'https://lukasztkacz.com',
        'version' => '1.0',
        'guid' => '',
        'compatibility' => '18*',
        'codename' => 'post_warning',
    );
}

/**
 * Standard MyBB activation functions 
 * 
 */
function postWarning_activate() {
    global $db;
    
    require_once(MYBB_ROOT . '/inc/adminfunctions_templates.php');
    find_replace_templatesets('newreply', '#' . preg_quote('{$editdraftpid}') . '#', '<input type="hidden" name="lastpid" id="lastpid" value="{$last_pid}" />{$editdraftpid}');   
    find_replace_templatesets('showthread', '#' . preg_quote('</head>') . '#', '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/postWarning.js?ver=1804"></script></head>');
    
    $db->update_query("settings", array("value" => "1"), "name = 'threadreview'");
    rebuild_settings();
}                  

function postWarning_deactivate() {
    require_once(MYBB_ROOT . '/inc/adminfunctions_templates.php');
    find_replace_templatesets('newreply', '#' . preg_quote('<input type="hidden" name="lastpid" id="lastpid" value="{$last_pid}" />') . '#', '');
    find_replace_templatesets('showthread', '#' . preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/postWarning.js?ver=1804"></script>') . '#', '');
}

/**
 * Plugin Class 
 * 
 */
class postWarning 
{
    public static function replyStart()
    {
        global $db, $tid, $last_pid;
        
		$query = $db->simple_select("posts", "MAX(pid) as mpid", "tid='{$tid}'");
		$last_pid = $db->fetch_field($query, "mpid");
    }
    
    
    public static function replyValidate(&$validator)
    {
        global $mybb, $db, $tid, $last_pid;
        
        // Was there a new post since we hit the quick reply button?
        $lastpid = $mybb->get_input('lastpid', MyBB::INPUT_INT); 
        if (!$lastpid) {
            return;
        }
    	
        $query = $db->simple_select("posts", "MAX(pid) as mpid", "tid='{$tid}'");
    	$last_pid = $db->fetch_field($query, "mpid");
        
        if ($last_pid > $lastpid) {
            global $lang;
            $lang->load("postWarning");
            $validator->set_error("post_warning_error");
        }
    }
    
    public static function XMLHTTP()
    {
        global $mybb, $db;

        if (!$mybb->get_input('postwarning', MyBB::INPUT_INT)) {
            return;
        }
        
        // Check key
        verify_post_check($mybb->get_input('my_post_key'));
        
        // Was there a new post since we hit the quick reply button?
        $lastpid = $mybb->get_input('lastpid', MyBB::INPUT_INT); 
        $tid = $mybb->get_input('tid', MyBB::INPUT_INT); 
        if (!$lastpid || !$tid) {
            return 2;
        }
        
        $query = $db->simple_select("posts", "MAX(pid) as mpid", "tid='{$tid}'");
    	$last_pid = $db->fetch_field($query, "mpid");
        
        if ($last_pid > $lastpid) {
            echo 1;
            exit;
        }
    }    
    
    public static function pluginThanks(&$content) 
    {
        global $session, $lukasamd_thanks;
        
        if (!isset($lukasamd_thanks) && $session->is_spider) {
            $thx = '<div style="margin:auto; text-align:center;">This forum uses <a href="https://lukasztkacz.com">Lukasz Tkacz</a> MyBB addons.</div></body>';
            $content = str_replace('</body>', $thx, $content);
            $lukasamd_thanks = true;
        }
    }

}  