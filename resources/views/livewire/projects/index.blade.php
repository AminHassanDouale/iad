<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\Priority;
use App\Models\Status;
use App\Traits\ClearsProperties;
use App\Traits\ResetsPaginationWhenPropsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, ResetsPaginationWhenPropsChanges, ClearsProperties;

    #[Url]
    public string $name = '';

    #[Url]
    public int $status_id = 0;

    #[Url]
    public ?int $category_id = 0;
     #[Url]
     public ?int $priority_id = 0;

    #[Url]
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public bool $showFilters = false;

    public function filterCount(): int
    {
        return ($this->category_id ? 1 : 0) + ($this->status_id ? 1 : 0) + ($this->priority_id ? 1 : 0) + (strlen($this->name) ? 1 : 0);
    }

    public function projects(): LengthAwarePaginator
    {
        return Project::query()
            ->with(['status', 'category', 'priority'])
            ->withAggregate('status', 'name')
            ->withAggregate('category', 'name')
            ->withAggregate('priority', 'name')
            ->when($this->name, fn(Builder $q) => $q->where('name', 'like', "%$this->name%"))
            ->when($this->status_id, fn(Builder $q) => $q->where('status_id', $this->status_id))
            ->when($this->category_id, fn(Builder $q) => $q->where('category_id', $this->category_id))
            ->when($this->priority_id, fn(Builder $q) => $q->where('priority_id', $this->priority_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(7);
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'No', 'class' => '', 'sortable' => true],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'status.name', 'label' => 'Status', 'sortBy' => 'status_name', 'class' => 'hidden lg:table-cell'],
            ['key' => 'category.name', 'label' => 'Category', 'sortBy' => 'category_name', 'class' => 'hidden lg:table-cell'],
            ['key' => 'priority.name', 'label' => 'Priority', 'sortBy' => 'priority_name', ],
        ];
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'projects' => $this->projects(),
            'statuses' => Status::all(),
            'priorities' => Priority::all(),
            'categories' => Category::all(),
            'filterCount' => $this->filterCount()
        ];
    }
}; ?>

<div>
    {{--  HEADER  --}}
    <x-header title="Projects" separator progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Name..." wire:model.live.debounce="name" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-button
                label="Filters"
                icon="o-funnel"
                :badge="$filterCount"
                badge-classes="font-mono"
                @click="$wire.showFilters = true"
                class="bg-base-300"
                responsive />

            <x-button label="Create" icon="o-plus" link="/projects/create" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    {{--  TABLE --}}
    <x-card>
        @if($projects->count() > 0)
        <x-table :headers="$headers" :rows="$projects"  :sort-by="$sortBy" with-pagination>
            @scope('actions', $project)
            <x-button :link="'/projects/' . $project->id . '/edit'" icon="o-eye" class="btn-sm btn-ghost text-error" spinner />

            @endscope
        </x-table>
        @else
        <div class="flex items-center justify-center gap-10 mx-auto">
            <div>
                <img src="/images/no-results.png" width="300" />
            </div>
            <div class="text-lg font-medium">
                Desole {{ Auth::user()->name }}, Pas des Project.
            </div>
        </div>
    @endif
    </x-card>

    {{-- FILTERS --}}
    <x-drawer wire:model="showFilters" title="Filters" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-input label="Name ..." wire:model.live.debounce="name" icon="o-user" inline />
            <x-select label="Status" :options="$statuses" wire:model.live="status_id" icon="o-map-pin" placeholder="All" placeholder-value="0" inline />
            <x-select label="Category" :options="$categories" wire:model.live="category_id" icon="o-flag" placeholder="All" placeholder-value="0" inline />
            <x-select label="Priority" :options="$priorities" wire:model.live="priority_id" icon="o-flag" placeholder="All" placeholder-value="0" inline />
          
        </div>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-drawer>
</div>
