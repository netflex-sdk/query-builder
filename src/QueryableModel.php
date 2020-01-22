<?php

namespace Netflex\Query;

use Exception;
use ArrayAccess;
use JsonSerializable;

use Netflex\Query\Traits\Queryable;
use Netflex\Query\Traits\ModelMapper;
use Netflex\Query\Traits\HasRelation;
use Netflex\Query\Traits\Resolvable;

use Netflex\Query\Exceptions\MassAssignmentException;
use Netflex\Query\Exceptions\JsonEncodingException;

use GuzzleHttp\Exception\GuzzleException;

use Illuminate\Support\Arr;

use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Routing\UrlRoutable;

abstract class QueryableModel implements Arrayable, ArrayAccess, Jsonable, JsonSerializable, UrlRoutable
{
  use GuardsAttributes, HasAttributes, HasEvents, HasRelation, HasTimestamps, HidesAttributes, ModelMapper, Queryable, Resolvable;

  /**
   * The number of models to return for pagination.
   *
   * @var int
   */
  protected $perPage;

  /**
   * Indicates if the model exists.
   *
   * @var bool
   */
  public $exists = false;

  /**
   * Indicates if the model was inserted during the current request lifecycle.
   *
   * @var bool
   */
  public $wasRecentlyCreated = false;

  /**
   * The array of trait initializers that will be called on each new instance.
   *
   * @var array
   */
  protected static $traitInitializers = [];

  /**
   * The array of booted models.
   *
   * @var array
   */
  protected static $booted = [];

  /**
   * The interal storage of the model data.
   *
   * @var string
   */
  protected $attributes = [];

  /**
   * The relation associated with the model.
   *
   * @var string
   */
  protected $relation;

  /**
   * The directory_id associated with the model.
   *
   * @var int
   */
  protected $relationId;

  /**
   * The primary field for the model.
   *
   * @var string
   */
  protected $primaryField = 'id';

  /**
   * The "type" of the primary key ID.
   *
   * @var string
   */
  protected $keyType = 'int';

  /**
   * The resolvable field associated with the model.
   *
   * @var string
   */
  protected $resolvableField;

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = true;

  /**
   * Indicates if we should respect the models publishing status when retrieving it.
   *
   * @var bool
   */
  protected $respectPublishingStatus = true;

  /**
   * Indicates if we should automatically publish the model on save.
   *
   * @var bool
   */
  protected $autoPublishes = true;

  /**
   * The event dispatcher
   * @var Dispatcher
   */
  protected static $dispatcher;

  /**
   * The name of the "created at" column.
   *
   * @var string
   */
  const CREATED_AT = 'created';

  /**
   * The name of the "updated at" column.
   *
   * @var string
   */
  const UPDATED_AT = 'updated';


  /**
   * @param array $attributes
   */
  public function __construct(array $attributes = [])
  {
    $this->dateFormat = 'Y-m-d H:i:s';
    $this->bootIfNotBooted();
    $this->initializeTraits();
    $this->fill($attributes);
  }

  /**
   * Retrieves a record by key
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return array|null
   */
  protected function performRetrieveRequest(?int $relationId = null, $key)
  {
    //
  }

  /**
   * Inserts a new record, and returns its id
   *
   * @property ?int $relationId
   * @property array $attributes
   * @return mixed
   */
  protected function performInsertRequest(?int $relationId = null, array $attributes = [])
  {
    //
  }

  /**
   * Updates a record
   *
   * @param int|null $relationId
   * @param mixed $key
   * @param array $attributes
   * @return void
   */
  protected function performUpdateRequest(?int $relationId = null, $key, $attributes = [])
  {
    //
  }

  /**
   * Deletes a record
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return bool
   */
  protected function performDeleteRequest(?int $relationId = null, $key)
  {
    //
  }

  /**
   * Reload a fresh model instance from the database.
   *
   * @return static|null
   */
  public function fresh()
  {
    if (!$this->exists) {
      return;
    }

    $fresh = (new static)->newInstance([], true);

    return $fresh->withIgnoredPublishingStatus(function () use ($fresh) {
      try {
        $attributes = [$this->getKeyName() => $this->getKeyName()];
        $attributes = array_merge($attributes, $this->performRetrieveRequest($this->getRelationId(), $this->getKey()));
        $fresh->setRawAttributes($attributes, true);
        return $fresh;
      } catch (GuzzleException $ex) {
        return null;
      }
    });
  }

