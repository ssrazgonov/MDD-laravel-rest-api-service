<?php

namespace App\Core\Abstract;

use App\Core\Interfaces\ActionableInterface;
use App\Modules\Base\Dto\BaseDto;

class AbstractAction implements ActionableInterface
{
    public function __invoke(BaseDto $baseDto)
    {

    }
}