<?php

namespace ManaPHP;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Aop\Unaspectable;
use ManaPHP\Db\SqlFragmentable;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\ParameterOrderException;
use ManaPHP\Exception\UnknownPropertyException;
use ManaPHP\Helper\Sharding;
use ManaPHP\Helper\Sharding\ShardingTooManyException;
use ManaPHP\Helper\Str;
use ManaPHP\Model\Expression\Decrement;
use ManaPHP\Model\Expression\Increment;
use ManaPHP\Model\NotFoundException;
use ManaPHP\Model\Relation\BelongsTo;
use ManaPHP\Model\Relation\HasMany;
use ManaPHP\Model\Relation\HasManyOthers;
use ManaPHP\Model\Relation\HasManyToMany;
use ManaPHP\Model\Relation\HasOne;
use ManaPHP\Model\SerializeNormalizable;
use ManaPHP\Validator\ValidateFailedException;
use ReflectionClass;
use Serializable;

/**
 * Class ManaPHP\Model
 *
 * @package ManaPHP
 * @property-read \ManaPHP\Di $_di
 */
abstract class Model implements ModelInterface, Serializable, ArrayAccess, JsonSerializable, Unaspectable
{
    const OP_NONE = 0;
    const OP_CREATE = 1;
    const OP_READ = 2;
    const OP_UPDATE = 3;
    const OP_DELETE = 4;

    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * @var float
     */
    protected $_last_refresh = 0;

    /**
     * \ManaPHP\Model constructor
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        if ($data) {
            foreach ($this->getJsonFields() as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $value = $data[$field];
                    $data[$field] = $value === '' ? [] : json_parse($value);
                }
            }

            $this->_snapshot = $data;

            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getAnyShard()
    {
        $shards = $this->getAllShards();

        return [key($shards), current($shards)[0]];
    }

    /**
     * @param array|\ManaPHP\Model $context =get_object_vars(new static)
     *
     * @return array
     */
    public function getUniqueShard($context)
    {
        $shards = $this->getMultipleShards($context);
        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `:dbs`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `:tables`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }

    /**
     * @param array|\ManaPHP\Model $context =get_object_vars(new static)
     *
     * @return array
     */
    public function getMultipleShards($context)
    {
        $db = $this->getDb();
        $table = $this->getTable();

        if (strcspn($db, ':,') === strlen($db) && strcspn($table, ':,') === strlen($table)) {
            return [$db => [$table]];
        } else {
            return Sharding::multiple($db, $table, $context);
        }
    }

    /**
     * @return array
     */
    public function getAllShards()
    {
        $db = $this->getDb();
        $table = $this->getTable();

        if (strcspn($db, ':,') === strlen($db) && strcspn($table, ':,') === strlen($table)) {
            return [$db => [$table]];
        } else {
            return Sharding::all($db, $table);
        }
    }

    /**
     * @param string $class
     *
     * @return string|null
     */
    protected function _inferPrimaryKey($class)
    {
        $fields = $this->getFields();

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $tryField = lcfirst(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1)) . '_id';
        if (in_array($tryField, $fields, true)) {
            return $tryField;
        }

        $table = $this->getTable();
        if (($pos = strpos($table, ':')) !== false) {
            $table = substr($table, 0, $pos);
        } elseif (($pos = strpos($table, ',')) !== false) {
            $table = substr($table, 0, $pos);
        }

        $tryField = (($pos = strpos($table, '.')) ? substr($table, $pos + 1) : $table) . '_id';
        if (in_array($tryField, $fields, true)) {
            return $tryField;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getForeignedKey()
    {
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey !== 'id') {
            return $primaryKey;
        }

        $table = $this->getTable();

        if (($pos = strpos($table, '.')) !== false) {
            $table = substr($table, $pos + 1);
        }

        if (($pos = strpos($table, ':')) !== false) {
            $key = substr($table, 0, $pos) . '_id';
        } else {
            $key = $table . '_id';
        }

        return $key;
    }