  /**
   * Create a new model instance that is existing.
   *
   * @param array $attributes
   * @return static
   */
  public function newFromBuilder($attributes = [])
  {
    $model = $this->newInstance([], true);

    $model->setRawAttributes((array) $attributes, true);

    $model->fireModelEvent('retrieved', false);

    return $model;
  }

  /**
   * Saves a new model
   *
   * @param array $attributes
   * @return static
   */
  public static function create(array $attributes = [])
  {
    $model = (new static)->newInstance($attributes);

    $model->save();

    return $model;
  }

  /**
   * Create a new instance of the given model.
   *
   * @param  array  $attributes
   * @param  bool  $exists
   * @return static
   */
  public function newInstance($attributes = [], $exists = false)
  {
    $model = new static((array) $attributes);

    $model->exists = $exists;

    return $model;
  }

  /**
   * @param string|null $tags
   * @return array
   */
  public function getTagsAttribute($tags = null)
  {
    return $tags ? explode(',', $tags) : [];
  }

  /**
   * @param array $tags
   * @return void
   */
  public function setTagsAttribute(array $tags = [])
  {
    $this->attributes['tags'] = implode(',', $tags);
  }

  /**
   * @todo
   *
   * @return void
   */
  public static function firstOrCreate()
  {
  }

  /**
   * Retrieves the first model, or creates a new instance if not found
   *
   * @param array $attributes
   * @return static
   */
  public static function firstOrNew($attributes = [])
  {
    if ($first = static::first()) {
      return $first;
    }

    return (new static)->newInstance($attributes, false);
  }

  /**
   * Perform a model update operation.
   *
   * @return bool
   */
  protected function performUpdate()
  {
    if ($this->fireModelEvent('updating') === false) {
      return false;
    }

    $dirty = $this->getDirty();
    $dirty['revision_publish'] = true;

    if ($this->autoPublishes) {
      $dirty['published'] = true;
    }

    if (count($dirty) > 0) {
      $this->performUpdateRequest($this->getRelationId(), $this->getKey(), $dirty);

      $this->withIgnoredPublishingStatus(function () {
        $this->attributes = $this->performRetrieveRequest($this->getRelationId(), $this->getKey());
      });

      $this->fireModelEvent('updated', false);
    }

    return true;
  }

  /**
   * Perform an action with temporary disabled respectPublishingStatus
   *
   * @param callable $callback
   * @return mixed
   */
  protected function withIgnoredPublishingStatus($callback)
  {
    $respectPublishingStatus = $this->respectPublishingStatus;
    $this->respectPublishingStatus = false;
    $result = $callback();
    $this->respectPublishingStatus = $respectPublishingStatus;

    return $result;
  }

  /**
   * Perform a model insert operation.
   *
   * @return bool
   */
  protected function performInsert()
  {
    if ($this->fireModelEvent('creating') === false) {
      return false;
    }

    $attributes = $this->getAttributes();
    $attributes['revision_publish'] = true;
    $attributes['name'] = $attributes['name'] ?? uuid();

    if ($this->autoPublishes) {
      $attributes['published'] = true;
    }

    $this->attributes[$this->getKeyName()] = $this->performInsertRequest($this->getRelationId(), $attributes);

    $this->withIgnoredPublishingStatus(function () {
      $this->attributes = $this->performRetrieveRequest($this->getRelationId(), $this->getKey());
    });

    $this->exists = true;

    $this->wasRecentlyCreated = true;

    $this->fireModelEvent('created', false);

    $this->syncOriginal();

    return true;
  }

  /**
   * Sync the original attributes with the current.
   *
   * @return $this
   */
  public function syncOriginal()
  {
    $this->original = $this->getAttributes();

    return $this;
  }

  /**
   * Perform any actions that are necessary after the model is saved.
   *
   * @param  array  $options
   * @return void
   */
  protected function finishSave()
  {
    $this->fireModelEvent('saved', false);

    $this->syncOriginal();
  }

  /**
   * @todo
   *
   * @return void
   */
  public function save()
  {
    if ($this->fireModelEvent('saving') === false) {
      return false;
    }

    if ($this->exists) {
      $saved = $this->isDirty() ?
        $this->performUpdate() : true;
    } else {
      $saved = $this->performInsert();
    }

    if ($saved) {
      $this->finishSave();
    }

    return $saved;
  }

