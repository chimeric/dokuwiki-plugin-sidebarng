<?php
/**
 * Example Sidebar of the sidebarng plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */

// This is a simple example of a custom sidebar.
// You can use custom sidebars to output anything you want.
// Simple Text:
print 'Hello World!';

// wiki pages
print p_wiki_xhtml('start');

// the output of dokuwiki plugins (not, using p_render() is an expensive task
// as it instantiates a new DokuWiki renderer
print p_render('xhtml', p_get_instructions('~~INFO:syntaxplugins~~'), $info);

// Or just plain HTML
?>
<h1>A H1 Headline</h1>
