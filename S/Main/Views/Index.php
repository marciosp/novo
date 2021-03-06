<?php

/**
 * 
 * This file is part of the S framework for PHP, a framework based on the O framework.
 * 
 * @license http://opensource.org/licenses/GPL-3.0 GPL-3.0
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 19/07/2013
 * 
 * This file is responsible for the main view creation, using the ExtJS framework
 */
//
// use O Plugin for integration with ExtJS
use O\UI\Plugins\ExtJS\Manager as m;
// use S App
use S\App;
// use V.Hook
use V\Hook\Manager as HookManager;

// get the config
$cfg = S\App::cfg();

// extra head files (.css & .js, for example)
$e_head = '';
if (isset($cfg['head']))
    $e_head = implode("\n", $cfg['head']);

// base path
$base_path = rtrim($cfg['paths']['base_path'], '/');

// get the menus
$cfg_menus = is_array($cfg['menus']) ? $cfg['menus'] : call_user_func($cfg['menus']);

// besides the basic menu config, we have the modules (it expects that each module has a Module.php that returns a config object containing in it a key 'menus'
$module_key = ' @-> ';
if (isset($cfg['paths']['modules_path'])) {
    $modules_path = $cfg['paths']['modules_path'];

    // iterates the modules directory
    foreach (new DirectoryIterator($modules_path) as $fileInfo) {
        if ($fileInfo->isDir() && !$fileInfo->isDot()) {

            // include the Module.php config file
            $module_cfg = include $fileInfo->getPathname() . DIRECTORY_SEPARATOR . 'Module.php';
            
            // check if we will load the module
            if(!$module_cfg) continue;

            // get the menus
            $cfg_menus[$module_cfg['name']] = $module_cfg['menus'];

            // apply to each menu leaf an replace, so we can now below when the menu is from a module and when the menu is not
            array_walk_recursive($cfg_menus[$module_cfg['name']], function(&$v) use($module_key, $fileInfo) {

                        // the menu link of a module changes to: module_name . module_key . menu_id
                        $v = $fileInfo->getFilename() . $module_key . $v;
                    });
        }
    }
}

// encode the menus, so we can work with them in the javascript code below
$menus = json_encode($cfg_menus);

