<?php

namespace Netflex\Query;

use ArrayAccess;
use Illuminate\Support\Facades\Cache;
use JsonSerializable;

use Netflex\Query\Traits\Queryable;
use Netflex\Query\Traits\ModelMapper;
use Netflex\Query\Traits\HasRelation;
use Netflex\Query\Traits\Resolvable;

use Netflex\Query\Exceptions\MassAssignmentException;
use Netflex\Query\Exceptions\JsonEncodingException;

use GuzzleHttp\Exception\GuzzleException;

use Illuminate\Support\Arr;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Routing\UrlRoutable;
use Netflex\Query\Exceptions\NotFoundException;
use Netflex\Query\Exceptions\ResolutionFailedException;
use Illuminate\Support\Traits\Macroable;
use Netflex\API\Contracts\APIClient;
use Netflex\API\Facades\APIClientConnectionResolver;
use Netflex\Files\File;
use Netflex\Pages\Components\Image;
use Netflex\Structure\File as StructureFile;

abstract class QueryableModel implements Arrayable, ArrayAccess, Jsonable, JsonSerializable, UrlRoutable
{
  use GuardsAttributes, HasAttributes, HasEvents, HasRelation, HasTimestamps, HidesAttributes, ModelMapper, Queryable, Resolvable, Macroable;

  /**
   * The connection name for the model.
   *
   * @var string|null
   */
  protected $connection;

  /**
   * The number of models to return for pagination.
   *
   * @var int
   */
  protected $perPage = 100;

  /**
   * Determines if QueryableModel::all() calls in queries should chunk the result.
   * NOTICE: If chunking is enabled, the results of QueryableModel::all() will not be cached, and can result in a performance hit on large structures.
   *
   * @var bool
   */
  protected $useChunking = false;

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
   * @var array
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
  protected $respectPublishingStatus = false;

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
   * Determines if we should cache some results.
   * Specifically ::all() and ::find() queries
   *
   * @var bool
   */
  protected $cachesResults = true;

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
   * Temporarily disable publishing status checks for the model
   *
   * @var boolean
   */
  public static $publishingStatusChecksTemporarilyDisabled = false;

  /**
   * Temporarily disable caching for the model
   *
   * @var boolean
   */
  public static $cachingTemporarilyDisabled = false;

  /**
   * @param array $attributes
   * @param bool $boot Should this model boot it's bootable traits and emit events?
   */
  public function __construct(array $attributes = [], $boot = true)
  {
    $this->dateFormat = 'Y-m-d H:i:s';

    if ($boot) {
      $this->bootIfNotBooted();
      $this->initializeTraits();
    }

    $this->fill($attributes);
  }

  public static function disablePublishingStatus()
  {
    static::$cachingTemporarilyDisabled = true;
    static::$publishingStatusChecksTemporarilyDisabled = true;
  }

  public static function enablePublishingStatus()
  {
    static::$cachingTemporarilyDisabled = false;
    static::$publishingStatusChecksTemporarilyDisabled = false;
  }

  /**
   * Get the api connection for the model.
   *
   * @return APIClient
   */
  public function getConnection()
  {
    return APIClientConnectionResolver::resolve($this->getConnectionName());
  }

  /**
   * Get the current connection name for the model.
   *
   * @return string|null
   */
  public function getConnectionName()
  {
    return $this->connection ?? 'default';
  }

