<?php

namespace App\Core\Abstract;

use App\Core\Interfaces\DtoInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractDto implements DtoInterface
{
    public function __construct(array $data)
    {
        $validated = $this->validateData($data);
        $this->fillProperties($validated);
    }
    
    abstract protected function rules(): array;
    
    protected function validateData(array $data): array
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
        return new static($data);
    }
    
    public function validate(): bool
    {
        try {
            $this->validateData($this->toArray());
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }
}