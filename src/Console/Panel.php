<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Console\Util\Format;
use Mini\Console\Util\StrBuffer;

class Panel
{
    public const ALIGN_LEFT = 'left';
    public const ALIGN_CENTER = 'center';
    public const ALIGN_RIGHT = 'right';
    /** @var string */
    public string $title = '';

    /** @var string */
    public string $titleBorder = '-';

    /** @var string */
    public string $titleStyle = 'bold';

    /** @var string */
    public string $titleAlign = self::ALIGN_LEFT;

    /** @var string|array */
    public string|array $data;

    /** @var string */
    public string $bodyAlign = self::ALIGN_LEFT;

    /** @var string */
    public string $footerBorder = '-';

    /** @var string */
    public string $footer = '';

    /** @var bool */
    public bool $ucFirst = true;

    /** @var int */
    public int $width = 0;

    /** @var bool */
    public bool $showBorder = true;

    /** @var string */
    public string $borderYChar = '-';

    /** @var string */
    public string $borderXChar = '|';

    /**
     * @var string Template for the panel. don't contains border
     */
    public string $template = <<<EOF
{%title%}
{%title-border%}
{%content%}
{%footer-border%}
{%footer%}
EOF;

    /**
     * Show information data panel
     *
     * @param mixed $data
     * @param string $title
     * @param array $opts
     * @return int
     */
    public static function show(mixed $data, string $title = 'Information Panel', array $opts = []): int
    {
        if (!$data) {
            Cli::write('<info>No data to display!</info>');
            return -2;
        }

        $opts = array_merge([
            'borderChar' => '*',
            'ucFirst' => true,
        ], $opts);

        $data = is_array($data) ? array_filter($data) : [trim($data)];
        $title = trim($title);

        $panelData = []; // [ 'label' => 'value' ]
        $borderChar = $opts['borderChar'];

        $labelMaxWidth = 0; // if label exists, label max width
        $valueMaxWidth = 0; // value max width

        foreach ($data as $label => $value) {
            // label exists
            if (!is_numeric($label)) {
                $width = mb_strlen((string)$label, 'UTF-8');
                $labelMaxWidth = max($width, $labelMaxWidth);
            }
            // translate array to string
            if (is_array($value)) {
                $temp = '';
                foreach ($value as $key => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }
                    $temp .= (!is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }
                $value = rtrim($temp, ' ,');
            } elseif (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = trim((string)$value);
            }
            // get value width
            /** @var string $value */
            $value = trim($value);
            $width = mb_strlen(strip_tags($value), 'UTF-8'); // must clear style tag

            $valueMaxWidth = max($width, $valueMaxWidth);
            $panelData[$label] = $value;
        }
        $border = null;
        $panelWidth = $labelMaxWidth + $valueMaxWidth;
        Cli::startBuffer();
        // output title
        if ($title) {
            $title = ucwords($title);

            $titleLength = mb_strlen($title, 'UTF-8');
            $panelWidth = max($panelWidth, $titleLength);
            $indentSpace = str_pad(' ', (int)(ceil($panelWidth / 2) - ceil($titleLength / 2) + 4), ' ');
            Cli::write("{$indentSpace}<bold>{$title}</bold>");
        }
        // output panel top border
        if ($borderChar) {
            $border = str_pad($borderChar, (int)($panelWidth + 9), $borderChar);
            Cli::write($border);
        }
        // output panel body
        $panelStr = Format::spliceKeyValue($panelData, [
            'leftChar' => "$borderChar ",
            'sepChar' => ' | ',
            'keyMaxWidth' => $labelMaxWidth,
            'ucFirst' => $opts['ucFirst'],
        ]);
        // already exists "\n"
        Cli::write($panelStr, false);

        // output panel bottom border
        if ($border) {
            Cli::write("$border\n");
        }

        Cli::flushBuffer();
        unset($panelData);
        return 0;
    }

    /**
     * @return string
     */
    public function format(): string
    {
        if (!$this->data) {
            // self::write('<info>No data to display!</info>');
            return '';
        }

        $buffer = new StrBuffer();
        $data = is_array($this->data) ? array_filter($this->data) : [trim($this->data)];
        $title = trim($this->title);

        $panelData = []; // [ 'label' => 'value' ]
        $borderChar = $this->borderXChar;

        $labelMaxWidth = 0; //
        $valueMaxWidth = 0; //

        foreach ($data as $label => $value) {
            // label exists
            if (!is_numeric($label)) {
                $width = mb_strlen((string)$label, 'UTF-8');

                $labelMaxWidth = max($width, $labelMaxWidth);
            }

            // translate array to string
            if (is_array($value)) {
                $temp = '';

                foreach ($value as $key => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } elseif (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = trim((string)$value);
            }

            // get value width
            /** @var string $value */
            $value = trim($value);
            $width = mb_strlen(strip_tags($value), 'UTF-8'); // must clear style tag

            $valueMaxWidth = $width > $valueMaxWidth ? $width : $valueMaxWidth;
            $panelData[$label] = $value;
        }

        $panelWidth = $labelMaxWidth + $valueMaxWidth;

        // output title
        if ($title) {
            $title = ucwords($title);
            $titleLength = mb_strlen($title, 'UTF-8');
            $panelWidth = max($panelWidth, $titleLength);
            $indentSpace = str_pad(' ', (int)(ceil($panelWidth / 2) - ceil($titleLength / 2) + 4), ' ');
            $buffer->write("  {$indentSpace}<bold>{$title}</bold>\n");
        }

        // output panel top border
        if ($topBorder = $this->titleBorder) {
            $border = str_pad($topBorder, (int)($panelWidth + 9), $topBorder);
            $buffer->write('  ' . $border . PHP_EOL);
        }

        // output panel body
        $panelStr = Format::spliceKeyValue($panelData, [
            'ucFirst' => $this->ucFirst,
            'leftChar' => "  $borderChar ",
            'sepChar' => ' | ',
            'keyMaxWidth' => $labelMaxWidth,
        ]);

        // already exists "\n"
        $buffer->write($panelStr);

        // output panel bottom border
        if ($footBorder = $this->footerBorder) {
            $border = str_pad($footBorder, (int)($panelWidth + 9), $footBorder);
            $buffer->write('  ' . $border . PHP_EOL);
        }

        unset($panelData);
        return $buffer->toString();
    }

    /**
     * @param bool $border
     * @return $this
     */
    public function showBorder(bool $border): self
    {
        $this->showBorder = $border;
        return $this;
    }
}