  /**
   * Set the connection associated with the model.
   *
   * @param  string|null  $name
   * @return $this
   */
  public function setConnection($name)
  {
    $this->connection = $name;

    return $this;
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

  public function getPageSize()
  {
    return $this->perPage ?? 100;
  }

  public function usesChunking()
  {
    return $this->useChunking ?? false;
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
   * Retrieves the first matching model or creates it
   *
   * @param array $query
   * @param array $with
   * @return static
   */
  public static function firstOrCreate(array $query, array $with = [])
  {
    $builder = static::makeQueryBuilder();

    foreach ($query as $key => $value) {
      $builder = $builder->where($key, $value);
    }

    if ($model = $builder->first()) {
      return $model;
    }

    $attributes = array_merge($query, $with);
    $model = (new static)->newInstance($attributes, false);
    $model->save();

    return $model;
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

    if (count($dirty) > 0) {
      $dirty['revision_publish'] = true;
      $this->performUpdateRequest($this->getRelationId(), $this->getKey(), $dirty);
    }

    $this->withIgnoredPublishingStatus(function () {
      $this->attributes = $this->performRetrieveRequest($this->getRelationId(), $this->getKey());
    });

    $this->fireModelEvent('updated', false);

    if (!static::$cachingTemporarilyDisabled && $this->cachesResults) {
      $entryCacheKey = $this->getCacheIdentifier($this->getKey());
      $entriesCacheKey = $this->getAllCacheIdentifier();

      Cache::forget($entryCacheKey);
      Cache::forget($entriesCacheKey);
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

    if ($this->autoPublishes && !array_key_exists('published', $this->getDirty())) {
      $attributes['published'] = true;
    }

    // Special handling for inserting File, Image, or Entry objects
    array_walk_recursive($attributes, function (&$item, $key) {
      if ($item instanceof File || $item instanceof StructureFile || $item instanceof Image || $item instanceof QueryableModel) {
        $item = $item->id;
      }
    });

    $this->attributes[$this->getKeyName()] = $this->performInsertRequest($this->getRelationId(), $attributes);

    $this->withIgnoredPublishingStatus(function () {
      $this->attributes = $this->performRetrieveRequest($this->getRelationId(), $this->getKey());
    });

    $this->exists = true;

    $this->wasRecentlyCreated = true;

    $this->fireModelEvent('created', false);

    $this->syncOriginal();

    if (!static::$cachingTemporarilyDisabled && $this->cachesResults) {
      $entriesCacheKey = $this->getAllCacheIdentifier();

      Cache::forget($entriesCacheKey);
    }

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
   * Updates or stores the model
   *
   * @return bool
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
   * Destroys one or multiple instances by primary key
   *
   * @param mixed|array|Collection $identifiers
   * @return bool
   */
  public static function destroy(...$identifiers)
  {
    $identifiers = Collection::make($identifiers)->flatten()->toArray();
    $models = static::findMany($identifiers);

    $destroyed = $models->map(function (QueryableModel $model) {
      return $model->delete();
    })->reduce(function ($carry, $wasDeleted) {
      return $carry && $wasDeleted;
    }, true);

    return $destroyed && $models->count() === count($identifiers);
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

      if (!static::$cachingTemporarilyDisabled && $this->cachesResults) {
        $entryCacheKey = $this->getCacheIdentifier($this->getKey());
        $entriesCacheKey = $this->getAllCacheIdentifier();

        Cache::forget($entryCacheKey);
        Cache::forget($entriesCacheKey);
      }

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
   * Update the model
   *
   * @param array $attributes
   * @return bool
   */
  public function update(array $attributes = [])
  {
    if (!$this->exists) {
      return false;
    }

    return $this->fill($attributes)->save();
  }

  /**
   * Updates an exisisting model or creates it
   *
   * @param array $query
   * @param array $with
   * @return static
   */
  public static function updateOrCreate(array $query, array $with = [])
  {
    $builder = static::makeQueryBuilder();

    foreach ($query as $key => $value) {
      $builder = $builder->where($key, $value);
    }

    if ($model = $builder->first()) {
      $model->update($with);
      return $model;
    }

    $attributes = array_merge($query, $with);
    $model = (new static)->newInstance($attributes, false);
    $model->save();

    return $model;
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
    return trim($this->getAttribute($this->getRouteKeyName()), '/');
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
   * @param  mixed  $rawValue
   * @param  string|null $field
   * @return \Illuminate\Database\Eloquent\Model|null
   * @throws NotFoundException
   */
  public function resolveRouteBinding($rawValue, $field = null)
  {
    $field = $field ?? $this->getResolvableField();
    $query = static::where($field, $rawValue)
      ->orWhere($field, $rawValue . '/');

    /** @var static */
    if ($model = $query->first()) {
      if ($field !== 'url') {
        return $model->{$field} == $rawValue
          ? $model
          : null;
      }

      return $model;
    }

    $e = new NotFoundException;
    $e->setModel(static::class, [$rawValue]);

    throw $e;
  }

  /**
   * Resolves an instance
   *
   * @param mixed $resolveBy
   * @param  string|null $field
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolve($rawValue, $field = null)
  {
    try {
      return with(new static)
        ->resolveRouteBinding($rawValue, $field);
    } catch (NotFoundException $e) {
      return null;
    } catch (ResolutionFailedException $e) {
      return null;
    }
  }

  /**
   * Retrieve the child model for a bound value.
   *
   * @param  string   $childType
   * @param  mixed   $value
   * @param  string|null  $field
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function resolveChildRouteBinding($childType, $value, $field)
  {
    return $this->{Str::plural($childType)}()->where($field, $value)->first();
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
    return $this->getAttribute($offset);
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

      static::boot();

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

  /**
   * Shim to make model compatible with HasAttributes trait
   *
   * @param string $key
   * @return bool
   */
  protected function relationLoaded($key)
  {
    return false;
  }

  /**
   * @param string $key
   * @return bool
   */
  public function __isset($key)
  {
    return $this->__get($key) !== null;
  }

  /**
   * @return array
   */
  public function __debugInfo()
  {
    return $this->attributes;
  }
}
