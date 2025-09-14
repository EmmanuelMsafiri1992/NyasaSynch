<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CodingWorkspace extends BaseModel
{
    protected $fillable = [
        'user_id', 'course_module_id', 'workspace_name', 'language', 'code',
        'files', 'description', 'is_shared', 'share_token', 'execution_results',
        'last_run_at'
    ];

    protected $casts = [
        'files' => 'array',
        'is_shared' => 'boolean',
        'execution_results' => 'array',
        'last_run_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (!$workspace->share_token) {
                $workspace->share_token = Str::random(32);
            }
        });
    }

    /**
     * Get the user that owns the workspace
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course module this workspace belongs to
     */
    public function courseModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class);
    }

    /**
     * Get the default code template for the language
     */
    public function getDefaultTemplate(): string
    {
        $templates = [
            'javascript' => "// JavaScript Workspace\nconsole.log('Hello, World!');\n",
            'python' => "# Python Workspace\nprint('Hello, World!')\n",
            'java' => "public class Main {\n    public static void main(String[] args) {\n        System.out.println(\"Hello, World!\");\n    }\n}\n",
            'php' => "<?php\n// PHP Workspace\necho 'Hello, World!';\n",
            'html_css' => "<!DOCTYPE html>\n<html>\n<head>\n    <style>\n        body { font-family: Arial, sans-serif; }\n    </style>\n</head>\n<body>\n    <h1>Hello, World!</h1>\n</body>\n</html>\n",
            'react' => "import React from 'react';\n\nfunction App() {\n  return (\n    <div>\n      <h1>Hello, World!</h1>\n    </div>\n  );\n}\n\nexport default App;\n",
            'node' => "// Node.js Workspace\nconst express = require('express');\nconst app = express();\n\napp.get('/', (req, res) => {\n  res.send('Hello, World!');\n});\n\napp.listen(3000);\n",
            'sql' => "-- SQL Workspace\nSELECT 'Hello, World!' as greeting;\n"
        ];

        return $templates[$this->language] ?? "// Welcome to your coding workspace!\n";
    }

    /**
     * Execute the code (mock implementation)
     */
    public function executeCode(): array
    {
        $this->last_run_at = now();
        $this->save();

        // This is a mock implementation
        // In a real application, you would integrate with a code execution service
        $results = [
            'output' => 'Code execution simulation - output would appear here',
            'errors' => [],
            'execution_time' => '0.045s',
            'status' => 'success'
        ];

        $this->execution_results = $results;
        $this->save();

        return $results;
    }

    /**
     * Share the workspace
     */
    public function share(): string
    {
        $this->is_shared = true;
        $this->save();

        return $this->share_token;
    }

    /**
     * Unshare the workspace
     */
    public function unshare(): void
    {
        $this->is_shared = false;
        $this->save();
    }

    /**
     * Get workspace by share token
     */
    public static function findByShareToken($token)
    {
        return static::where('share_token', $token)
            ->where('is_shared', true)
            ->first();
    }

    /**
     * Scope for shared workspaces
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope for workspaces by language
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }
}