<?php
/**
 * Horde_Argv test suite
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

if (!function_exists('_')) {
    function _($t) {
        return $t;
    }
}

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Argv_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';
set_include_path(dirname(__FILE__) . '/../../' . PATH_SEPARATOR . get_include_path());

/**
 * @package    Horde_Argv
 * @subpackage UnitTests
 */
class Horde_Argv_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Argv_AllTests::main') {
    Horde_Argv_AllTests::main('Horde_Argv', __FILE__);
}
