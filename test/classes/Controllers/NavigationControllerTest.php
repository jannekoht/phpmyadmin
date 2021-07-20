<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\Tests\AbstractTestCase;

use function sprintf;

class NavigationControllerTest extends AbstractTestCase
{
    public function testIndex(): void
    {
        global $containerBuilder;

        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        parent::loadDefaultConfig();
        parent::setLanguage();

        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'air-balloon_burner_dev2';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';
        parent::loadResponseIntoContainerBuilder();

        // This example path data has nothing to do with the actual test
        // root.air-balloon_burner_dev2
        $_POST['n0_aPath'] = 'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=';
        // root.air-balloon.burner_dev2
        $_POST['n0_vPath'] = 'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=';

        $_GET['ajax_request'] = true;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SELECT CURRENT_USER();',
            [['pma_test@localhost']]
        );
        $this->dummyDbi->addResult(
            'SHOW GRANTS',
            []
        );
        $this->dummyDbi->addResult(
            'SELECT (COUNT(DB_first_level) DIV 100) * 100 from ('
            . ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) DB_first_level '
            . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE `SCHEMA_NAME` < \'air-balloon_burner_dev2\' ) t',
            []
        );
        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                . 'ORDER BY SCHEMA_NAME ASC',
            [
                ['air-balloon_burner_dev2'],
            ],
            ['SCHEMA_NAME']
        );
        $sqlCount = 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) '
        . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t';
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]]
        );
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` NOT IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT @@lower_case_table_names',
            []
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` =\'air-balloon_burner_dev2\' AND `ROUTINE_TYPE`=\'FUNCTION\'',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE `ROUTINE_SCHEMA` =\'air-balloon_burner_dev2\''
            . 'AND `ROUTINE_TYPE`=\'PROCEDURE\'',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE `EVENT_SCHEMA` =\'air-balloon_burner_dev2\'',
            [[0]]
        );

        /** @var NavigationController $navigationController */
        $navigationController = $containerBuilder->get(NavigationController::class);
        $this->setResponseIsAjax();
        $navigationController->index();
        $this->assertResponseWasSuccessfull();

        $responseMessage = $this->getResponseJsonResult()['message'];

        $this->assertStringContainsString(
            '<div id=\'pma_navigation_tree_content\'>',
            $responseMessage
        );

        // root.air-balloon_burner_dev2
        // cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=
        $this->assertStringContainsString(
            '<ul>' . "\n"
            . '    <li class="first database">'
                . '<div class=\'block\'><i class=\'first\'></i><b></b>'
                    . '<a class="expander" href=\'#\'>'
                        . '<span class="hide paths_nav" data-apath="cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI="'
                        . ' data-vpath="cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI="'
                        . ' data-pos="0"></span><img src="themes/dot.gif"'
                        . ' title="Expand/Collapse" alt="Expand/Collapse" class="icon ic_b_plus"></a>'
                . '</div>'
                . '<div class="block second">'
                    . '<a href=\'index.php?route=/database/operations&lang=en&amp;server=1&amp;'
                        . 'db=air-balloon_burner_dev2&amp;\'>'
                        . '<img src="themes/dot.gif" title="Database operations"'
                        . ' alt="Database operations" class="icon ic_s_db">'
                    . '</a>'
                . '</div>'
                . '<a class=\'hover_show_full\''
                    . ' href=\'index.php?route=/database/structure&lang=en&server=1&amp;db=air-balloon_burner_dev2\''
                    . ' title=\'Structure\'>air-balloon_burner_dev2</a><div class="clearfloat"></div>' . "\n"
            . '  </ul>' . "\n",
            $responseMessage
        );
        $this->assertAllQueriesConsumed();
    }

    public function testIndexWithPosAndValue(): void
    {
        global $containerBuilder;

        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        parent::loadDefaultConfig();
        parent::setLanguage();

        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'air-balloon_burner_dev2';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';
        parent::loadResponseIntoContainerBuilder();

        // root.air-balloon_burner_dev2
        $_POST['n0_aPath'] = 'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=';
        // root.air-balloon.burner_dev2
        $_POST['n0_vPath'] = 'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=';

        $_POST['n0_pos2_name'] = 'tables';
        $_POST['n0_pos2_value'] = 0;

        $_GET['ajax_request'] = true;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SELECT CURRENT_USER();',
            [['pma_test@localhost']]
        );
        $this->dummyDbi->addResult(
            'SHOW GRANTS',
            []
        );
        $this->dummyDbi->addResult(
            'SELECT (COUNT(DB_first_level) DIV 100) * 100 from ('
            . ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) DB_first_level '
            . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE `SCHEMA_NAME` < \'air-balloon_burner_dev2\' ) t',
            []
        );
        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                . 'ORDER BY SCHEMA_NAME ASC',
            [
                ['air-balloon_burner_dev'],
                ['air-balloon_burner_dev2'],
                ['air-balloon_dev'],
            ],
            ['SCHEMA_NAME']
        );

        $sqlCount = 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) '
        . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t';
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]]
        );
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` NOT IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT @@lower_case_table_names',
            []
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` =\'air-balloon_burner_dev2\' AND `ROUTINE_TYPE`=\'FUNCTION\'',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE `EVENT_SCHEMA` =\'air-balloon_burner_dev2\'',
            [[0]]
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` =\'air-balloon_burner_dev2\'AND `ROUTINE_TYPE`=\'PROCEDURE\'',
            [[0]]
        );

        /** @var NavigationController $navigationController */
        $navigationController = $containerBuilder->get(NavigationController::class);
        $this->setResponseIsAjax();
        $navigationController->index();
        $this->assertResponseWasSuccessfull();

        $responseMessage = $this->getResponseJsonResult()['message'];

        $this->assertStringContainsString(
            '<div id=\'pma_navigation_tree_content\'>',
            $responseMessage
        );

        $dbTemplate =
            '<li class="database database">'
            . '<div class=\'block\'><i></i><b></b><a class="expander" href=\'#\'>'
                . '<span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>'
                . '<img src="themes/dot.gif" title="Expand/Collapse" alt="Expand/Collapse" class="icon ic_b_plus">'
            . '</a></div>'
            . '<div class="block second">'
            . '<a href=\'index.php?route=/database/operations&lang=en&amp;server=1&amp;db=%s&amp;\'>'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a></div><a class=\'hover_show_full\''
            . ' href=\'index.php?route=/database/structure&lang=en&server=1&amp;db=%s\' title=\'Structure\'>%s</a>'
            . '<div class="clearfloat"></div>'
            . '</li>';

        $dbTemplateLast =
            '<li class="database last database">'
            . '<div class=\'block\'><i></i><a class="expander" href=\'#\'>'
                . '<span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>'
                . '<img src="themes/dot.gif" title="Expand/Collapse" alt="Expand/Collapse" class="icon ic_b_plus">'
            . '</a></div>'
            . '<div class="block second">'
            . '<a href=\'index.php?route=/database/operations&lang=en&amp;server=1&amp;db=%s&amp;\'>'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a></div><a class=\'hover_show_full\''
            . ' href=\'index.php?route=/database/structure&lang=en&server=1&amp;db=%s\' title=\'Structure\'>%s</a>'
            . '<div class="clearfloat"></div>'
            . '</li>';

        $dbTemplateExpanded =
            '<li class="database database">'
            . '<div class=\'block\'><i></i><b></b><a class="expander loaded" href=\'#\'>'
                . '<span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>'
                . '<img src="themes/dot.gif" title="" alt="" class="icon ic_b_minus">'
            . '</a></div>'
            . '<div class="block second">'
            . '<a href=\'index.php?route=/database/operations&lang=en&amp;server=1&amp;db=%s&amp;\'>'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a></div><a class=\'hover_show_full\''
            . ' href=\'index.php?route=/database/structure&lang=en&server=1&amp;db=%s\' title=\'Structure\'>%s</a>'
            . '<div class="clearfloat"></div>'
            . '</li>';

        // root.air-balloon_burner_dev2
        // cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=
        $this->assertStringContainsString(
            '<div id=\'pma_navigation_tree_content\'>' . "\n"
            . '  <ul>' . "\n"
            . '    <li class="first navGroup">'
                . '<div class=\'block\'><i class=\'first\'></i><b></b>'
                    . '<a class="expander loaded container" href=\'#\'>'
                        . '<span class="hide paths_nav" data-apath="cm9vdA=="'
                        . ' data-vpath="cm9vdA==.YWlyLWJhbGxvb24="'
                        . ' data-pos="0"></span><img src="themes/dot.gif"'
                        . ' title="" alt="" class="icon ic_b_minus"></a>'
                . '</div>'
                . '<i>'
                . '<div class="block second"><u><img src="themes/dot.gif" title="Groups" alt="Groups"'
                . ' class="icon ic_b_group"></u></div>&nbsp;air-balloon'
                . '</i>'
                . '<div class="clearfloat"></div>'
                . '<div class=\'list_container\'>'
                    . '<ul>'
                    . sprintf(
                        $dbTemplate,
                        'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2Rldg==',
                        'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2Rldg==',
                        'air-balloon_burner_dev',
                        'air-balloon_burner_dev',
                        'air-balloon_burner_dev'
                    )
                    . sprintf(
                        $dbTemplateExpanded,
                        'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=',
                        'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=',
                        'air-balloon_burner_dev2',
                        'air-balloon_burner_dev2',
                        'air-balloon_burner_dev2'
                    )
                    . sprintf(
                        $dbTemplateLast,
                        'cm9vdA==.YWlyLWJhbGxvb25fZGV2',
                        'cm9vdA==.YWlyLWJhbGxvb24=.ZGV2',
                        'air-balloon_dev',
                        'air-balloon_dev',
                        'air-balloon_dev'
                    )
                    . '</ul>'
                . '</div>' . "\n"
            . '  </ul>' . "\n"
            . '</div>' . "\n",
            $responseMessage
        );
        $this->assertAllQueriesConsumed();
    }
}
