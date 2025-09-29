<?php

namespace App\Base;

use App\Contracts\Dto as DtoContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use UnitEnum;

abstract class Dto implements DtoContract
{
    public function __construct(array $data)
    {
        $this->fillProperties($data);
    }

    protected function rules(): array
    {
        return [];
    }

    public function validateData(array $data): array
    {
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function fillProperties(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $args = [];

        foreach ($parameters as $param) {
            $name = $param->getName();
            $key = Str::snake($name);

            $value = $data[$name] ?? $data[$key] ?? null;

            $type = $param->getType();
            if ($type && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                if (is_subclass_of($typeName, UnitEnum::class) && is_string($value)) {
                    $value = $typeName::from($value);
                }
            }

            if ($value === null && $param->isDefaultValueAvailable()) {
                $value = $param->getDefaultValue();
            }

            $args[$name] = $value;
        }

        return new static(...$args);
    }

    public function validate(): bool
    {
        try {
            $this->validateData($this->toArray());

            return true;
        } catch (ValidationException) {
            return false;
        }
    }
}
