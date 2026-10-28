<?php

declare(strict_types=1);

namespace Tests\Fixtures\Enums;

enum Folder: string
{
    case Documents = 'documents';
    case Photos = 'photos';
}
