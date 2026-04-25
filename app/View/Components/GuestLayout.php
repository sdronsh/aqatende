<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public bool $fullBleed;

    public function __construct(bool $fullBleed = false)
    {
        $this->fullBleed = $fullBleed;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest', [
            'fullBleed' => $this->fullBleed,
        ]);
    }
}
