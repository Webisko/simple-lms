<?php
/**
 * PHPStorm/IntelliJ Meta file for WordPress
 * Helps IDE understand WordPress functions and constants
 * 
 * @see https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {
    // Override functions
    override(\add_action(0), map([
        '' => 'void',
    ]));
    
    override(\add_filter(0), map([
        '' => 'mixed',
    ]));
    
    override(\get_option(0), map([
        '' => '@',
    ]));
    
    override(\get_post_meta(0), map([
        '' => '@',
    ]));
}
