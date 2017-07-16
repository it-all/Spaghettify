<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

class Field
{
    private $tag; // input, textarea, select
    private $inputType; // text, number, etc
    private $id;
    private $name;
    private $label;
    private $validation;
    private $persist;

    function __construct(string $tag = 'input', string $inputType = null, string $id = null, string $name = null, string $label = null, array $validation = [], bool $persist = true)
    {
        $this->tag = $tag;
        $this->inputType = $inputType;
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->validation = $validation;
        $this->persist = $persist;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getInputType()
    {
        return $this->inputType;
    }

    public function getValidation(): array
    {
        return $this->validation;
    }

    public function getPersist(): bool
    {
        return $this->persist;
    }
}
