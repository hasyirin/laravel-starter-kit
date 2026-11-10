<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Enums;

enum FieldType: string
{
    case Text = 'text';
    case Lines = 'lines';
    case Date = 'date';
    case Number = 'number';
    case YesNo = 'yes-no';
    case Choice = 'choice';
    case List = 'list';
    case Table = 'table';
    case Image = 'image';
    case Group = 'group';
}
