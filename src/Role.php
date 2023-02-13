<?php

namespace Wallo\FilamentCompanies;

use JsonSerializable;

class Role implements JsonSerializable
{
    /**
     * The key identifier for the role.
     *
     * @var string
     */
    public string $key;

    /**
     * The name of the role.
     *
     * @var string
     */
    public string $name;

    /**
     * The role's permissions.
     *
     * @var array
     */
    public array $permissions;

    /**
     * The role's description.
     *
     * @var string
     */
    public string $description;

    /**
     * Create a new role instance.
     *
     * @param  string  $key
     * @param  string  $name
     * @param  array  $permissions
     * @return void
     */
    public function __construct(string $key, string $name, array $permissions)
    {
        $this->key = $key;
        $this->name = $name;
        $this->permissions = $permissions;
    }

    /**
     * Describe the role.
     *
     * @param  string  $description
     * @return $this
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the JSON serializable representation of the object.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'name' => __($this->name),
            'description' => __($this->description),
            'permissions' => $this->permissions,
        ];
    }
}
