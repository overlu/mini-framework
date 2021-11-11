<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Console\Util\StrBuffer;

class Table
{
    public const FINISHED = -1;
    public const CHAR_SPACE = ' ';
    public const CHAR_HYPHEN = '-';
    public const CHAR_UNDERLINE = '_';
    public const CHAR_VERTICAL = '|';
    public const CHAR_EQUAL = '=';
    public const CHAR_STAR = '*';
    public const POS_LEFT = 'l';
    public const POS_MIDDLE = 'm';
    public const POS_RIGHT = 'r';
    /** @var array */
    public array $data = [];

    /** @var array */
    public array $columns = [];

    /** @var string|array */
    public $body;

    /** @var string */
    public string $title = '';

    /** @var string */
    public string $titleBorder = '-';

    /** @var string */
    public string $titleStyle = '-';

    /** @var string */
    public string $titleAlign = 'left';

    /**
     * Tabular data display
     *
     * @param array $data
     * @param string $title
     * @param array $opts
     *
     * @return int
     * @example
     *
     * ```php
     * // like from database query's data.
     * $data = [
     *  [ col1 => value1, col2 => value2, col3 => value3, ... ], // first row
     *  [ col1 => value4, col2 => value5, col3 => value6, ... ], // second row
     *  ... ...
     * ];
     * Show::table($data, 'a table');
     *
     * // use custom head
     * $data = [
     *  [ value1, value2, value3, ... ], // first row
     *  [ value4, value5, value6, ... ], // second row
     *  ... ...
     * ];
     * $opts = [
     *   'showBorder' => true,
     *   'columns' => [col1, col2, col3, ...]
     * ];
     * Show::table($data, 'a table', $opts);
     * ```
     */
    public static function show(array $data, string $title = 'Data Table', array $opts = []): int
    {
        if (!$data) {
            return -2;
        }

        $buf = new StrBuffer();
        $opts = array_merge([
            'showBorder' => true,
            'leftIndent' => '  ',
            'titlePos' => self::POS_LEFT,
            'titleStyle' => 'bold',
            'headStyle' => 'comment',
            'headBorderChar' => self::CHAR_EQUAL,   // default is '='
            'bodyStyle' => '',
            'rowBorderChar' => self::CHAR_HYPHEN,   // default is '-'
            'colBorderChar' => self::CHAR_VERTICAL, // default is '|'
            'columns' => [],                  // custom column names
        ], $opts);

        $hasHead = false;
        $rowIndex = 0;
        $head = [];
        $tableHead = $opts['columns'];
        $leftIndent = $opts['leftIndent'];
        $showBorder = $opts['showBorder'];
        $rowBorderChar = $opts['rowBorderChar'];
        $colBorderChar = $opts['colBorderChar'];

        $info = [
            'rowCount' => count($data),
            'columnCount' => 0,     // how many column in the table.
            'columnMaxWidth' => [], // table column max width
            'tableWidth' => 0,      // table width. equals to all max column width's sum.
        ];

        // parse table data
        foreach ($data as $row) {
            // collection all field name
            if ($rowIndex === 0) {
                $head = $tableHead ?: array_keys($row);
                //
                $info['columnCount'] = count($row);

                foreach ($head as $index => $name) {
                    if (is_string($name)) {// maybe no column name.
                        $hasHead = true;
                    }

                    $info['columnMaxWidth'][$index] = mb_strlen((string)$name, 'UTF-8');
                }
            }

            $colIndex = 0;

            foreach ((array)$row as $value) {
                $value = Color::clearColor($value);
                // collection column max width
                if (isset($info['columnMaxWidth'][$colIndex])) {
                    $colWidth = mb_strlen($value, 'UTF-8');

                    // If current column width gt old column width. override old width.
                    if ($colWidth > $info['columnMaxWidth'][$colIndex]) {
                        $info['columnMaxWidth'][$colIndex] = $colWidth;
                    }
                } else {
                    $info['columnMaxWidth'][$colIndex] = mb_strlen($value, 'UTF-8');
                }

                $colIndex++;
            }

            $rowIndex++;
        }

        $tableWidth = $info['tableWidth'] = array_sum($info['columnMaxWidth']);
        $columnCount = $info['columnCount'];

        // output title
        if ($title) {
            $tStyle = $opts['titleStyle'] ?: 'bold';
            $title = ucwords(trim($title));
            $titleLength = mb_strlen($title, 'UTF-8');
            $indentSpace = str_pad(' ', (int)(ceil($tableWidth / 2) - ceil($titleLength / 2) + ($columnCount * 2)), ' ');
            $buf->write("  {$indentSpace}<$tStyle>{$title}</$tStyle>\n");
        }

        $border = $leftIndent . str_pad($rowBorderChar, (int)($tableWidth + ($columnCount * 3) + 2), $rowBorderChar);

        // output table top border
        if ($showBorder) {
            $buf->write($border . "\n");
        } else {
            $colBorderChar = '';// clear column border char
        }

        // output table head
        if ($hasHead) {
            $headStr = "{$leftIndent}{$colBorderChar} ";

            foreach ($head as $index => $name) {
                $colMaxWidth = $info['columnMaxWidth'][$index];
                // format
                $name = str_pad($name, (int)$colMaxWidth, ' ');
                $name = ColorTag::wrap($name, $opts['headStyle']);
                $headStr .= " {$name} {$colBorderChar}";
            }

            $buf->write($headStr . "\n");

            // head border: split head and body
            if ($headBorderChar = $opts['headBorderChar']) {
                $headBorder = $leftIndent . str_pad(
                        $headBorderChar,
                        (int)($tableWidth + ($columnCount * 3) + 2),
                        $headBorderChar
                    );
                $buf->write($headBorder . "\n");
            }
        }

        $rowIndex = 0;

        // output table info
        foreach ($data as $row) {
            $colIndex = 0;
            $rowStr = "  $colBorderChar ";

            foreach ((array)$row as $value) {
                $colMaxWidth = $info['columnMaxWidth'][$colIndex];
                // format
                $temp_length = (int)$colMaxWidth - mb_strlen(Color::clearColor($value), 'UTF-8');
                $value = $temp_length > 0 ? $value . str_repeat(' ', $temp_length) : str_pad($value, (int)$colMaxWidth, ' ');
                $value = ColorTag::wrap($value, $opts['bodyStyle']);
                $rowStr .= " {$value} {$colBorderChar}";
                $colIndex++;
            }

            $buf->write($rowStr . "\n");
            $rowIndex++;
        }

        // output table bottom border
        if ($showBorder) {
            $buf->write($border . "\n");
        }

        return Cli::write($buf);
    }
}