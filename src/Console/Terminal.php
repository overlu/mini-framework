<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Support\Command;

/**
 * Class Terminal - terminal control by ansiCode
 * 2K 清除本行
 * \x0D = \r = 13 回车，回到行首
 * ESC = \x1B = \033 = 27
 */
class Terminal
{
    public const BEGIN_CHAR = "\033[";

    public const END_CHAR = "\033[0m";

    // Control cursor code name list. more @see [[self::$ctrlCursorCodes]]
    public const CUR_HIDE = 'hide';

    public const CUR_SHOW = 'show';

    public const CUR_SAVE_POSITION = 'savePosition';

    public const CUR_RESTORE_POSITION = 'restorePosition';

    public const CUR_UP = 'up';

    public const CUR_DOWN = 'down';

    public const CUR_FORWARD = 'forward';

    public const CUR_BACKWARD = 'backward';

    public const CUR_NEXT_LINE = 'nextLine';

    public const CUR_PREV_LINE = 'prevLine';

    public const CUR_COORDINATE = 'coordinate';

    // Control screen code name list. more @see [[self::$ctrlScreenCodes]]
    public const CLEAR = 'clear';

    public const CLEAR_BEFORE_CURSOR = 'clearBeforeCursor';

    public const CLEAR_LINE = 'clearLine';

    public const CLEAR_LINE_BEFORE_CURSOR = 'clearLineBeforeCursor';

    public const CLEAR_LINE_AFTER_CURSOR = 'clearLineAfterCursor';

    public const SCROLL_UP = 'scrollUp';

    public const SCROLL_DOWN = 'scrollDown';

    /**
     * current class's instance
     *
     * @var self|null
     */
    private static ?Terminal $instance = null;

    /**
     * Control cursor code list
     *
     * @var array
     */
    private static array $ctrlCursorCodes = [
        // Hides the cursor. Use [show] to bring it back.
        'hide' => '?25l',

        // Will show a cursor again when it has been hidden by [hide]
        'show' => '?25h',

        // Saves the current cursor position, Position can then be restored with [restorePosition].
        // - 保存当前光标位置，然后可以使用[restorePosition]恢复位置
        'savePosition' => 's',

        // Restores the cursor position saved with [savePosition] - 恢复[savePosition]保存的光标位置
        'restorePosition' => 'u',

        // Moves the terminal cursor up
        'up' => '%dA',

        // Moves the terminal cursor down
        'down' => '%B',

        // Moves the terminal cursor forward - 移动终端光标前进多远
        'forward' => '%dC',

        // Moves the terminal cursor backward - 移动终端光标后退多远
        'backward' => '%dD',

        // Moves the terminal cursor to the beginning of the previous line - 移动终端光标到前一行的开始
        'prevLine' => '%dF',

        // Moves the terminal cursor to the beginning of the next line - 移动终端光标到下一行的开始
        'nextLine' => '%dE',

        // Moves the cursor to an absolute position given as column and row
        // $column 1-based column number, 1 is the left edge of the screen.
        //  $row 1-based row number, 1 is the top edge of the screen. if not set, will move cursor only in current line.
        'coordinate' => '%dG|%d;%dH' // only column: '%dG', column and row: '%d;%dH'.
    ];

    /**
     * Control screen code list
     *
     * @var array
     */
    private static array $ctrlScreenCodes = [
        // Clears entire screen content - 清除整个屏幕内容
        'clear' => "H\033[2J", // "\033[2J"

        // Clears text from cursor to the beginning of the screen - 从光标清除文本到屏幕的开头
        'clearBeforeCursor' => '1J',

        // Clears the line - 清除此行
        'clearLine' => '2K',

        // Clears text from cursor position to the beginning of the line - 清除此行从光标位置开始到开始的字符
        'clearLineBeforeCursor' => '1K',

        // Clears text from cursor position to the end of the line - 清除此行从光标位置开始到结束的字符
        'clearLineAfterCursor' => '0K',

        // Scrolls whole page up. e.g "\033[2S" scroll up 2 line. - 上移多少行
        'scrollUp' => '%dS',

        // Scrolls whole page down.e.g "\033[2T" scroll down 2 line. - 下移多少行
        'scrollDown' => '%dT',
    ];

    /**
     * @return Terminal
     */
    public static function instance(): Terminal
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * build ansi code string
     *
     * ```
     * Terminal::build(null, 'u');  // "\033[s" Saves the current cursor position
     * Terminal::build(0);          // "\033[0m" Build end char, Resets any ANSI format
     * ```
     *
     * @param mixed $format
     * @param string $type
     *
     * @return string
     */
    public static function build(mixed $format, string $type = 'm'): string
    {
        $format = null === $format ? '' : implode(';', (array)$format);
        return self::BEGIN_CHAR . implode(';', (array)$format) . $type . self::END_CHAR;
    }

