<?php

namespace App\Database\Schema;

use App\Support\ColumnDefinition;
use App\Support\Traits\AndroidMacroable;
use Closure;
use App\Database\Schema\Grammars\Grammar;

class Blueprint
{
    use AndroidMacroable;
    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected $table;
    /**
     * The prefix of the table.
     *
     * @var string
     */
    protected $prefix;
    /**
     * The columns that should be added to the table.
     *
     * @var ColumnDefinition[]
     */
    protected $columns = [];
    /**
     * The commands that should be run for the table.
     *
     * @var \App\Support\ColumnDefinition[]
     */
    protected $commands = [];
    /**
     * The storage engine that should be used for the table.
     *
     * @var string
     */
    public $engine;
    /**
     * The default character set that should be used for the table.
     */
    public $charset;
    /**
     * The collation that should be used for the table.
     */
    public $collation;
    /**
     * Whether to make the table temporary.
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Create a new schema blueprint.
     *
     * @param  string $table
     * @param  \Closure|null $callback
     * @param  string $prefix
     * @return void
     */
    public function __construct($table, Closure $callback = null, $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;
        if (!is_null($callback)) {
            $callback($this);
        }
    }


    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param  \App\Database\Schema\Grammars\Grammar $grammar
     * @return array
     */
    public function toSql(Grammar $grammar)
    {
        $this->addImpliedCommands($grammar);
        $statements = [];
        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);
            if (method_exists($grammar, $method)) {
                if (!is_null($sql = $grammar->$method($this, $command))) {
                    $statements = array_merge($statements, (array)$sql);
                }
            }
        }
        return $statements;
    }

    /**
     * Get all of the commands matching the given names.
     *
     * @param  array $names
     * @return \App\Support\Collection
     */
    protected function commandsNamed(array $names)
    {
        return collect($this->commands)->filter(function ($command) use ($names) {
            return in_array($command->name, $names);
        });
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * @param  \App\Database\Schema\Grammars\Grammar $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar)
    {
        if (count($this->getAddedColumns()) > 0 && !$this->creating()) {
            array_unshift($this->commands, $this->createCommand('add'));
        }
        if (count($this->getChangedColumns()) > 0 && !$this->creating()) {
            array_unshift($this->commands, $this->createCommand('change'));
        }
        $this->addFluentIndexes();
        $this->addFluentCommands($grammar);
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * @return void
     */
    protected function addFluentIndexes()
    {
        foreach ($this->columns as $column) {
            foreach (['primary', 'unique', 'index', 'spatialIndex'] as $index) {
                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);
                    continue 2;
                }
                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
                elseif (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});
                    continue 2;
                }
            }
        }
    }

    /**
     * Add the fluent commands specified on any columns.
     *
     * @param  \App\Database\Schema\Grammars\Grammar $grammar
     * @return void
     */
    public function addFluentCommands(Grammar $grammar)
    {
        foreach ($this->columns as $column) {
            foreach ($grammar->getFluentCommands() as $commandName) {
                $attributeName = lcfirst($commandName);
                if (!isset($column->{$attributeName})) {
                    continue;
                }
                $value = $column->{$attributeName};
                $this->addCommand(
                    $commandName, compact('value', 'column')
                );
            }
        }
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name === 'create';
        });
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return \App\Support\ColumnDefinition
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return \App\Support\ColumnDefinition
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return \App\Support\ColumnDefinition
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  array|mixed $columns
     * @return \App\Support\ColumnDefinition
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Indicate that the given columns should be renamed.
     *
     * @param  string $from
     * @param  string $to
     * @return \App\Support\ColumnDefinition
     */
    public function renameColumn($from, $to)
    {
        return $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given spatial index should be dropped.
     *
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    public function dropSpatialIndex($index)
    {
        return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    public function dropForeign($index)
    {
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * Indicate that the given indexes should be renamed.
     *
     * @param  string $from
     * @param  string $to
     * @return \App\Support\ColumnDefinition
     */
    public function renameIndex($from, $to)
    {
        return $this->addCommand('renameIndex', compact('from', 'to'));
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestamps()
    {
        $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param  string $column
     * @return void
     */
    public function dropSoftDeletes($column = 'deleted_at')
    {
        $this->dropColumn($column);
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param  string $column
     * @return void
     */
    public function dropSoftDeletesTz($column = 'deleted_at')
    {
        $this->dropSoftDeletes($column);
    }

    /**
     * Indicate that the remember token column should be dropped.
     *
     * @return void
     */
    public function dropRememberToken()
    {
        $this->dropColumn('remember_token');
    }

    /**
     * Indicate that the polymorphic columns should be dropped.
     *
     * @param  string $name
     * @param  string|null $indexName
     * @return void
     */
    public function dropMorphs($name, $indexName = null)
    {
        $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));
        $this->dropColumn("{$name}_type", "{$name}_id");
    }

    /**
     * Rename the table to a given name.
     *
     * @param  string $to
     * @return \App\Support\ColumnDefinition
     */
    public function rename($to)
    {
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @param  string|null $algorithm
     * @return \App\Support\ColumnDefinition
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @param  string|null $algorithm
     * @return \App\Support\ColumnDefinition
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @param  string|null $algorithm
     * @return \App\Support\ColumnDefinition
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @return \App\Support\ColumnDefinition
     */
    public function spatialIndex($columns, $name = null)
    {
        return $this->indexCommand('spatialIndex', $columns, $name);
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @return \App\Support\ColumnDefinition
     */
    public function foreign($columns, $name = null)
    {
        return $this->indexCommand('foreign', $columns, $name);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function integerIncrements($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function tinyIncrements($column)
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function smallIncrements($column)
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function mediumIncrements($column)
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     *
     * @param  string $column
     * @param  int $length
     * @return ColumnDefinition
     */
    public function char($column, $length = null)
    {
        $length = $length ?: 191;
        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create a new string column on the table.
     *
     * @param  string $column
     * @param  int $length
     * @return ColumnDefinition
     */
    public function string($column, $length = null)
    {
        $length = $length ?: 191;
        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new text column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function text($column)
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function mediumText($column)
    {
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function longText($column)
    {
        return $this->addColumn('longText', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @param  bool $unsigned
     * @return ColumnDefinition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @param  bool $unsigned
     * @return ColumnDefinition
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @param  bool $unsigned
     * @return ColumnDefinition
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @param  bool $unsigned
     * @return ColumnDefinition
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @param  bool $unsigned
     * @return ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedTinyInteger($column, $autoIncrement = false)
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedSmallInteger($column, $autoIncrement = false)
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedMediumInteger($column, $autoIncrement = false)
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @param  bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string $column
     * @param  int $total
     * @param  int $places
     * @return ColumnDefinition
     */
    public function float($column, $total = 8, $places = 2)
    {
        return $this->addColumn('float', $column, compact('total', 'places'));
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string $column
     * @param  int|null $total
     * @param  int|null $places
     * @return ColumnDefinition
     */
    public function double($column, $total = null, $places = null)
    {
        return $this->addColumn('double', $column, compact('total', 'places'));
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string $column
     * @param  int $total
     * @param  int $places
     * @return ColumnDefinition
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    /**
     * Create a new unsigned decimal column on the table.
     *
     * @param  string $column
     * @param  int $total
     * @param  int $places
     * @return ColumnDefinition
     */
    public function unsignedDecimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, [
            'total' => $total, 'places' => $places, 'unsigned' => true,
        ]);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function boolean($column)
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string $column
     * @param  array $allowed
     * @return ColumnDefinition
     */
    public function enum($column, array $allowed)
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function json($column)
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function jsonb($column)
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function date($column)
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    /**
     * Create a new time column on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function time($column, $precision = 0)
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function timeTz($column, $precision = 0)
    {
        return $this->addColumn('timeTz', $column, compact('precision'));
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param  int $precision
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();
        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * Alias for self::timestamps().
     *
     * @param  int $precision
     * @return void
     */
    public function nullableTimestamps($precision = 0)
    {
        $this->timestamps($precision);
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * @param  int $precision
     * @return void
     */
    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->nullable();
        $this->timestampTz('updated_at', $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * @param  string $column
     * @param  int $precision
     * @return ColumnDefinition
     */
    public function softDeletesTz($column = 'deleted_at', $precision = 0)
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Create a new year column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function year($column)
    {
        return $this->addColumn('year', $column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function binary($column)
    {
        return $this->addColumn('binary', $column);
    }

    /**
     * Create a new uuid column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function uuid($column)
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new IP address column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function ipAddress($column)
    {
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function macAddress($column)
    {
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Create a new geometry column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function geometry($column)
    {
        return $this->addColumn('geometry', $column);
    }

    /**
     * Create a new point column on the table.
     *
     * @param  string $column
     * @param  int|null $srid
     * @return ColumnDefinition
     */
    public function point($column, $srid = null)
    {
        return $this->addColumn('point', $column, compact('srid'));
    }

    /**
     * Create a new linestring column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function lineString($column)
    {
        return $this->addColumn('linestring', $column);
    }

    /**
     * Create a new polygon column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function polygon($column)
    {
        return $this->addColumn('polygon', $column);
    }

    /**
     * Create a new geometrycollection column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function geometryCollection($column)
    {
        return $this->addColumn('geometrycollection', $column);
    }

    /**
     * Create a new multipoint column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function multiPoint($column)
    {
        return $this->addColumn('multipoint', $column);
    }

    /**
     * Create a new multilinestring column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function multiLineString($column)
    {
        return $this->addColumn('multilinestring', $column);
    }

    /**
     * Create a new multipolygon column on the table.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function multiPolygon($column)
    {
        return $this->addColumn('multipolygon', $column);
    }

    /**
     * Add the proper columns for a polymorphic table.
     *
     * @param  string $name
     * @param  string|null $indexName
     * @return void
     */
    public function morphs($name, $indexName = null)
    {
        $this->string("{$name}_type");
        $this->unsignedBigInteger("{$name}_id");
        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
     *
     * @param  string $name
     * @param  string|null $indexName
     * @return void
     */
    public function nullableMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();
        $this->unsignedBigInteger("{$name}_id")->nullable();
        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * @return ColumnDefinition
     */
    public function rememberToken()
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param  string $type
     * @param  string|array $columns
     * @param  string $index
     * @param  string|null $algorithm
     * @return \App\Support\ColumnDefinition
     */
    protected function indexCommand($type, $columns, $index, $algorithm = null)
    {
        $columns = (array)$columns;
        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $index = $index ?: $this->createIndexName($type, $columns);
        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * @param  string $command
     * @param  string $type
     * @param  string|array $index
     * @return \App\Support\ColumnDefinition
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];
        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }
        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * Create a default index name for the table.
     *
     * @param  string $type
     * @param  array $columns
     * @return string
     */
    protected function createIndexName($type, array $columns)
    {
        $index = strtolower($this->prefix . $this->table . '_' . implode('_', $columns) . '_' . $type);
        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  string $type
     * @param  string $name
     * @param  array $parameters
     * @return ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        $this->columns[] = $column = new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        );
        return $column;
    }

    /**
     * Remove a column from the schema blueprint.
     *
     * @param  string $name
     * @return $this
     */
    public function removeColumn($name)
    {
        $this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
            return $c['attributes']['name'] != $name;
        }));
        return $this;
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string $name
     * @param  array $parameters
     * @return \App\Support\ColumnDefinition
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);
        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param  string $name
     * @param  array $parameters
     * @return \App\Support\ColumnDefinition
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new ColumnDefinition(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the table the blueprint describes.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return ColumnDefinition[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return \App\Support\ColumnDefinition[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get the columns on the blueprint that should be added.
     *
     * @return ColumnDefinition[]
     */
    public function getAddedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return !$column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
     *
     * @return ColumnDefinition[]
     */
    public function getChangedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return (bool)$column->change;
        });
    }

}
