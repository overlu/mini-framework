<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Watch;

class Filter extends \RecursiveFilterIterator
{
    protected string $watch_ext = 'php,env';
    protected array $exclude_dir = ['vendor', 'runtime', 'public'];

    public function accept()
    {
        if ($this->current()->isDir()) {
            return 0 === strpos($this->current()->getFilename(), ".")
                ? false
                : !in_array($this->current()->getFilename(), $this->exclude_dir, true);
        }
        $list = array_map(static function (string $item): string {
            return "\.$item";
        }, explode(',', $this->watch_ext));
        $list = implode('|', $list);
        return preg_match("/($list)$/", $this->current()->getFilename());
    }
}