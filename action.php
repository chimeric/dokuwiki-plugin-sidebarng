<?php
/**
 * DokuWiki Action Plugin SidebarNG
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class action_plugin_sidebarng extends DokuWiki_Action_Plugin {

    // register hook
    function register(&$controller) {
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_before');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'AFTER', $this, '_after');
    }

    function _before(&$event, $param) {
        $pos = $this->getConf('pos');
        ob_start();
        $this->p_sidebar($pos);
        $this->sidebar = ob_get_contents();
        if(empty($this->sidebar) && !$this->getConf('main_always')) {
            print '<div class="page">' . DOKU_LF;
        } else {
            if($pos == 'left') {
                    print '<div class="' . $pos . '_sidebar">' . DOKU_LF;
                    print $sidebar;
                    print '</div>' . DOKU_LF;
                    print '<div class="page_right">' . DOKU_LF;
            } else {
                print '<div class="page_left">' . DOKU_LF;
            }
        }
    }

    function _after(&$event, $param) {
        $pos = $this->getConf('pos');
        if(empty($this->sidebar) && !$this->getConf('main_always')) {
            print '</div>' . DOKU_LF;
        } else {
            if($pos == 'left') {
            print '</div>' . DOKU_LF; 
            } else {
                print '</div>' . DOKU_LF;
                print '<div class="' . $pos . '_sidebar">' . DOKU_LF;
                $this->p_sidebar($pos);
                print '</div>'. DOKU_LF;
            }
        }
    }

    /**
     * Displays the sidebar
     *
     * Michael Klier <chi@chimeric.de>
     */
    function p_sidebar($pos) {
        $sb_order   = explode(',', $this->getConf('order'));
        $sb_content = explode(',', $this->getConf('content'));
        $notoc      = (in_array('toc', $sb_content)) ? true : false;

        // process contents by given order
        foreach($sb_order as $sb) {
            if(in_array($sb,$sb_content)) {
                $key = array_search($sb,$sb_content);
                unset($sb_content[$key]);
                $this->_sidebar_dispatch($sb,$pos);
            }
        }

        // check for left content not specified by order
        if(is_array($sb_content) && !empty($sb_content) > 0) {
            foreach($sb_content as $sb) {
                $this->_sidebar_dispatch($sb,$pos);
            }
        }
    }

    /**
     * Prints given sidebar box
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _sidebar_dispatch($sb, $pos) {
        global $lang;
        global $conf;
        global $ID;
        global $REV;
        global $INFO;

        $svID  = $ID;   // save current ID
        $svREV = $REV;  // save current REV 

        $pname = $this->getConf('pagename');

        switch($sb) {

            case 'main':
                $main_sb = $pname;
                if(@page_exists($main_sb) && auth_quickaclcheck($main_sb) >= AUTH_READ) {
                    $always = $this->getConf('main_always');
                    if($always or (!$always && !getNS($ID))) {
                        print '<div class="main_sidebar sidebar_box">' . DOKU_LF;
                        print $this->p_sidebar_xhtml($main_sb,$pos) . DOKU_LF;
                        print '</div>' . DOKU_LF;
                    }
                } else {
                    $out = $this->locale_xhtml('nosidebar');
                    $link = '<a href="' . wl($pname) . '" class="wikilink2">' . $pname . '</a>' . DOKU_LF;
                    print '<div class="main_sidebar sidebar_box">' . DOKU_LF;
                    print str_replace('LINK', $link, $out);
                    print '</div>' . DOKU_LF;
                }
                break;

            case 'namespace':
                $user_ns  = $this->getConf('user_ns');
                $group_ns = $this->getConf('group_ns');
                if(!preg_match("/^".$user_ns.":.*?$|^".$group_ns.":.*?$/", $svID)) { // skip group/user sidebars and current ID
                    $ns_sb = $this->_getNsSb($svID);
                    if($ns_sb && auth_quickaclcheck($ns_sb) >= AUTH_READ) {
                        print '<div class="namespace_sidebar sidebar_box">' . DOKU_LF;
                        print $this->p_sidebar_xhtml($ns_sb,$pos) . DOKU_LF;
                        print '</div>' . DOKU_LF;
                    }
                }
                break;

            case 'user':
                $user_ns = $this->getConf('user_ns');
                if(isset($INFO['userinfo']['name'])) {
                    $user = $_SERVER['REMOTE_USER'];
                    $user_sb = $user_ns . ':' . $user . ':' . $pname;
                    if(@page_exists($user_sb)) {
                        $subst = array('pattern' => array('/@USER@/'), 'replace' => array($user));
                        print '<div class="user_sidebar sidebar_box">' . DOKU_LF;
                        print $this->p_sidebar_xhtml($user_sb,$pos,$subst) . DOKU_LF;
                        print '</div>';
                    }
                    // check for namespace sidebars in user namespace too
                    if(preg_match('/'.$user_ns.':'.$user.':.*/', $svID)) {
                        $ns_sb = $this->_getNsSb($svID); 
                        if($ns_sb && $ns_sb != $user_sb && auth_quickaclcheck($ns_sb) >= AUTH_READ) {
                            print '<div class="namespace_sidebar sidebar_box">' . DOKU_LF;
                            print $this->p_sidebar_xhtml($ns_sb,$pos) . DOKU_LF;
                            print '</div>' . DOKU_LF;
                        }
                    }

                }
                break;

            case 'group':
                $group_ns = $this->getConf('group_ns');
                if(isset($INFO['userinfo']['name'], $INFO['userinfo']['grps'])) {
                    foreach($INFO['userinfo']['grps'] as $grp) {
                        $group_sb = $group_ns.':'.$grp.':'.$pname;
                        if(@page_exists($group_sb) && auth_quickaclcheck(cleanID($group_sb)) >= AUTH_READ) {
                            $subst = array('pattern' => array('/@GROUP@/'), 'replace' => array($grp));
                            print '<div class="group_sidebar sidebar_box">' . DOKU_LF;
                            print $this->p_sidebar_xhtml($group_sb,$pos,$subst) . DOKU_LF;
                            print '</div>' . DOKU_LF;
                        }
                    }
                }
                break;

            case 'toolbox':
                $actions = array('admin', 'edit', 'history', 'recent', 'backlink', 'subscribe', 'subscribens', 'index', 'login', 'profile');

                print '<div class="toolbox_sidebar sidebar_box">' . DOKU_LF;
                print '  <ul>' . DOKU_LF;

                foreach($actions as $action) {
                    if(!actionOK($action)) continue;
                    // start output buffering
                    if($action == 'edit') {
                        // check if new page button plugin is available
                        if(!plugin_isdisabled('npd') && ($npd =& plugin_load('helper', 'npd'))) {
                            $npb = $npd->html_new_page_button(true);
                            if($npb) {
                                print '    <li class="level1"><div class="li">';
                                print $npb;
                                print '</div></li>' . DOKU_LF;
                            }
                        }
                    }
                    ob_start();
                    print '   <li><div class="li">';
                    if(tpl_actionlink($action)) {
                        print '</div></li>' . DOKU_LF;
                        ob_end_flush();
                    } else {
                        ob_end_clean();
                    }
                }

                print '  </ul>' . DOKU_LF;
                print '</div>' . DOKU_LF;
                break;

            case 'trace':
                print '<div class="trace_sidebar sidebar_box">' . DOKU_LF;
                print '  <h1>'.$lang['breadcrumb'].'</h1>' . DOKU_LF;
                print '  <div class="breadcrumbs">' . DOKU_LF;
                ($conf['youarehere'] != 1) ? tpl_breadcrumbs() : tpl_youarehere();
                print '  </div>' . DOKU_LF;
                print '</div>' . DOKU_LF;
                break;

            case 'extra':
                print '<div class="extra_sidebar sidebar_box">' . DOKU_LF;
                @include(dirname(__FILE__).'/sidebar.html');
                print '</div>' . DOKU_LF;
                break;

            default:
                // check for user defined sidebars
                if(@file_exists(DOKU_PLUGIN.'sidebarng/sidebars/'.$sb.'/sidebar.php')) {
                    print '<div class="'.$sb.'_sidebar sidebar_box">' . DOKU_LF;
                    @require_once(DOKU_PLUGIN.'sidebarng/sidebars/'.$sb.'/sidebar.php');
                    print '</div>' . DOKU_LF;
                }
                break;
        }

        // restore ID and REV
        $ID  = $svID;
        $REV = $svREV;
    }

    /**
     * Removes the TOC of the sidebar pages and 
     * shows a edit button if the user has enough rights
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function p_sidebar_xhtml($sb,$pos,$subst=array()) {
        $data = p_wiki_xhtml($sb,'',false);
        if(!empty($subst)) {
            $data = preg_replace($subst['pattern'], $subst['replace'], $data);
        }
        if(auth_quickaclcheck($sb) >= AUTH_EDIT) {
            $data .= '<div class="secedit">'.html_btn('secedit',$sb,'',array('do'=>'edit','rev'=>'','post')).'</div>';
        }
        // strip TOC
        $data = preg_replace('/<div class="toc">.*?(<\/div>\n<\/div>)/s', '', $data);
        // replace headline ids for XHTML compliance
        $data = preg_replace('/(<h.*?><a.*?name=")(.*?)(".*?id=")(.*?)(">.*?<\/a><\/h.*?>)/','\1sb_'.$pos.'_\2\3sb_'.$pos.'_\4\5', $data);
        return ($data);
    }

    /**
     * Searches for namespace sidebars
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _getNsSb($id) {
        $pname = $this->getConf('pagename');
        $ns_sb = '';
        $path  = explode(':', $id);
        $found = false;

        while(count($path) > 0) {
            $ns_sb = implode(':', $path).':'.$pname;
            if(@page_exists($ns_sb)) return $ns_sb;
            array_pop($path);
        }
        
        // nothing found
        return false;
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