    /**
     * control cursor
     *
     * @param string $typeName
     * @param int $arg1
     * @param null $arg2
     *
     * @return $this
     */
    private function cursor(string $typeName, int $arg1 = 1, $arg2 = null): self
    {
        if (!isset(self::$ctrlCursorCodes[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::$ctrlCursorCodes[$typeName];

        // allow argument
        if (str_contains($code, '%')) {
            // The special code: ` 'coordinate' => '%dG|%d;%dH' `
            if ($typeName === self::CUR_COORDINATE) {
                $codes = explode('|', $code);

                if (null === $arg2) {
                    $code = sprintf($codes[0], $arg1);
                } else {
                    $code = sprintf($codes[1], $arg1, $arg2);
                }
            } else {
                $code = sprintf($code, $arg1);
            }
        }

        Command::message(self::build($code, ''));

        return $this;
    }

    /**
     * control screen
     *
     * @param string $typeName
     * @param null $arg
     *
     * @return $this
     */
    private function screen(string $typeName, $arg = null): self
    {
        if (!isset(self::$ctrlScreenCodes[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::$ctrlScreenCodes[$typeName];

        // allow argument
        if (str_contains($code, '%')) {
            $code = sprintf($code, $arg);
        }

        Command::message(self::build($code, ''));

        return $this;
    }

    /**
     * ============================= screen ==============================
     */

    /**
     * 清除整个屏幕内容
     * @return $this
     */
    public function clear(): self
    {
        return $this->screen(self::CLEAR);
    }

    /**
     * 从光标清除文本到屏幕的开头
     * @return $this
     */
    public function clearBeforeCursor(): self
    {
        return $this->screen(self::CLEAR_BEFORE_CURSOR);
    }

    /**
     * 清除此行
     * @return $this
     */
    public function clearLine(): self
    {
        return $this->screen(self::CLEAR_LINE);
    }

    /**
     * 清除此行从光标位置开始到开始的字符
     * @return $this
     */
    public function clearLineBeforeCursor(): self
    {
        return $this->screen(self::CLEAR_LINE_BEFORE_CURSOR);
    }

    /**
     * 清除此行从光标位置开始到结束的字符
     * @return $this
     */
    public function clearLineAfterCursor(): self
    {
        return $this->screen(self::CLEAR_LINE_AFTER_CURSOR);
    }

    /**
     * 上移$number行
     * @param int $number
     * @return $this
     */
    public function scrollUp(int $number = 1): self
    {
        return $this->screen(self::SCROLL_UP, $number);
    }

    /**
     * 下移$number行
     * @param int $number
     * @return $this
     */
    public function scrollDown(int $number = 1): self
    {
        return $this->screen(self::SCROLL_DOWN, $number);
    }

    /**
     * ============================= cursor ==============================
     */

    /**
     * Hides the cursor
     * @return $this
     */
    public function cursorHide(): self
    {
        return $this->cursor(self::CUR_HIDE);
    }

    /**
     * Will show a cursor again when it has been hidden
     * @return $this
     */
    public function cursorShow(): self
    {
        return $this->cursor(self::CUR_SHOW);
    }

    /**
     * 保存当前光标位置，然后可以使用[restorePosition]恢复位置
     * @return $this
     */
    public function saveCursorPosition(): self
    {
        return $this->cursor(self::CUR_SAVE_POSITION);
    }

    /**
     * 恢复[savePosition]保存的光标位置
     * @return $this
     */
    public function restoreCursorPosition(): self
    {
        return $this->cursor(self::CUR_RESTORE_POSITION);
    }

    /**
     * 终端光标上移
     * @param int $number
     * @return $this
     */
    public function cursorUp(int $number = 1): self
    {
        return $this->cursor(self::CUR_UP, $number);
    }

    /**
     * 终端光标下移
     * @param int $number
     * @return $this
     */
    public function cursorDown(int $number = 1): self
    {
        return $this->cursor(self::CUR_DOWN, $number);
    }

    /**
     * 终端光标前移
     * @param int $number
     * @return $this
     */
    public function cursorForward(int $number = 1): self
    {
        return $this->cursor(self::CUR_FORWARD, $number);
    }

    /**
     * 终端光标后移
     * @param int $number
     * @return $this
     */
    public function cursorBackward(int $number = 1): self
    {
        return $this->cursor(self::CUR_BACKWARD, $number);
    }

    /**
     * 移动终端光标到前$number行的开始
     * @param int $number
     * @return $this
     */
    public function cursorPrevLine(int $number = 1): self
    {
        return $this->cursor(self::CUR_PREV_LINE, $number);
    }

    /**
     * 移动终端光标到后$number行的开始
     * @param int $number
     * @return $this
     */
    public function cursorNextLine(int $number = 1): self
    {
        return $this->cursor(self::CUR_NEXT_LINE, $number);
    }

    /**
     * 移动终端光标到指定位置
     * @param int $column
     * @param int|null $row
     * @return $this
     */
    public function cursorPosition(int $column = 1, ?int $row = null): self
    {
        return $this->cursor(self::CUR_COORDINATE, $column, $row);
    }

    public function sound(): void
    {
        print "\007";
    }
}