    /**
     * Returns table name mapped in the model
     *
     * @return string
     */
    public function getTable()
    {
        $class = static::class;
        return Str::underscore(($pos = strrpos($class, '\\')) === false ? $class : substr($class, $pos + 1));
    }

    /**
     * @return string|null =key(get_object_vars(new static))
     */
    public function getAutoIncrementField()
    {
        $primaryKey = $this->getPrimaryKey();
        return is_string($primaryKey) ? $primaryKey : null;
    }

    /**
     * @param string $field =key(get_object_vars(new static))
     *
     * @return bool
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields(), true);
    }

    /**
     * @param string $field =key(get_object_vars(new static))
     *
     * @return string
     */
    public function getDateFormat($field)
    {
        if (isset($this->_snapshot[$field])) {
            $ts = is_numeric($this->_snapshot[$field]);
        } elseif (isset($this->$field)) {
            $ts = is_numeric($this->$field);
        } else {
            $ts = in_array($field, $this->getIntFields(), true);
        }
        return $ts ? 'U' : 'Y-m-d H:i:s';
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getSafeFields()
    {
        return array_keys($this->rules());
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function getJsonFields()
    {
        return [];
    }

    /**
     * @return array =get_object_vars(new static) ?: [$field => \PHPSTORM_META\validator_rule()]
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array =get_object_vars(new static)
     */
    public function labels()
    {
        return [];
    }

    /**
     * @return static
     */
    public static function sample()
    {
        static $cached;

        $class = static::class;

        if (!isset($cached[$class])) {
            $cached[$class] = new $class;
        }

        return $cached[$class];
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array $filters =get_object_vars(new static)
     * @param array $options =['order'=>get_object_vars(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC], 'index'=>get_object_vars(new static)]
     * @param array $fields  =get_object_vars(new static)
     *
     * @return  static[]
     */
    public static function all($filters = [], $options = null, $fields = null)
    {
        return static::select($fields)->where($filters)->options($options)->fetch();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array $filters =get_object_vars(new static)
     * @param array $options =['order'=>get_object_vars(new static) ?: [$k=>SORT_ASC, $k2=>SORT_DESC], 'index'=>get_object_vars(new static)]
     * @param array $fields  =get_object_vars(new static)
     *
     * @return  \ManaPHP\Paginator
     */
    public static function paginate($filters = [], $options = null, $fields = null)
    {
        return static::select($fields)->search($filters)->options($options)->paginate();
    }

    /**
     * @param string|array $fields  =get_object_vars(new static) ?: key(get_object_vars(new static)) ?: [$k=>key(get_object_vars(new static))]
     * @param array        $filters =get_object_vars(new static)
     *
     * @return array
     */
    public static function lists($fields, $filters = null)
    {
        $sample = static::sample();

        if (is_string($fields)) {
            $keyField = $sample->getPrimaryKey();

            $query = static::select([$keyField, $fields])->where($filters);
            if ($sample->hasField('display_order')) {
                return $query->orderBy(['display_order' => SORT_DESC, $keyField => SORT_ASC])->execute();
            } else {
                return $query->orderBy([$keyField => SORT_ASC])->execute();
            }
        } elseif (isset($fields[0])) {
            $keyField = $sample->getPrimaryKey();
            array_unshift($fields, $keyField);

            if ($sample->hasField('display_order')) {
                $order = ['display_order' => SORT_DESC, $keyField => SORT_ASC];
            } else {
                $order = [$keyField => SORT_ASC];
            }
            return static::select($fields)->where($filters)->orderBy($order)->execute();
        } else {
            $keyField = key($fields);
            $valueField = current($fields);

            $list = [];
            foreach (static::select([$keyField, $valueField])->where($filters)->execute() as $v) {
                $key = $v[$keyField];
                $value = $v[$valueField];

                if (!isset($list[$key])) {
                    $list[$key] = $value;
                } elseif (is_array($list[$key])) {
                    $list[$key][] = $value;
                } else {
                    $list[$key] = [$list[$key], $value];
                }
            }

            return $list;
        }
    }

    /**
     * @param int|string $id
     * @param int|array  $fieldsOrTtl =get_object_vars(new static)
     *
     * @return static
     */
    public static function get($id, $fieldsOrTtl = null)
    {
        if (!is_scalar($id)) {
            throw new InvalidValueException('Model::get id is not scalar');
        }

        $model = static::sample();

        if (!is_int($fieldsOrTtl)) {
            if (!$rs = static::select($fieldsOrTtl)->whereEq($model->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(static::class, $id);
            } else {
                return $rs[0];
            }
        }

        $ttl = $fieldsOrTtl;

        $key = '_mp:models:' . static::class . ":get:$id:$ttl";
        if ($r = $model->_di->ipcCache->get($key)) {
            return $r;
        }

        if (!$r) {
            if (!$rs = static::select()->whereEq($model->getPrimaryKey(), $id)->limit(1)->fetch()) {
                throw new NotFoundException(static::class, $id);
            }

            $r = $rs[0];

            $model->_di->ipcCache->set($key, $r, $ttl);
        }

        return $r;
    }

    /**
     * @param array|string $fields =get_object_vars(new static)
     * @param string       $alias
     *
     * @return \ManaPHP\QueryInterface <static>
     */
    public static function select($fields = [], $alias = null)
    {
        return static::query($alias)->select($fields);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param int|string|array $filters =get_object_vars(new static)
     * @param array            $fields  =get_object_vars(new static)
     *
     * @return static|null
     */
    public static function first($filters, $fields = null)
    {
        if ($filters === null) {
            throw new MisuseException('Model:first is not support null value filters');
        }

        $rs = static::select($fields)
            ->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters)
            ->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     * @param array            $fields  =get_object_vars(new static)
     *
     * @return static
     */
    public static function firstOrFail($filters, $fields = null)
    {
        if (!$r = static::first($filters, $fields)) {
            throw new NotFoundException(static::class, $filters);
        }

        return $r;
    }

    /**
     * @return int|string
     */
    public static function rId()
    {
        $sample = static::sample();

        return $sample->_di->request->getId($sample->getPrimaryKey());
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public static function rGet($fields = null)
    {
        return static::get(static::rId(), $fields);
    }

    /**
     * Allows to query the last record that match the specified conditions
     *
     * @param array $filters =get_object_vars(new static)
     * @param array $fields  =get_object_vars(new static)
     *
     * @return static|null
     */
    public static function last($filters = null, $fields = null)
    {
        $sample = static::sample();

        $primaryKey = $sample->getPrimaryKey();
        $rs = static::select($fields)->where($filters)->orderBy([$primaryKey => SORT_DESC])->limit(1)->fetch();
        return $rs[0] ?? null;
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     * @param string           $field   =key(get_object_vars(new static))
     * @param int              $ttl
     *
     * @return int|float|string|null
     */
    public static function value($filters, $field, $ttl = null)
    {
        if (!is_string($field)) {
            throw new ParameterOrderException(__METHOD__ . ' field');
        }

        if ($ttl !== null && !is_int($ttl)) {
            throw new MisuseException('ttl must be a integer');
        }

        $sample = static::sample();
        $pkName = $sample->getPrimaryKey();

        $pkValue = null;
        if (is_scalar($filters)) {
            $pkValue = $filters;
            $filters = [$pkName => $pkValue];
        } elseif (is_array($filters)) {
            if (count($filters) === 1 && isset($filters[$pkName])) {
                $pkValue = $filters[$pkName];
            }
        }

        if ($ttl === null || $pkValue === null) {
            $rs = static::select([$field])->where($filters)->limit(1)->execute();
            return $rs ? $rs[0][$field] : null;
        }

        $key = '_mp:models:' . static::class . ":value:$field:$pkValue:$ttl";
        if (($value = $sample->_di->ipcCache->get($key)) !== false) {
            return $value;
        }

        $rs = static::select([$field])->whereEq($pkName, $pkValue)->limit(1)->execute();
        $value = $rs ? $rs[0][$field] : null;

        $sample->_di->ipcCache->set($key, $value, $ttl);

        return $value;
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     * @param string           $field   =key(get_object_vars(new static))
     * @param int              $ttl
     *
     * @return int|float|string
     */
    public static function valueOrFail($filters, $field, $ttl = null)
    {
        $value = static::value($filters, $field, $ttl);
        if ($value === null) {
            throw new NotFoundException(static::class, $filters);
        } else {
            return $value;
        }
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     * @param string|float|int $field   =key(get_object_vars(new static))
     * @param mixed            $default
     *
     * @return float|int|string
     */
    public static function valueOrDefault($filters, $field, $default)
    {
        return ($value = static::value($filters, $field)) === null ? $default : $value;
    }

    /**
     * @param string $field   =key(get_object_vars(new static))
     * @param array  $filters =get_object_vars(new static)
     *
     * @return array
     */
    public static function values($field, $filters = null)
    {
        if (!is_string($field)) {
            throw new ParameterOrderException(__METHOD__ . ' field');
        }
        return static::where($filters)->orderBy([$field => SORT_ASC])->values($field);
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     *
     * @return bool
     */
    public static function exists($filters)
    {
        return static::select()->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters)->exists();
    }

    /**
     * @param array        $filters =get_object_vars(new static)
     * @param array        $aggregation
     * @param string|array $options
     *
     * @return array
     */
    public static function aggregate($filters, $aggregation, $options = null)
    {
        if (is_string($options)) {
            if (str_contains($options, ',')) {
                $options = ['group' => $options];
            } else {
                $options = ['group' => $options, 'index' => $options];
            }
        }
        return static::where($filters)->options($options)->aggregate($aggregation);
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * @param array  $filters =get_object_vars(new static)
     * @param string $field   =key(get_object_vars(new static))
     *
     * @return int
     */
    public static function count($filters = null, $field = '*')
    {
        return static::where($filters)->count($field);
    }

    /**
     * Allows to calculate a summary on a field that match the specified conditions
     *
     * @param string $field   =key(get_object_vars(new static))
     * @param array  $filters =get_object_vars(new static)
     *
     * @return int|float|null
     */
    public static function sum($field, $filters = null)
    {
        return static::where($filters)->sum($field);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string $field   =key(get_object_vars(new static))
     * @param array  $filters =get_object_vars(new static)
     *
     * @return int|float|null
     */
    public static function max($field, $filters = null)
    {
        return static::where($filters)->max($field);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     *
     * @param string $field   =key(get_object_vars(new static))
     * @param array  $filters =get_object_vars(new static)
     *
     * @return int|float|null
     */
    public static function min($field, $filters = null)
    {
        return static::where($filters)->min($field);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string $field   =key(get_object_vars(new static))
     * @param array  $filters =get_object_vars(new static)
     *
     * @return float|null
     */
    public static function avg($field, $filters = null)
    {
        return (float)static::where($filters)->avg($field);
    }

    /**
     * Assigns values to a model from an array
     *
     * @param array|\ManaPHP\Model $data   =get_object_vars(new static)
     * @param array                $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function assign($data, $fields)
    {
        if ($data instanceof self) {
            foreach ($fields as $field) {
                $this->$field = $data->$field;
            }
        } else {
            foreach ($fields as $field) {
                $this->$field = $data[$field];
            }
        }

        return $this;
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function load($fields = null)
    {
        $fields = $fields ?? $this->getSafeFields();
        $data = $this->_di->request->getContext()->_REQUEST;

        foreach ($fields as $k => $v) {
            if (is_string($k)) {
                $field = $k;
                $value = $v;
            } elseif (isset($data[$field = $v])) {
                $value = $data[$field];
            } else {
                continue;
            }

            $this->$field = is_string($value) ? trim($value) : $value;
        }

        return $this;
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return void
     */
    public function validate($fields = null)
    {
        if (!$rules = $this->rules()) {
            return;
        }

        if (isset($rules[0])) {
            throw new MisuseException([
                '`:model` rules must be an associative array: `:field` is invalid',
                'model' => static::class,
                'field' => $rules[0]
            ]);
        }

        $errors = [];

        foreach ($fields ?: $this->getChangedFields() as $field) {
            if (!isset($rules[$field]) || $this->$field instanceof SqlFragmentable) {
                continue;
            }

            try {
                $this->$field = $this->_di->validator->validateModel($field, $this, $rules[$field]);
            } catch (ValidateFailedException $exception) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $errors += $exception->getErrors();
            }
        }

        if ($errors) {
            throw new ValidateFailedException($errors);
        }
    }

    /**
     * @param string $field =key(get_object_vars(new static))
     * @param array  $rules
     *
     * @return void
     */
    public function validateField($field, $rules = null)
    {
        if ($rules === null) {
            if (!isset($rules[$field])) {
                return;
            }

            $rules = $rules[$field];
        }

        $this->$field = $this->_di->validator->validateModel($field, $this, $rules);
    }

    /**
     * @param int $opMode
     *
     * @return  array
     */
    public function getAutoFilledData($opMode)
    {
        $data = [];

        $current_time = time();
        $user_id = $this->_di->identity->getId(0);
        $user_name = $this->_di->identity->getName('');

        if ($opMode === self::OP_CREATE) {
            foreach ($this->getFields() as $field) {
                if ($this->$field !== null) {
                    continue;
                }

                if (in_array($field, ['created_time', 'created_at', 'updated_time', 'updated_at'], true)) {
                    $data[$field] = date($this->getDateFormat($field), $current_time);
                } elseif (in_array($field, ['creator_id', 'created_id', 'updator_id', 'updated_id'], true)) {
                    $data[$field] = $user_id;
                } elseif (in_array($field, ['creator_name', 'created_name', 'updator_name', 'updated_name'], true)) {
                    $data[$field] = $user_name;
                } elseif (in_array($field, ['created_date', 'updated_date'], true)) {
                    $data[$field] = (int)date('ymd', $current_time);
                }
            }
        } elseif ($opMode === self::OP_UPDATE) {
            foreach ($this->getFields() as $field) {
                if (in_array($field, ['updated_time', 'updated_at'], true)) {
                    $data[$field] = date($this->getDateFormat($field), $current_time);
                } elseif (in_array($field, ['updator_id', 'updated_id'], true)) {
                    $data[$field] = $user_id;
                } elseif (in_array($field, ['updator_name', 'updated_name'], true)) {
                    $data[$field] = $user_name;
                } elseif ($field === 'updated_date') {
                    $data[$field] = (int)date('ymd', $current_time);
                }
            }
        }

        return $data;
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public static function rCreate($fields = null)
    {
        return (new static())->load($fields)->create();
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public static function rUpdate($fields = null)
    {
        return static::rGet()->load($fields)->update();
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return bool
     */
    protected function _exists()
    {
        $primaryKey = $this->getPrimaryKey();
        if ($this->$primaryKey === null) {
            return false;
        } else {
            return $this->newQuery()->where([$primaryKey => $this->$primaryKey])->forceUseMaster()->exists();
        }
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function save($fields = null)
    {
        if ($fields) {
            $this->load($fields);
        }

        $primaryKey = $this->getPrimaryKey();
        if ($this->_snapshot || $this->$primaryKey) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /**
     * @return static
     */
    public static function rDelete()
    {
        return static::rGet()->delete();
    }

    /**
     * @param array $fieldValues =get_object_vars(new static)
     * @param array $filters     =get_object_vars(new static)
     *
     * @return int
     */
    public static function updateAll($fieldValues, $filters)
    {
        return static::where($filters)->update($fieldValues);
    }

    /**
     * @param array $filters =get_object_vars(new static)
     *
     * @return int
     */
    public static function deleteAll($filters)
    {
        return static::where($filters)->delete();
    }

    /**
     * @param string|array $withs
     *
     * @return static
     */
    public function with($withs)
    {
        $this->_di->relationsManager->earlyLoad($this, [$this], $withs, false);
        return $this;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array =get_object_vars(new static)
     */
    public function toArray()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($field[0] === '_') {
                continue;
            }

            if (is_object($value)) {
                if ($value instanceof self) {
                    $value = $value->toArray();
                } else {
                    continue;
                }
            } elseif (is_array($value) && ($first = current($value)) && $first instanceof self) {
                foreach ($value as $k => $v) {
                    $value[$k] = $v->toArray();
                }
            }

            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function only($fields)
    {
        $model = new static();
        $model->_snapshot = false;

        foreach ($fields as $field) {
            $model->$field = $this->$field;
        }

        return $model;
    }

    /**
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function except($fields)
    {
        $model = clone $this;
        $model->_snapshot = false;

        foreach ($fields as $field) {
            unset($model->$field);
        }

        return $model;
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array =get_object_vars(new static)
     */
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array =get_object_vars(new static)
     */
    public function getChangedFields()
    {
        $snapshot = $this->_snapshot;

        $changed = [];
        foreach ($this->getFields() as $field) {
            if (isset($snapshot[$field])) {
                if ($this->{$field} !== $snapshot[$field]) {
                    $changed[] = $field;
                }
            } elseif ($this->$field !== null) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|array $fields =get_object_vars(new static)
     *
     * @return bool
     */
    public function hasChanged($fields)
    {
        $snapshot = $this->_snapshot;

        foreach ((array)$fields as $field) {
            if (!isset($snapshot[$field]) || $this->{$field} !== $snapshot[$field]) {
                return true;
            }
        }

        return false;
    }

    public function fireEvent($event, $data = [])
    {
        $this->_di->eventsManager->fireEvent($event, $this, $data);
    }

    /**
     * @param float $interval
     * @param array $fields =get_object_vars(new static)
     *
     * @return static
     */
    public function refresh($interval, $fields = null)
    {
        if ($interval > 0) {
            if ($this->_last_refresh && microtime(true) - $this->_last_refresh < $interval) {
                return $this;
            }
            $this->_last_refresh = microtime(true);
        }

        $primaryKey = $this->getPrimaryKey();
        $r = $this->newQuery()->select($fields)->where([$primaryKey => $this->$primaryKey])->execute();
        if (!$r) {
            throw new NotFoundException(static::class, [$primaryKey => $this->$primaryKey]);
        }

        $data = (array)$r[0];
        foreach ($this->getJsonFields() as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                if ($data[$field] === '') {
                    $data[$field] = [];
                } elseif (($json = json_parse($data[$field])) === null) {
                    throw new InvalidJsonException([
                        '`:field` field value of `:model` is not a valid json string',
                        'field' => $field,
                        'model' => static::class
                    ]);
                } else {
                    $data[$field] = $json;
                }
            }
        }

        foreach ($data as $field => $value) {
            $this->$field = $value;
        }

        $this->_snapshot = array_merge($this->_snapshot, $data);

        return $this;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @param string $serialized
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized, ['allowed_classes' => false]);
        $this->_snapshot = $unserialized;

        foreach ((array)$unserialized as $field => $value) {
            $this->$field = $value;
        }
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [];
    }

    /**
     * @param string $name
     * @param bool   $comment
     *
     * @return array
     */
    public static function constants($name, $comment = false)
    {
        $name = strtoupper($name) . '_';
        $constants = [];

        $rc = new ReflectionClass(static::class);
        $file = $comment ? file_get_contents($rc->getFileName()) : '';
        foreach ($rc->getConstants() as $cName => $cValue) {
            if (str_starts_with($cName, $name)) {
                if ($comment && preg_match('#\s+const\s+' . $cName . '\s*=[^/]+//(<([^>\r\n]+)>|[^\s]+)#', $file, $match)) {
                    $constants[$cValue] = trim($match[2] ?? $match[1]);
                } else {
                    $constants[$cValue] = strtolower(substr($cName, strlen($name)));
                }
            }
        }

        if (!$constants) {
            throw new MisuseException(['starts with `:1` constants is not exists in `:2` model', $name, static::class]);
        }

        return $constants;
    }

    /**
     * @param string    $field =key(get_object_vars(new static))
     * @param int|float $step
     *
     * @return static
     */
    public function increment($field, $step = 1)
    {
        if (!$this->hasField($field)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Increment($step);

        return $this;
    }

    /**
     * @param string    $field =key(get_object_vars(new static))
     * @param int|float $step
     *
     * @return static
     */
    public function decrement($field, $step = 1)
    {
        if (!$this->hasField($field)) {
            throw new InvalidArgumentException([':field field is invalid.', 'field' => $field]);
        }

        $this->$field = new Decrement($step);

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return \ManaPHP\QueryInterface <static>
     */
    public static function query($alias = null)
    {
        $query = static::sample()->newQuery();

        return $alias ? $query->from(static::class, $alias) : $query;
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\QueryInterface <static>
     */
    public static function where($filters)
    {
        return static::select()->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters);
    }

    /**
     * @param array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\QueryInterface <static>
     */
    public static function search($filters)
    {
        return static::select()->search($filters);
    }

    /**
     * Deletes a model instance.
     *
     * @return static
     */
    public function delete()
    {
        $primaryKey = $this->getPrimaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($db, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:deleting');

        /** @var DbInterface $db */
        $db = $this->_di->getShared($db);

        $db->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }

    /**
     * @param string $thatModel
     * @param string $thisField =key(get_object_vars(new static))
     *
     * @return \ManaPHP\Model\Relation\BelongsTo
     */
    public function belongsTo($thatModel, $thisField = null)
    {
        /** @var \ManaPHP\Model $thatModel */
        $thatInstance = $thatModel::sample();

        return new BelongsTo(static::class, $thisField ?? $thatInstance->getForeignedKey(), $thatModel, $thatInstance->getPrimaryKey());
    }

    /**
     * @param string $thatModel
     * @param string $thatField =key(get_object_vars(new static))
     *
     * @return \ManaPHP\Model\Relation\HasOne
     */
    public function hasOne($thatModel, $thatField = null)
    {
        return new HasOne(static::class, $this->getPrimaryKey(), $thatModel, $thatField ?? $this->getForeignedKey());
    }

    /**
     * @param string $thatModel
     * @param string $thatField =key(get_object_vars(new static))
     *
     * @return \ManaPHP\Model\Relation\HasMany
     */
    public function hasMany($thatModel, $thatField = null)
    {
        return new HasMany(static::class, $this->getPrimaryKey(), $thatModel, $thatField ?? $this->getForeignedKey());
    }

    /**
     * @param string $thatModel
     * @param string $pivotModel
     *
     * @return \ManaPHP\Model\Relation\HasManyToMany
     */
    public function hasManyToMany($thatModel, $pivotModel)
    {
        /** @var \ManaPHP\Model $thatModel */
        $thatInstance = $thatModel::sample();

        return new HasManyToMany(static::class, $this->getPrimaryKey(), $thatModel, $thatInstance->getPrimaryKey(),
            $pivotModel, $this->getForeignedKey(), $thatInstance->getForeignedKey());
    }

    /**
     * @param string $thatModel
     * @param string $thisFilter =key(get_object_vars(new static))
     *
     * @return \ManaPHP\Model\Relation\HasManyOthers
     */
    public function hasManyOthers($thatModel, $thisFilter = null)
    {
        /** @var \ManaPHP\Model $thatModel */
        $thatInstance = $thatModel::sample();

        $foreingedKey = $thatInstance->getForeignedKey();

        if ($thisFilter === null) {
            $keys = [];
            foreach ($this->getFields() as $field) {
                if ($field === $foreingedKey || $field === 'id' || $field === '_id' || !str_contains($field, '_id')) {
                    continue;
                }

                if (in_array($field, ['updator_id', 'creator_id'], true)) {
                    continue;
                }

                $keys[] = $field;
            }

            if (count($keys) === 1) {
                $thisFilter = $keys[0];
            } else {
                throw new MisuseException('$thisValue must be not null');
            }
        }

        return new HasManyOthers(static::class, $thisFilter, $thatInstance->getForeignedKey(), $thatModel, $thatInstance->getPrimaryKey());
    }

    /**
     * alias of hasManyToMany
     *
     * @param string $thatModel
     * @param string $pivotModel
     *
     * @return \ManaPHP\Model\Relation\HasManyToMany
     */
    public function belongsToMany($thatModel, $pivotModel)
    {
        return $this->hasManyToMany($thatModel, $pivotModel);
    }

    /**
     * @param string $name
     *
     * @return \ManaPHP\Model|\ManaPHP\Model[]|mixed
     * @throws \ManaPHP\Exception\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($name === '_di') {
            return $this->_di = Di::getDefault();
        }

        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$name = $this->$method()->fetch();
        } elseif ($this->_di->has($name)) {
            return $this->{$name} = $this->_di->getShared($name);
        } elseif ($this->_di->relationsManager->has($this, $name)) {
            return $this->$name = $this->_di->relationsManager->lazyLoad($this, $name)->fetch();
        } else {
            throw new UnknownPropertyException([
                '`:model` does not contain `:field` field: `:fields`',
                'model' => static::class,
                'field' => $name,
                'fields' => $this->getFields()
            ]);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (is_scalar($value)) {
            throw new MisuseException(['`:model` Model does\'t contains `:field` field', 'field' => $name, 'model' => static::class]);
        }

        $this->$name = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return \ManaPHP\QueryInterface
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function __call($name, $arguments)
    {
        if (str_starts_with($name, 'get')) {
            $relation = lcfirst(substr($name, 3));
            if ($this->_di->relationsManager->has($this, $relation)) {
                return $this->_di->relationsManager->lazyLoad($this, $relation);
            } else {
                throw new NotSupportedException(['`:model` model does not define `:method` relation', 'model' => static::class, 'method' => $relation]);
            }
        }
        throw new NotSupportedException(['`:model` does not contain `:method` method', 'model' => static::class, 'method' => $name]);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if (in_array($field, ['_di', '_snapshot', '_last_refresh'], true)) {
                continue;
            }

            if ($value instanceof Component && !$value instanceof self) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($changedFields = $this->getChangedFields()) {
            $data['*changed_fields*'] = $changedFields;
        }

        foreach ($this->getFields() as $field) {
            if (!isset($this->$field)) {
                continue;
            }

            $value = $this->$field;

            if (is_int($value) && $value > 100000000 /**1973/3/3 17:46:40*/ && !str_contains($field, '_id')) {
                $data['*human_time*'][$field] = date('Y-m-d H:i:s', $value);
            }

            if (is_numeric($value)) {
                foreach ((new ReflectionClass(static::class))->getConstants() as $cName => $cValue) {
                    /** @noinspection TypeUnsafeComparisonInspection */
                    if ($cValue == $value && stripos($cName, $field) === 0) {
                        $data['*human_const*'][$field] = $cName;
                    }
                }
            }
        }

        return $data;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    public function jsonSerialize()
    {
        $data = $this->toArray();

        return $this instanceof SerializeNormalizable ? $this->serializeNormalize($data) : $data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_stringify($this->toArray());
    }
}