  /**
   * Delete the model from the database.
   *
   * @return bool|null
   *
   * @throws \Exception
   */
  public function delete()
  {
    if (is_null($this->getKeyName())) {
      throw new Exception('No primary key defined on model.');
    }

    if (!$this->exists) {
      return;
    }

    if ($this->fireModelEvent('deleting') === false) {
      return false;
    }

    if ($wasDeleted = $this->performDeleteOnModel()) {
      $this->fireModelEvent('deleted', false);

      return $wasDeleted;
    }

    return false;
  }

  /**
   * Perform the actual delete query on this model instance.
   *
   * @return void
   */
  protected function performDeleteOnModel()
  {
    if ($wasDeleted = $this->performDeleteRequest($this->getRelationId(), $this->getKey())) {
      $this->exists = false;
      return $wasDeleted;
    }

    return false;
  }

  /**
   * Clone the model into a new, non-existing instance.
   *
   * @param  array|null $except
   * @return static
   */
  public function replicate(array $except = null)
  {
    $defaults = [
      $this->getKeyName(),
      $this->getCreatedAtColumn(),
      $this->getUpdatedAtColumn(),
    ];

    $attributes = Arr::except(
      $this->getAttributes(),
      $except ? array_unique(array_merge($except, $defaults)) : $defaults
    );

    return tap(new static, function ($instance) use ($attributes) {
      $instance->setRawAttributes($attributes);

      $instance->fireModelEvent('replicating', false);
    });
  }

  /**
   * Convert the object into something JSON serializable.
   *
   * @return array
   */
  public function jsonSerialize()
  {
    return $this->toArray();
  }

  /**
   * @todo
   *
   * @return void
   */
  public static function destroy()
  {
  }

  /**
   * @todo
   *
   * @return void
   */
  public function update()
  {
  }

  /**
   * @todo
   */
  public static function updateOrCreate()
  {
  }

  /**
   * Fill the model with an array of attributes.
   *
   * @param  array  $attributes
   * @return $this
   *
   * @throws MassAssignmentException
   */
  public function fill(array $attributes)
  {
    $totallyGuarded = $this->totallyGuarded();

    foreach ($this->fillableFromArray($attributes) as $key => $value) {
      if ($this->isFillable($key)) {
        $this->setAttribute($key, $value);
      } else if ($totallyGuarded) {
        throw new MassAssignmentException(sprintf(
          'Add [%s] to fillable property to allow mass assignment on [%s].',
          $key,
          get_class($this)
        ));
      }
    }

    return $this;
  }

  /**
   * @param static $other
   * @return bool
   */
  public function is(self $other)
  {
    return $this->id === $other->id;
  }

  /**
   * Convert the model instance to an array.
   *
   * @return array
   */
  public function toArray()
  {
    return array_merge($this->attributesToArray());
  }

  /**
   * Convert the model instance to JSON.
   *
   * @param  int  $options
   * @return string
   *
   * @throws JsonEncodingException
   */
  public function toJson($options = 0)
  {
    $json = json_encode($this->jsonSerialize(), $options);

    if (JSON_ERROR_NONE !== json_last_error()) {
      throw JsonEncodingException::forModel($this, json_last_error_msg());
    }

    return $json;
  }

  /**
   * Get the value of the model's route key.
   *
   * @return mixed
   */
  public function getRouteKey()
  {
    return $this->getAttribute($this->getRouteKeyName());
  }

  /**
   * Get the route key for the model.
   *
   * @return string
   */
  public function getRouteKeyName()
  {
    return $this->getResolvableField();
  }

  /**
   * Retrieve the model for a bound value.
   *
   * @param  mixed  $value
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function resolveRouteBinding($value)
  {
    return static::resolve($value);
  }

  /**
   * Get the value indicating whether the IDs are incrementing.
   *
   * @return bool
   */
  public function getIncrementing()
  {
    return $this->incrementing;
  }

  /**
   * @param mixed $offset
   * @return bool
   */
  public function offsetExists($offset)
  {
    return !is_null($this->getAttribute($offset));
  }

