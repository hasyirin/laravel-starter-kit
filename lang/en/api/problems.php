<?php

declare(strict_types=1);

return [
    '401' => 'The request needs a valid API token.',
    '403' => 'The API client cannot make this request.',
    '404' => 'The resource does not exist.',
    '405' => 'The resource does not accept this method.',
    '422' => 'The request has data that is not valid.',
    '429' => 'There were too many requests. Try again later.',
    '500' => 'The server could not complete the request.',
];
