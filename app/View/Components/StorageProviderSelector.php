<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StorageProviderSelector extends Component
{
    /**
     * The currently selected provider.
     *
     * @var string
     */
    public string $selected;

    /**
     * Create a new component instance.
     *
     * @param string|null $selected The currently selected provider
     */
    public function __construct(?string $selected = null)
    {
        $this->selected = $selected ?? config('cloud-storage.default');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.storage-provider-selector');
    }
}
