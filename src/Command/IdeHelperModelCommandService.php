<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Exception;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Facades\DB;
use Mini\Facades\File;
use ReflectionClass;
use ReflectionException;
use Swoole\Process;
use Carbon\Carbon;

class IdeHelperModelCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return void
     * @throws Exception
     */
    public function handle(?Process $process): void
    {
        $model = $this->argument('model', '');
        $path = base_path('app/Models/' . $model);
        $files = File::isDirectory($path) ? File::allFilesToArray($path) : [$model];
        foreach ($files as $file) {
            $this->parse($file);
        }
    }

    /**
     * @param $filePath
     * @return void
     * @throws ReflectionException
     */
    public function parse($filePath): void
    {
        $fileContent = File::get($filePath);
        if (preg_match('/namespace (.*?);/', $fileContent, $spaceMatch) && preg_match('/class (.*?) extends .*/', $fileContent, $classMatch)) {
            $class = new ReflectionClass($spaceMatch[1] . '\\' . $classMatch[1]);
            $newComment = $this->parseDocComment($this->getNewDocComment($class));
            $docComment = $class->getDocComment();
            if ($docComment) {
                $newFileContent = str_replace($docComment, $newComment, $fileContent);
            } else {
                $newFileContent = str_replace($classMatch[0], $newComment . PHP_EOL . $classMatch[0], $fileContent);
            }
            File::put($filePath, $newFileContent);
        }
    }

    /**
     * @param string $comment
     * @return string
     */
    private function parseDocComment(string $comment): string
    {
        if (preg_match('/\/\*\*(.*)/', $comment)) {
            return $comment;
        }
        $arr = explode("\n", rtrim($comment, "\n"));
        $newComment = "/**\n";
        foreach ($arr as $str) {
            $newComment .= " * " . $str . "\n";
        }
        return $newComment . " */";
    }

    /**
     * @param ReflectionClass $class
     * @return string
     * @throws ReflectionException
     */
    private function getNewDocComment(ReflectionClass $class): string
    {
        /**
         * @var $model Model
         */
        $model = $class->newInstance();
        $tableName = $model->getTable();
        $connectionName = $model->getConnectionName();
        $databaseName = $model->getConnection()->getDatabaseName();
        $columns = DB::connection($connectionName)
            ->select("
SELECT COLUMN_NAME, DATA_TYPE , COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = '{$tableName}'
AND table_schema = '{$databaseName}' ORDER BY ORDINAL_POSITION ASC");
        $comment = "";
        foreach ($columns as $column) {
            $type = 'string';
            if (in_array($column->DATA_TYPE, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'])) {
                $type = 'int';
            } elseif (in_array($column->DATA_TYPE, ['float', 'double', 'decimal'])) {
                $type = 'float';
            } elseif ($column->DATA_TYPE === 'json') {
                $type = 'array';
            }
            if (in_array($column->COLUMN_NAME, array_merge($model->getDates(), ['deleted_at']), true)) {
                $type = '\\' . Carbon::class;
            }
            $comment .= sprintf("@property %s \$%s %s\n", $type, $column->COLUMN_NAME, $column->COLUMN_COMMENT);
        }
        return $comment;
    }

    public function getCommand(): string
    {
        return 'ide:model';
    }

    public function getCommandDescription(): string
    {
        return 'Generate property for ide to understand models .
                   <blue>{--model : The model name .}</blue> ';
    }
}