// plugins
$plugins = str_replace(array('"%', '%"'), '', json_encode(array_map(function($v) {
                            return $v->result;
                        }, HookManager::fire($this, 'toolbar'))));
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= ($title = $cfg['app']['name'] . ' - v' . $cfg['app']['version']); ?></title>
        <?= m::scripts(); ?>
        <?= $e_head; ?>
    </head>
    <body>
        <script>
            // creates a S global var (the only one!) that contains some useful functions
            var S = (function() {
                
                // show and hide private methods
                var s = function () {
                    var cp = Ext.WindowManager.getActive();
                    cp ? cp.setLoading(true) : Ext.getBody().mask('Loading...');
                }, h = function () {
                    var cp = Ext.WindowManager.getActive();
                    cp ? cp.setLoading(false) : Ext.getBody().unmask();
                };
                
                // the return object
                return {
                    // mask and unmask functions
                    h: h,
                    s: s,
                    /**
                     * 
                     * Load a page
                     * 
                     * @param url string The URL where the page will be loaded (using JsonP)
                     * @param params object Parameters to be send via GET
                     * @param fn_s function A function to be executed after request succeed
                     * @param fn_f function A function to be executed after request failure
                     * 
                     * @return void
                     * 
                     * @author Vitor de Souza <vitor_souza@outlook.com>
                     * @date 25/07/2013 | 09/08/2013
                     */
                    load: function(url, params, fn_s, fn_f) {
                        s();
                        Ext.data.JsonP.request({
                            url: url,
                            params: params,
							timeout: 9999999,
                            success: Ext.Function.createSequence(Ext.getCmp('s-win') ? this.success.win : this.success.normal, fn_s || Ext.emptyFn),
                            failure: Ext.Function.createSequence(this.failure, fn_f || Ext.emptyFn)
                        });
                    },
                    failure: function() {
                        h();
                        Ext.Msg.alert('App', 'App failed!');
                        console.log('FAILED');
                    },
                    success: {
                        /**
                         * 
                         * Executed when request is successful and we ARE NOT using S SystemWindow
                         * 
                         * @return void
                         * 
                         * @author Vitor de Souza <vitor_souza@outlook.com>
                         * @date 31/07/2013
                         */
                        normal: function(cfg) {
                            h();
                            // check for messages
                            if('success' in cfg && cfg.msg) {
                                Ext.Msg.alert('App', cfg.msg);
                                return;
                            }
                            
                            // stop if we don't want to load a widget
                            if(!('xtype' in cfg)) return;
                                    
                            // create the widget
                            var widget = Ext.widget(cfg.xtype, cfg);
                                        
                            // if the widget is not rendered yet, we render it inside a wrapper panel
                            if(widget.xtype !== 'window' && !widget.renderTo) {
                                            
                                // widget title
                                var title = widget.title;
                                delete widget.title;
                                            
                                // creates a wrapper
                                Ext.create('Ext.window.Window', {
                                    id: 's-wrapper',
                                    title: title,
                                    bodyStyle: 'padding: 5px',
                                    layout: 'fit',
                                    maximized: true,
                                    items: widget,
                                    autoShow: true
                                });
                            }
                        },
                        /**
                         * 
                         * Executed when request is successful and we ARE using S SystemWindow
                         * 
                         * @return void
                         * 
                         * @author Vitor de Souza <vitor_souza@outlook.com>
                         * @date 31/07/2013
                         */
                        win: function(cfg) {
                            h();
                            // check for messages
                            if('success' in cfg && cfg.msg) {
                                Ext.Msg.alert('App', cfg.msg);
                                return;
                            }
                            
                            // stop if we don't want to load a widget
                            if(!('xtype' in cfg)) return;
                                    
                            // create the widget
                            var widget = Ext.widget(cfg.xtype, cfg),
                            win = Ext.getCmp('s-win');
                            
                            // remove and show
                            if(!cfg.autoShow) {
                                win.removeAll();
                                win.add(widget);
                            }
                        }
                    }
                }
            })();
            
            // create the main component
            Ext.onReady(function() {
                
                // body unselectable
                Ext.getBody().el.unselectable();
                
                // create the menus
                var menus = (function buildMenus(m) {
                    var menu = [];
                    for(var i in m) m.hasOwnProperty(i) && menu.push({
                        text: i,
                        padding: '5px 0 5px 0',
                        menu: Ext.isObject(m[i]) ? {
                            items: buildMenus(m[i])
                        } : null,
                        handler: Ext.isObject(m[i]) ? null : (function(id) {
                            var p = id;
                            return function() {
                                id = p;
                                // modules treatment
                                var module_key = '<?= $module_key; ?>', 
                                module_key_len = module_key.length, 
                                index = id.indexOf(module_key), 
                                is_module = index >= 0, 
                                module_name = '';
                                    
                                if(is_module) {
                                    module_name = id.substring(0, index);
                                    id = id.substring(index + module_key_len);
                                }
                                
                                // make the request
                                S.load('<?= $base_path; ?>/systems/' + id, {
                                        
                                    // if the menu is from a module
                                    module: is_module,
                                        
                                    // module name
                                    module_name: module_name
                                });
                            }
                        })(m[i])
                    });
                    return menu;
                })(<?= $menus; ?>);
                
                // plugins
                var plugins = <?= $plugins; ?>;
                var toRight = ['->'];
                for(var i in plugins)
                    if('flex' in plugins[i]) {
                        toRight = [];
                        break;
                    }
                
                // creates the Toolbar
                var tbar = Ext.create('Ext.Toolbar', {
                    layoutConfig: {align:'stretch'},
                    layout: {
                        overflowHandler: 'Menu'
                    },
                    items: Ext.Array.merge(menus, toRight, plugins, [
                        '-',
                        {xtype: 'tbspacer', width: 10},
                        // Logout button
                        {
                            text: '<?= strtoupper(S\App::user()->login); ?>',
                            menu: {
                                items: [{
                                        text: '<?= App::t('LOGOUT'); ?>',
                                        padding: '5px 0 5px 0',
                                        handler: function() {document.location.href += 'logout';}
                                    }]
                            }
                        }
                    ]),
                    renderTo: Ext.getBody()
                });
                
                // refresh the layout of the TBAR on window resize
                Ext.EventManager.onWindowResize(function(){
                    tbar.doLayout();
                });
            });
        </script>
    </body>
</html>