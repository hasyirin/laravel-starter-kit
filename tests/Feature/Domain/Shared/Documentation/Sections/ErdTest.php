<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\Erd;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the diagram shows each table with its columns, simple types, and keys', function (): void {
    Schema::create('shelves', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('books', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('shelf_id')->constrained();
        $table->boolean('borrowed');
        $table->timestamps();
    });

    $erd = new Erd()->render([]);

    expect($erd)
        ->toStartWith("```mermaid\nerDiagram\n")
        ->toContain(<<<'MD'
                books {
                    integer id PK
                    integer shelf_id FK
                    boolean borrowed
                    datetime created_at
                    datetime updated_at
                }
            MD);
});

test('each foreign key draws a line from the parent to its children', function (Closure $key, string $line): void {
    Schema::create('shelves', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('books', function (Blueprint $table) use ($key): void {
        $table->id();
        $key($table);
    });

    $erd = new Erd()->render([]);

    expect($erd)->toContain($line);
})->with([
    'a required key' => [fn (Blueprint $table) => $table->foreignId('shelf_id')->constrained(), '    shelves ||--o{ books : "shelf_id"'],
    'a nullable key' => [fn (Blueprint $table) => $table->foreignId('shelf_id')->nullable()->constrained(), '    shelves |o--o{ books : "shelf_id"'],
    'a unique key' => [fn (Blueprint $table) => $table->foreignId('shelf_id')->unique()->constrained(), '    shelves ||--o| books : "shelf_id"'],
]);

test('a partial or expression unique index does not make a key unique', function (string $index): void {
    Schema::create('shelves', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('books', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('shelf_id')->constrained();
        $table->integer('slot')->nullable();
        $table->boolean('borrowed');
    });
    DB::statement($index);

    $erd = new Erd()->render([]);

    expect($erd)->toContain('    shelves ||--o{ books : "shelf_id"');
})->with([
    'a partial index' => ['create unique index books_shelf_unique on books (shelf_id) where borrowed = 0'],
    'an expression index' => ['create unique index books_shelf_unique on books (shelf_id, coalesce(slot, 0))'],
]);

test('each column shows a simple type instead of the SQLite type', function (string $declared, string $type): void {
    DB::statement("create table samples (value {$declared})");

    $erd = new Erd()->render([]);

    expect($erd)->toContain("    samples {\n        {$type} value\n    }");
})->with([
    'varchar' => ['varchar', 'string'],
    'tinyint(1)' => ['tinyint(1)', 'boolean'],
    'numeric' => ['numeric', 'decimal'],
    'double' => ['double', 'float'],
    'blob' => ['blob', 'binary'],
    'jsonb' => ['jsonb', 'json'],
    'a type with a simple name' => ['text', 'text'],
]);

test('the diagram leaves out the tables of the framework', function (string $table): void {
    $erd = new Erd()->render([]);

    expect($erd)
        ->toContain('    users {')
        ->not->toContain("    {$table} {");
})->with(['cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations', 'password_reset_tokens', 'sessions']);

test('polymorphic column pairs have no line, so the diagram lists them below it', function (): void {
    Schema::create('comments', function (Blueprint $table): void {
        $table->id();
        $table->morphs('commentable');
        $table->string('mime_type');
    });

    $erd = new Erd()->render([]);

    expect($erd)
        ->toContain('- `comments.commentable_type` and `comments.commentable_id`')
        ->not->toContain('`comments.mime_type`');
});
