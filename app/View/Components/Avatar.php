<?php

namespace App\View\Components;

use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\View\Component;

class Avatar extends Component
{
    public Actor $actor;
    public string $fallback;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
        $this->fallback = asset('/img/default_avatar.svg');
        if ($actor instanceof RemoteActor) {
            $this->fallback = 'https://source.boringavatars.com/';
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.avatar');
    }
}
