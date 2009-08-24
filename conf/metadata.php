<?php
/**
 * Configuration metadata for the SidebarNG plugin
 */
$meta['pos']         = array('multichoice', '_choices' => array('left', 'right'));
$meta['pagename']    = array('string', '_pattern' => '#[a-z0-9]*#');
$meta['user_ns']     = array('string', '_pattern' => '#^[a-z:]*#');
$meta['group_ns']    = array('string', '_pattern' => '#^[a-z:]*#');
$meta['order']       = array('string', '_pattern' => '#[a-z0-9,]*#');
$meta['content']     = array('multicheckbox', '_choices' => array('main','user','group','namespace','toolbox','trace','extra'));
$meta['main_always'] = array('onoff');
// vim:ts=4:sw=4:et:enc=utf-8:
