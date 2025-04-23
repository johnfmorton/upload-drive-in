<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    /**
     * Render the Privacy Policy page.
     */
    public function privacyPolicy(): View
    {
        $html = $this->renderMarkdown('privacy-policy', 'privacy-policy');

        return view('privacy-policy.show', compact('html'));
    }

    /**
     * Render the Terms and Conditions page.
     */
    public function termsAndConditions(): View
    {
        $html = $this->renderMarkdown('terms', 'terms');

        return view('terms.show', compact('html'));
    }

    /**
     * Load and parse a Markdown file, with optional override.
     *
     * @param  string  $directory  The subdirectory under resources/views
     * @param  string  $filename   The base filename (without extension)
     * @return string  HTML-rendered content
     */
    protected function renderMarkdown(string $directory, string $filename): string
    {
        $override = resource_path("views/{$directory}/{$filename}.override.md");
        $default  = resource_path("views/{$directory}/{$filename}.md");

        $path = file_exists($override) ? $override : $default;
        $markdown = file_get_contents($path);

        // Convert GitHub-flavored Markdown to HTML
        return Str::markdown($markdown);
    }
}