  /**
   * @param mixed $offset
   * @return mixed
   */
  public function offsetGet($offset)
  {
    $this->getAttribute($offset);
  }

  /**
   * @param mixed $offset
   * @param mixed $value
   * @return void
   */
  public function offsetSet($offset, $value)
  {
    $this->setAttribute($offset, $value);
  }

  /**
   * @param mixed $offset
   * @return void
   */
  public function offsetUnset($offset)
  {
    unset($this->attributes[$offset]);
  }

  /**
   * Check if the model needs to be booted and if so, do it.
   *
   * @return void
   */
  protected function bootIfNotBooted()
  {
    if (!isset(static::$booted[static::class])) {
      static::$booted[static::class] = true;
      $this->fireModelEvent('booting', false);

      static::booting();
      static::boot();
      static::booted();

      $this->fireModelEvent('booted', false);
    }
  }

  /**
   * Bootstrap the model and its traits.
   *
   * @return void
   */
  protected static function boot()
  {
    static::bootTraits();
  }

  /**
   * Boot all of the bootable traits on the model.
   *
   * @return void
   */
  protected static function bootTraits()
  {
    $class = static::class;
    $booted = [];
    static::$traitInitializers[$class] = [];
    foreach (class_uses_recursive($class) as $trait) {
      $method = 'boot' . class_basename($trait);
      if (method_exists($class, $method) && !in_array($method, $booted)) {
        forward_static_call([$class, $method]);
        $booted[] = $method;
      }
      if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
        static::$traitInitializers[$class][] = $method;
        static::$traitInitializers[$class] = array_unique(
          static::$traitInitializers[$class]
        );
      }
    }
  }

  /**
   * Dynamically retrieve attributes on the model.
   *
   * @param  string  $key
   * @return mixed
   */
  public function __get($key)
  {
    return $this->getAttribute($key);
  }

  /**
   * Dynamically set attributes on the model.
   *
   * @param  string  $key
   * @param  mixed  $value
   * @return void
   */
  public function __set($key, $value)
  {
    $this->setAttribute($key, $value);
  }

  /**
   * Initialize any initializable traits on the model.
   *
   * @return void
   */
  protected function initializeTraits()
  {
    foreach (static::$traitInitializers[static::class] as $method) {
      $this->{$method}();
    }
  }

  /**
   * Get the value of the model's primary key.
   *
   * @return mixed
   */
  public function getKey()
  {
    return $this->getAttribute($this->getKeyName());
  }

  /**
   * Get the primary key for the model.
   *
   * @return string
   */
  public function getKeyName()
  {
    return $this->primaryField;
  }

  /**
   * Set the primary key for the model.
   *
   * @param  string  $key
   * @return $this
   */
  public function setKeyName($key)
  {
    $this->primaryField = $key;

    return $this;
  }

  /**
   * Get the auto-incrementing key type.
   *
   * @return string
   */
  public function getKeyType()
  {
    return $this->keyType;
  }

  /**
   * Set the data type for the primary key.
   *
   * @param  string  $type
   * @return $this
   */
  public function setKeyType($type)
  {
    $this->keyType = $type;

    return $this;
  }

  /**
   * Clear the list of booted models so they will be re-booted.
   *
   * @return void
   */
  public static function clearBootedModels()
  {
    static::$booted = [];
  }

  /**
   * Handle dynamic static method calls into the method.
   *
   * @param  string  $method
   * @param  array  $parameters
   * @return mixed
   */
  public static function __callStatic($method, $parameters)
  {
    return (new static)->$method(...$parameters);
  }

  /**
   * Convert the model to its string representation.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->toJson();
  }

  /**
   * When a model is being unserialized, check if it needs to be booted.
   *
   * @return void
   */
  public function __wakeup()
  {
    $this->bootIfNotBooted();
  }

  /**
   * Register a booting model event with the dispatcher.
   *
   * @param  \Closure|string  $callback
   * @return void
   */
  protected static function booting($callback)
  {
    if (has_trait(static::class, HasEvents::class)) {
      static::registerModelEvent('booting', $callback);
    }
  }

  /**
   * Register a booted model event with the dispatcher.
   *
   * @param  \Closure|string  $callback
   * @return void
   */
  protected static function booted($callback)
  {
    if (has_trait(static::class, HasEvents::class)) {
      static::registerModelEvent('booted', $callback);
    }
  }
}
